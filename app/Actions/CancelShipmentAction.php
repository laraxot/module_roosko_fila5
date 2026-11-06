<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Exceptions\RooskoException;
use Modules\Roosko\Models\Shipment;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Annulla un'uscita non ancora spedita: stato `cancelled` e unita' liberate (`reserved` →
 * `stored`). Le righe restano come traccia di cosa era stato caricato.
 */
class CancelShipmentAction
{
    use QueueableAction;

    public function execute(Shipment $shipment): Shipment
    {
        app(RunInPlantTransactionAction::class)->execute(static function () use ($shipment): void {
            $locked = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            Assert::true(
                $locked->status->canTransitionTo(ShipmentStatusEnum::CANCELLED),
                RooskoException::transitionMessage($locked->code, $locked->status, ShipmentStatusEnum::CANCELLED),
            );

            $unitIds = $locked->items()->pluck('stock_unit_id')->all();
            foreach (StockUnit::query()->whereKey($unitIds)->get() as $unit) {
                app(ReserveShipmentUnitAction::class)->execute($unit, reserve: false);
            }

            $locked->forceFill(['status' => ShipmentStatusEnum::CANCELLED])->save();
            $shipment->setRawAttributes($locked->getAttributes(), true);
        });

        return $shipment;
    }
}
