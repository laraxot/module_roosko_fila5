<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Toglie una riga da un'uscita ancora aperta e libera l'unita' (`reserved` → `stored`).
 * Lock dell'uscita: non si toglie una riga mentre l'uscita viene spedita.
 */
class RemoveShipmentItemAction
{
    use QueueableAction;

    public function execute(ShipmentItem $item): void
    {
        app(RunInPlantTransactionAction::class)->execute(static function () use ($item): void {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($item->shipment_id);
            if (! $shipment->status->acceptsItems()) {
                throw ShipmentException::closed($shipment->code);
            }

            $unit = StockUnit::query()->find($item->stock_unit_id);
            $item->delete();
            if ($unit instanceof StockUnit) {
                app(ReserveShipmentUnitAction::class)->execute($unit, reserve: false);
            }
        });
    }
}
