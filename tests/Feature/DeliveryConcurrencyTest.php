<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Queue;
use Modules\Roosko\Actions\AcceptDeliveryAction;
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Actions\RejectDeliveryAction;
use Modules\Roosko\Datas\AcceptedUnitData;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Exceptions\DeliveryException;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use PHPUnit\Framework\Assert;

/*
 * Doppia accettazione / respingimento / ricezione (audit database R2) e FIR in bozza.
 *
 * Su sqlite non si possono avere due sessioni concorrenti: la richiesta "in ritardo" si simula
 * con un'istanza stantia del conferimento (letta prima che l'altra richiesta committasse).
 * La rilettura sotto lock dentro la transazione deve vedere lo stato aggiornato e fermarsi.
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

function acceptanceOf(float $kg): DeliveryAcceptanceData
{
    return new DeliveryAcceptanceData(
        acceptedKg: $kg,
        units: [new AcceptedUnitData(ContainerTypeEnum::from('skip'), $kg)],
        operation: WasteOperationEnum::from('R13'),
    );
}

describe('decisione sul conferimento con istanza stantia', function (): void {
    test('seconda accettazione dello stesso conferimento: nessun lotto o carico in piu\'', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, 1000.0);
        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);
        $stale = Delivery::query()->findOrFail($delivery->id);

        app(AcceptDeliveryAction::class)->execute($delivery, acceptanceOf(1000.0));
        $loads = RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'load')->count();

        Assert::assertSame(DeliveryStatusEnum::ARRIVED, $stale->status);
        expect(static fn () => app(AcceptDeliveryAction::class)->execute($stale, acceptanceOf(1000.0)))
            ->toThrow(\InvalidArgumentException::class);

        Assert::assertSame(1, WasteLot::query()->where('delivery_id', $delivery->id)->count());
        Assert::assertSame($loads, RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'load')->count());
        Assert::assertSame(DeliveryStatusEnum::ACCEPTED, $delivery->refresh()->status);
    });

    test('respingimento dopo un\'accettazione concorrente: rifiutato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, 800.0);
        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);
        $stale = Delivery::query()->findOrFail($delivery->id);

        app(AcceptDeliveryAction::class)->execute($delivery, acceptanceOf(800.0));

        expect(static fn () => app(RejectDeliveryAction::class)->execute($stale, 'Non conforme'))
            ->toThrow(\InvalidArgumentException::class);
        Assert::assertSame(DeliveryStatusEnum::ACCEPTED, $delivery->refresh()->status);
        Assert::assertSame(FirStatusEnum::ACCEPTED, $fir->refresh()->status);
    });

    test('l\'istanza passata all\'action riflette lo stato salvato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, 500.0);
        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);

        app(RejectDeliveryAction::class)->execute($delivery, 'Carico non conforme');

        Assert::assertSame(DeliveryStatusEnum::REJECTED, $delivery->status);
        Assert::assertFalse($delivery->isDirty());
    });
});

describe('un FIR = un conferimento aperto', function (): void {
    test('l\'indice unique su open_fir_id blocca due conferimenti vivi sullo stesso FIR', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics);
        Delivery::factory()->create(['fir_id' => $fir->id, 'plant_site_id' => $plant->plant->id, 'warehouse_id' => $plant->warehouse->id]);

        expect(static fn () => Delivery::factory()->create(['fir_id' => $fir->id, 'plant_site_id' => $plant->plant->id, 'warehouse_id' => $plant->warehouse->id]))
            ->toThrow(UniqueConstraintViolationException::class);
    });

    test('dopo un respingimento o un\'eliminazione il FIR torna libero a livello di schema', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics);
        $attributes = ['fir_id' => $fir->id, 'plant_site_id' => $plant->plant->id, 'warehouse_id' => $plant->warehouse->id];

        Delivery::factory()->status(DeliveryStatusEnum::REJECTED)->create($attributes);
        Delivery::factory()->create($attributes)->delete();
        $open = Delivery::factory()->create($attributes);

        Assert::assertSame($fir->id, (int) $open->refresh()->getAttribute('open_fir_id'));
    });

    test('un FIR in stato finale non si conferisce', function (string $status): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, status: $status);

        expect(static fn () => app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse))
            ->toThrow(DeliveryException::class);
        Assert::assertSame(0, Delivery::query()->where('fir_id', $fir->id)->count());
    })->with(['cancelled', 'rejected', 'accepted']);
});

describe('FIR in bozza (default della UI di Waste)', function (): void {
    test('la ricezione porta il FIR draft fino a delivered e l\'accettazione riesce', function (string $initial): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, 1000.0, status: $initial);

        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);

        Assert::assertSame(FirStatusEnum::DELIVERED, $fir->refresh()->status);
        if ($initial !== 'delivered') {
            Assert::assertNotNull($fir->arrived_at);
        }

        app(AcceptDeliveryAction::class)->execute($delivery, acceptanceOf(1000.0));

        Assert::assertSame(DeliveryStatusEnum::ACCEPTED, $delivery->refresh()->status);
        Assert::assertSame(FirStatusEnum::ACCEPTED, $fir->refresh()->status);
    })->with(['draft', 'issued', 'in_transit', 'delivered']);
});
