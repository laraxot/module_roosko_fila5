<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Processing\Enums\OutputKindEnum;
use Modules\Rentri\Actions\IssueDigitalFirAction;
use Modules\Rentri\Models\RentriCredential;
use Modules\Roosko\Actions\AddShipmentItemAction;
use Modules\Roosko\Actions\CreateShipmentAction;
use Modules\Roosko\Actions\DispatchShipmentAction;
use Modules\Roosko\Actions\RegisterTreatmentOutputAction;
use Modules\Roosko\Actions\StartTreatmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Datas\TreatmentOutputData;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\StockUnit;
use PHPUnit\Framework\Assert;
use Spatie\QueueableAction\ActionJob;

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

/**
 * Scarto 19 12 12 stoccato, prodotto da un trattamento sui conferiti.
 */
function storedScrap(PlantFixture $plant, LotClassificationEnum $classification = LotClassificationEnum::WASTE): StockUnit
{
    $unit = $plant->receiveAndAccept($plant->plastics, [800.0])['units'][0];
    app(StoreLotAction::class)->execute($unit, $plant->storageLocation);
    $order = app(StartTreatmentAction::class)->execute($plant->shredder, WasteOperationEnum::R12, collect([$unit]));

    return app(RegisterTreatmentOutputAction::class)->execute($order, new TreatmentOutputData(
        ewcCodeId: $plant->mechanicalResidue->id,
        physicalState: PhysicalStateEnum::SOLID_NON_POWDERY,
        hazardProperties: [],
        classification: $classification,
        destination: $classification->isWaste() ? LotDestinationEnum::EXTERNAL_RECOVERY : LotDestinationEnum::SALE,
        containerType: ContainerTypeEnum::SKIP,
        netWeightKg: 750.0,
        kind: OutputKindEnum::SCRAP,
        locationId: $plant->storageLocation->id,
        eowBatchCode: $classification->isWaste() ? null : 'EOW-TEST',
    ));
}

describe('flusso 4 — uscita dello scarto verso terzi', function (): void {
    test('FIR in uscita creato, scarico registro con carichi d\'origine, unita\' shipped', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        RentriCredential::factory()->create(['operator_site_id' => $plant->plant->id, 'is_active' => true]);
        $scrap = storedScrap($plant);

        $shipment = app(CreateShipmentAction::class)->execute($plant->warehouse, $plant->recipient, ShipmentDocumentEnum::FIR, WasteOperationEnum::R13);
        Assert::assertMatchesRegularExpression('/^USC-\d{4}-\d{6}$/', $shipment->code);

        app(AddShipmentItemAction::class)->execute($shipment, $scrap);
        Assert::assertSame(ShipmentStatusEnum::LOADING, $shipment->refresh()->status);

        app(DispatchShipmentAction::class)->execute($shipment);
        $shipment->refresh();

        Assert::assertSame(ShipmentStatusEnum::DISPATCHED, $shipment->status);
        Assert::assertNotNull($shipment->fir_id);
        $fir = Fir::query()->findOrFail($shipment->fir_id);
        Assert::assertSame($plant->mechanicalResidue->id, $fir->ewc_code_id);
        Assert::assertSame($plant->recipient->id, $fir->recipient_operator_id);
        Assert::assertTrue($fir->is_digital);

        Assert::assertSame(StockUnitStatusEnum::SHIPPED, $scrap->refresh()->status);

        $unload = RegisterEntry::query()->where('register_id', $plant->register->id)->where('cause', 'to_third_parties')->orderByDesc('id')->firstOrFail();
        Assert::assertSame($fir->id, $unload->fir_id);
        Assert::assertSame($plant->recipient->id, $unload->counterpart_operator_id);
        Assert::assertNotEmpty($unload->load_entry_ids);

        Assert::assertSame(1, Queue::pushed(ActionJob::class, static fn (ActionJob $job): bool => $job->displayName() === IssueDigitalFirAction::class)->count());
    });

    test('un lotto EoW non puo\' viaggiare con FIR', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $eow = storedScrap($plant, LotClassificationEnum::END_OF_WASTE);
        $shipment = app(CreateShipmentAction::class)->execute($plant->warehouse, $plant->recipient, ShipmentDocumentEnum::FIR, WasteOperationEnum::R13);

        expect(static fn () => app(AddShipmentItemAction::class)->execute($shipment, $eow))->toThrow(ShipmentException::class);
    });

    test('vendita EoW: serve il numero della dichiarazione, nessuno scarico di registro', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $eow = storedScrap($plant, LotClassificationEnum::END_OF_WASTE);
        $shipment = app(CreateShipmentAction::class)->execute($plant->warehouse, $plant->recipient, ShipmentDocumentEnum::EOW_DECLARATION);
        app(AddShipmentItemAction::class)->execute($shipment, $eow);

        expect(static fn () => app(DispatchShipmentAction::class)->execute($shipment))->toThrow(ShipmentException::class);

        $shipment->forceFill(['document_number' => 'DC-2026-15'])->save();
        $unloadsBefore = RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'unload')->count();
        app(DispatchShipmentAction::class)->execute($shipment);

        Assert::assertNull($shipment->refresh()->fir_id);
        Assert::assertSame($unloadsBefore, RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'unload')->count());
        Assert::assertSame('DC-2026-15', $eow->refresh()->subject?->getAttribute('eow_declaration_number'));
    });
});
