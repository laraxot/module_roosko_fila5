<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Roosko\Actions\AddShipmentItemAction;
use Modules\Roosko\Actions\CancelShipmentAction;
use Modules\Roosko\Actions\CreateShipmentAction;
use Modules\Roosko\Actions\DispatchShipmentAction;
use Modules\Roosko\Actions\RemoveShipmentItemAction;
use Modules\Roosko\Actions\StartTreatmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Actions\AdjustStockUnitWeightAction;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\StockMovement;
use PHPUnit\Framework\Assert;

/*
 * Audit database R6/R7 e funzionale "unita' non riservata": l'unita' caricata su un'uscita e'
 * `reserved`, torna `stored` se tolta o se l'uscita e' annullata, e la spedizione usa i kg
 * attuali in modo coerente fra movimento, FIR e registro.
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

function wasteShipment(PlantFixture $plant): Shipment
{
    return app(CreateShipmentAction::class)->execute($plant->warehouse, $plant->recipient, ShipmentDocumentEnum::FIR, WasteOperationEnum::R13);
}

test('caricata → reserved; tolta → stored', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $scrap = storedScrap($plant);
    $shipment = wasteShipment($plant);

    $item = app(AddShipmentItemAction::class)->execute($shipment, $scrap);
    Assert::assertSame(StockUnitStatusEnum::RESERVED, $scrap->refresh()->status);
    Assert::assertSame(ShipmentStatusEnum::LOADING, $shipment->status);

    app(RemoveShipmentItemAction::class)->execute($item);
    Assert::assertSame(StockUnitStatusEnum::STORED, $scrap->refresh()->status);
    Assert::assertSame(0, ShipmentItem::query()->whereKey($item->id)->count());
});

test('uscita annullata: unita\' liberate e stato cancelled', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $scrap = storedScrap($plant);
    $shipment = wasteShipment($plant);
    app(AddShipmentItemAction::class)->execute($shipment, $scrap);

    app(CancelShipmentAction::class)->execute($shipment);

    Assert::assertSame(ShipmentStatusEnum::CANCELLED, $shipment->refresh()->status);
    Assert::assertSame(StockUnitStatusEnum::STORED, $scrap->refresh()->status);

    // Libera: si carica su un'altra uscita.
    app(AddShipmentItemAction::class)->execute(wasteShipment($plant), $scrap);
    Assert::assertSame(StockUnitStatusEnum::RESERVED, $scrap->refresh()->status);
});

test('un\'unita\' riservata non si carica su un\'altra uscita, non si sposta, non si tratta', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $scrap = storedScrap($plant);
    app(AddShipmentItemAction::class)->execute(wasteShipment($plant), $scrap);

    expect(static fn () => app(AddShipmentItemAction::class)->execute(wasteShipment($plant), $scrap->refresh()))
        ->toThrow(ShipmentException::class);
    expect(static fn () => app(StoreLotAction::class)->execute($scrap->refresh(), $plant->storageLocation))
        ->toThrow(LotException::class);
    expect(static fn () => app(StartTreatmentAction::class)->execute($plant->shredder, WasteOperationEnum::R12, collect([$scrap->refresh()])))
        ->toThrow(LotException::class);
});

test('rettifica dopo il carico: movimento, scarico di registro e FIR usano i kg attuali', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $scrap = storedScrap($plant);
    $shipment = wasteShipment($plant);
    app(AddShipmentItemAction::class)->execute($shipment, $scrap);

    app(AdjustStockUnitWeightAction::class)->execute($scrap->refresh(), 700.0, 'Ripesata');
    app(DispatchShipmentAction::class)->execute($shipment);

    $movement = StockMovement::query()->where('stock_unit_id', $scrap->id)->where('type', 'shipment')->firstOrFail();
    $unload = RegisterEntry::query()->where('register_id', $plant->register->id)->where('cause', 'to_third_parties')->orderByDesc('id')->firstOrFail();
    $fir = Fir::query()->findOrFail($shipment->refresh()->fir_id);

    Assert::assertEqualsWithDelta(700.0, $movement->quantityKg(), 0.001);
    Assert::assertEqualsWithDelta(700.0, (float) $unload->quantity_kg, 0.001);
    Assert::assertEqualsWithDelta(700.0, (float) $fir->quantity, 0.001);
    Assert::assertEqualsWithDelta(700.0, $shipment->totalKg(), 0.001);
});

test('istanza stantia: nessuna riga aggiunta a un\'uscita gia\' spedita', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $shipment = wasteShipment($plant);
    app(AddShipmentItemAction::class)->execute($shipment, storedScrap($plant));
    $stale = Shipment::query()->findOrFail($shipment->id);
    app(DispatchShipmentAction::class)->execute($shipment);

    $other = storedScrap($plant);
    expect(static fn () => app(AddShipmentItemAction::class)->execute($stale, $other))->toThrow(ShipmentException::class);
    Assert::assertSame(StockUnitStatusEnum::STORED, $other->refresh()->status);
});
