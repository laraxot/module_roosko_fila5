<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Roosko\Models\WasteLot;
<<<<<<< HEAD
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Models\EwcCode;
=======
use Modules\Waste\Models\EwcCode;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\StockUnit;
>>>>>>> laraxot/dev
use Spatie\QueueableAction\QueueableAction;

/**
 * Carica un'unita' su un'uscita, verificando la coerenza:
 * - uscita ancora aperta, unita' in giacenza nello stesso magazzino e non gia' caricata altrove;
 * - documento compatibile con la classificazione del lotto (FIR solo per rifiuti, dichiarazione
 *   EoW solo per End of Waste, DDT per EoW e sottoprodotti);
 * - destino del lotto coerente col documento (FIR: recupero/smaltimento/bonifica; altrimenti vendita);
 * - con FIR, un solo codice EER per uscita (un formulario per codice).
 */
class AddShipmentItemAction
{
    use QueueableAction;

    public function execute(Shipment $shipment, StockUnit $unit): ShipmentItem
    {
        if (! $shipment->status->acceptsItems()) {
            throw ShipmentException::closed($shipment->code);
        }
        if (! \in_array($unit->status, [StockUnitStatusEnum::STORED, StockUnitStatusEnum::RESERVED], true)) {
            throw ShipmentException::unitNotInStock($unit->code);
        }
        if ($unit->warehouse_id !== $shipment->warehouse_id) {
            throw ShipmentException::otherWarehouse($unit->code);
        }

        $lot = app(ResolveWasteLotAction::class)->execute($unit);
        $this->assertNotLoadedElsewhere($unit);
        $this->assertCoherent($shipment, $unit, $lot);

        $connection = (new ShipmentItem)->getConnectionName() ?? DB::getDefaultConnection();

        return DB::connection($connection)->transaction(static function () use ($shipment, $unit): ShipmentItem {
            $item = ShipmentItem::query()->create([
                'shipment_id' => $shipment->id,
                'stock_unit_id' => $unit->id,
                'quantity_kg' => (float) $unit->net_weight_kg,
            ]);

            if ($shipment->status === ShipmentStatusEnum::DRAFT) {
                $shipment->forceFill(['status' => ShipmentStatusEnum::LOADING])->save();
            }

            return $item;
        });
    }

    private function assertNotLoadedElsewhere(StockUnit $unit): void
    {
<<<<<<< HEAD
        $existing = ShipmentItem::query()
            ->where('stock_unit_id', $unit->id)
            ->whereHas('shipment', static function ($query): void {
                $query->whereNotIn('status', [ShipmentStatusEnum::CANCELLED->value]);
            })
=======
        $openShipmentIds = Shipment::query()
            ->whereNotIn('status', [ShipmentStatusEnum::CANCELLED->value])
            ->pluck('id')
            ->all();

        $existing = ShipmentItem::query()
            ->where('stock_unit_id', $unit->id)
            ->whereIn('shipment_id', $openShipmentIds)
>>>>>>> laraxot/dev
            ->first();

        if ($existing instanceof ShipmentItem) {
            throw ShipmentException::alreadyLoaded($unit->code, $existing->shipment->code);
        }
    }

    private function assertCoherent(Shipment $shipment, StockUnit $unit, WasteLot $lot): void
    {
        $document = $shipment->document_type;
        if (! $document->accepts($lot->classification)) {
            throw ShipmentException::documentMismatch($unit->code, $lot->classification->getLabel(), $document->getLabel());
        }

        $expected = self::destinationsFor($document);
        if (! \in_array($lot->destination, $expected, true)) {
            throw ShipmentException::destinationMismatch(
                $unit->code,
                $lot->destination->getLabel(),
                implode(', ', array_map(static fn (LotDestinationEnum $destination): string => $destination->getLabel(), $expected)),
            );
        }

        if (! $document->carriesWaste()) {
            return;
        }

        $firstLot = $this->firstLot($shipment);
        if ($firstLot instanceof WasteLot && $firstLot->ewc_code_id !== $lot->ewc_code_id) {
            throw ShipmentException::ewcMismatch(
                $unit->code,
                EwcCode::query()->findOrFail($lot->ewc_code_id)->code,
                EwcCode::query()->findOrFail($firstLot->ewc_code_id)->code,
            );
        }
    }

    /**
     * @return list<LotDestinationEnum>
     */
    public static function destinationsFor(ShipmentDocumentEnum $document): array
    {
        return $document->carriesWaste()
            ? [LotDestinationEnum::EXTERNAL_RECOVERY, LotDestinationEnum::DISPOSAL, LotDestinationEnum::REMEDIATION]
            : [LotDestinationEnum::SALE];
    }

    private function firstLot(Shipment $shipment): ?WasteLot
    {
        $item = $shipment->items()->orderBy('id')->first();
        if (! $item instanceof ShipmentItem) {
            return null;
        }

        $unit = StockUnit::query()->find($item->stock_unit_id);

        return $unit instanceof StockUnit ? app(ResolveWasteLotAction::class)->find($unit) : null;
    }
}
