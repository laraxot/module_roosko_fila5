<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Processing\Enums\OutputKindEnum;
use Modules\Processing\Enums\ProcessingOrderStatusEnum;
use Modules\Processing\Models\LineageLink;
use Modules\Roosko\Actions\CompleteTreatmentAction;
use Modules\Roosko\Actions\RegisterTreatmentOutputAction;
use Modules\Roosko\Actions\StartTreatmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Actions\TraceLotOriginAction;
use Modules\Roosko\Datas\TreatmentOutputData;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

describe('flusso 3 — trattamento con nuove unita\'', function (): void {
    test('2 input uguali → scarichi, EoW + scarto 19 12 12, lineage 0.5/0.5, tracciabilita\' fino ai FIR', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $first = $plant->receiveAndAccept($plant->plastics, [1000.0]);
        $second = $plant->receiveAndAccept($plant->plastics, [1000.0]);
        $inputs = [$first['units'][0], $second['units'][0]];
        foreach ($inputs as $unit) {
            app(StoreLotAction::class)->execute($unit, $plant->storageLocation);
        }
        $unloadsBefore = RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'unload')->count();

        $order = app(StartTreatmentAction::class)->execute($plant->shredder, WasteOperationEnum::R12, collect($inputs));

        Assert::assertSame(ProcessingOrderStatusEnum::IN_PROGRESS, $order->refresh()->status);
        Assert::assertSame('R12', $order->operation_code);
        Assert::assertSame($unloadsBefore + 2, RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'unload')->where('cause', 'internal_treatment')->count());
        foreach ($inputs as $unit) {
            Assert::assertSame(StockUnitStatusEnum::CONSUMED, $unit->refresh()->status);
        }

        $product = app(RegisterTreatmentOutputAction::class)->execute($order, new TreatmentOutputData(
            ewcCodeId: $plant->plastics->id,
            physicalState: PhysicalStateEnum::SOLID_NON_POWDERY,
            hazardProperties: [],
            classification: LotClassificationEnum::END_OF_WASTE,
            destination: LotDestinationEnum::SALE,
            containerType: ContainerTypeEnum::BIG_BAG,
            netWeightKg: 1500.0,
            kind: OutputKindEnum::PRODUCT,
            locationId: $plant->storageLocation->id,
            eowBatchCode: 'EOW-2026-001',
        ));
        $loadsBefore = RegisterEntry::query()->where('register_id', $plant->register->id)->where('cause', 'new_production')->count();
        $scrap = app(RegisterTreatmentOutputAction::class)->execute($order, new TreatmentOutputData(
            ewcCodeId: $plant->mechanicalResidue->id,
            physicalState: PhysicalStateEnum::SOLID_NON_POWDERY,
            hazardProperties: [],
            classification: LotClassificationEnum::WASTE,
            destination: LotDestinationEnum::DISPOSAL,
            containerType: ContainerTypeEnum::SKIP,
            netWeightKg: 400.0,
            kind: OutputKindEnum::SCRAP,
        ));

        Assert::assertSame($loadsBefore + 1, RegisterEntry::query()->where('register_id', $plant->register->id)->where('cause', 'new_production')->count());
        $productLot = $product->subject;
        Assert::assertInstanceOf(WasteLot::class, $productLot);
        Assert::assertSame($order->id, $productLot->processing_order_id);
        Assert::assertInstanceOf(WasteLot::class, $scrap->subject);

        app(CompleteTreatmentAction::class)->execute($order, force: true);

        $links = LineageLink::query()->where('child_stock_unit_id', $product->id)->get();
        Assert::assertCount(2, $links);
        foreach ($links as $link) {
            Assert::assertEqualsWithDelta(0.5, (float) $link->share, 0.000001);
        }

        $origin = app(TraceLotOriginAction::class)->execute($product);
        Assert::assertCount(2, $origin);
        $firIds = $origin->map(static fn (array $row): ?int => $row['fir']?->id)->sort()->values()->all();
        Assert::assertSame(collect([$first['fir']->id, $second['fir']->id])->sort()->values()->all(), $firIds);
    });

    test('un prodotto EoW senza codice di lotto e\' rifiutato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $unit = $plant->receiveAndAccept($plant->plastics, [500.0])['units'][0];
        app(StoreLotAction::class)->execute($unit, $plant->storageLocation);
        $order = app(StartTreatmentAction::class)->execute($plant->shredder, WasteOperationEnum::R12, collect([$unit]));

        expect(static fn () => app(RegisterTreatmentOutputAction::class)->execute($order, new TreatmentOutputData(
            ewcCodeId: $plant->plastics->id,
            physicalState: PhysicalStateEnum::SOLID_NON_POWDERY,
            hazardProperties: [],
            classification: LotClassificationEnum::END_OF_WASTE,
            destination: LotDestinationEnum::SALE,
            containerType: ContainerTypeEnum::BIG_BAG,
            netWeightKg: 450.0,
        )))->toThrow(LotException::class);
    });
});
