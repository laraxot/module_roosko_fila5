<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Labeling\Actions\PrintLabelAction;
use Modules\Labeling\Models\Identifier;
use Modules\Rentri\Actions\SubmitFirAcceptanceAction;
use Modules\Rentri\Models\RentriCredential;
use Modules\Roosko\Actions\AcceptDeliveryAction;
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Actions\RejectDeliveryAction;
use Modules\Roosko\Datas\AcceptedUnitData;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Exceptions\DeliveryException;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Warehouse\Models\StockUnit;
use PHPUnit\Framework\Assert;
use Spatie\QueueableAction\ActionJob;

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

/**
 * Job di una queueable action accodati (ActionJob::displayName() = classe della action).
 */
function pushedActions(string $actionClass): int
{
    return Queue::pushed(ActionJob::class, static fn (ActionJob $job): bool => $job->displayName() === $actionClass)->count();
}

describe('flusso 1 — conferimento, pesata, accettazione', function (): void {
    test('FIR :dataset → 2 StockUnit + 2 WasteLot + 1 carico registro + QR', function (string $ewcProperty): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $ewc = $plant->ewcForProperty($ewcProperty);
        $loadsBefore = RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'load')->count();

        $result = $plant->receiveAndAccept($ewc, [1200.0, 800.0]);
        $delivery = $result['delivery']->refresh();

        Assert::assertSame(DeliveryStatusEnum::ACCEPTED, $delivery->status);
        Assert::assertMatchesRegularExpression('/^CNF-\d{4}-\d{6}$/', $delivery->code);
        Assert::assertEqualsWithDelta(2000.0, $delivery->acceptedKg(), 0.001);
        Assert::assertNotNull($delivery->weighing_id);

        $lots = WasteLot::query()->where('delivery_id', $delivery->id)->get();
        Assert::assertCount(2, $lots);
        foreach ($lots as $lot) {
            Assert::assertSame($ewc->id, $lot->ewc_code_id);
            Assert::assertSame(LotClassificationEnum::WASTE, $lot->classification);
            Assert::assertSame($plant->producer->id, $lot->producer_operator_id);
            Assert::assertSame($ewc->is_hazardous, $lot->isHazardous());
        }

        Assert::assertCount(2, $result['units']);
        Assert::assertEqualsWithDelta([1200.0, 800.0], array_map(static fn (StockUnit $unit): float => (float) $unit->net_weight_kg, $result['units']), 0.001);
        foreach ($result['units'] as $unit) {
            Assert::assertSame(1, Identifier::query()->where('identifiable_type', $unit->getMorphClass())->where('identifiable_id', $unit->id)->where('type', 'qr')->where('is_active', true)->count());
        }

        $loads = RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'load');
        Assert::assertSame($loadsBefore + 1, $loads->count());
        $entry = $loads->orderByDesc('id')->firstOrFail();
        Assert::assertSame($result['fir']->id, (int) $entry->fir_id);
        Assert::assertSame($plant->producer->id, (int) $entry->counterpart_operator_id);
        Assert::assertEqualsWithDelta(2000.0, (float) $entry->quantity_kg, 0.001);

        Assert::assertSame(2, pushedActions(PrintLabelAction::class));
        Assert::assertSame(0, pushedActions(SubmitFirAcceptanceAction::class));
    })->with([
        'non pericoloso 19 12 04' => ['plastics'],
        'pericoloso 16 01 07*' => ['oilFilters'],
    ]);

    test('FIR digitale con credenziale del sito: accettazione accodata verso RENTRI', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        RentriCredential::factory()->create(['operator_site_id' => $plant->plant->id, 'is_active' => true]);

        $plant->receiveAndAccept($plant->plastics, [500.0], digital: true);

        Assert::assertSame(1, pushedActions(SubmitFirAcceptanceAction::class));
    });

    test('FIR digitale senza credenziale: nessun invio RENTRI', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;

        $plant->receiveAndAccept($plant->plastics, [500.0], digital: true);

        Assert::assertSame(0, pushedActions(SubmitFirAcceptanceAction::class));
    });

    test('accettazione parziale: stato partially_accepted e kg respinti', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, 2000.0);
        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);

        app(AcceptDeliveryAction::class)->execute($delivery, new DeliveryAcceptanceData(
            acceptedKg: 1500.0,
            units: [new AcceptedUnitData(ContainerTypeEnum::from('skip'), 1500.0)],
            operation: WasteOperationEnum::from('R13'),
            rejectedKg: 500.0,
            rejectionReason: 'Frazione estranea',
        ));

        $delivery->refresh();
        Assert::assertSame(DeliveryStatusEnum::PARTIALLY_ACCEPTED, $delivery->status);
        Assert::assertEqualsWithDelta(500.0, $delivery->rejectedKg(), 0.001);
    });

    test('lo stesso FIR non si conferisce due volte', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics);
        app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);

        expect(static fn () => app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse))
            ->toThrow(DeliveryException::class);
    });

    test('respingimento: nessun lotto e nessun carico di registro', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $fir = $plant->fir($plant->plastics, 900.0);
        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $plant->plant, $plant->warehouse);
        $loadsBefore = RegisterEntry::query()->where('register_id', $plant->register->id)->count();

        app(RejectDeliveryAction::class)->execute($delivery, 'Rifiuto non conforme al FIR');

        Assert::assertSame(DeliveryStatusEnum::REJECTED, $delivery->refresh()->status);
        Assert::assertEqualsWithDelta(900.0, $delivery->rejectedKg(), 0.001);
        Assert::assertSame(0, WasteLot::query()->where('delivery_id', $delivery->id)->count());
        Assert::assertSame($loadsBefore, RegisterEntry::query()->where('register_id', $plant->register->id)->count());
    });
});
