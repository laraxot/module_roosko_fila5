<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Roosko\Actions\AcceptDeliveryAction;
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Datas\AcceptedUnitData;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Exceptions\AuthorizationException;
use Modules\Roosko\Exceptions\DeliveryException;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Warehouse\Models\StockUnit;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

/**
 * @param  list<float>  $unitsKg
 */
function acceptance(float $acceptedKg, array $unitsKg, string $operation = 'R13'): DeliveryAcceptanceData
{
    return new DeliveryAcceptanceData(
        acceptedKg: $acceptedKg,
        units: array_map(static fn (float $kg): AcceptedUnitData => new AcceptedUnitData(ContainerTypeEnum::from('drum'), $kg), $unitsKg),
        operation: WasteOperationEnum::from($operation),
    );
}

describe('somma delle unita\' in accettazione', function (): void {
    test('somma entro la tolleranza di 1 g: accettato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $delivery = app(ReceiveDeliveryAction::class)->execute($plant->fir($plant->plastics, 1000.0), $plant->plant, $plant->warehouse);

        app(AcceptDeliveryAction::class)->execute($delivery, acceptance(1000.0, [333.333, 333.333, 333.3335]));

        Assert::assertSame(DeliveryStatusEnum::ACCEPTED, $delivery->refresh()->status);
        Assert::assertSame(3, WasteLot::query()->where('delivery_id', $delivery->id)->count());
    });

    test('somma diversa dai kg accettati: eccezione e nessuna scrittura', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $delivery = app(ReceiveDeliveryAction::class)->execute($plant->fir($plant->plastics, 1000.0), $plant->plant, $plant->warehouse);
        $unitsBefore = StockUnit::query()->count();

        expect(static fn () => app(AcceptDeliveryAction::class)->execute($delivery, acceptance(1000.0, [600.0, 399.0])))
            ->toThrow(DeliveryException::class);

        Assert::assertSame(DeliveryStatusEnum::ARRIVED, $delivery->refresh()->status);
        Assert::assertSame(0, WasteLot::query()->where('delivery_id', $delivery->id)->count());
        Assert::assertSame($unitsBefore, StockUnit::query()->count());
    });

    test('nessuna unita\': eccezione', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $delivery = app(ReceiveDeliveryAction::class)->execute($plant->fir($plant->plastics), $plant->plant, $plant->warehouse);

        expect(static fn () => app(AcceptDeliveryAction::class)->execute($delivery, acceptance(0.0, [])))
            ->toThrow(DeliveryException::class);
    });

    test('operazione non autorizzata per il codice: eccezione', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $delivery = app(ReceiveDeliveryAction::class)->execute($plant->fir($plant->plastics, 500.0), $plant->plant, $plant->warehouse);

        expect(static fn () => app(AcceptDeliveryAction::class)->execute($delivery, acceptance(500.0, [500.0], 'D1')))
            ->toThrow(AuthorizationException::class);
    });
});
