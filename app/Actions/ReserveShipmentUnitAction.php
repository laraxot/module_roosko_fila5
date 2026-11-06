<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Warehouse\Actions\LockStockUnitAction;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Impegna (`reserved`) o libera (`stored`) un'unita' caricata su un'uscita, sotto lock.
 *
 * Un'unita' riservata resta nella sua posizione e continua a occupare capacita', ma non si
 * tratta, non si sposta e non si carica su un'altra uscita (vedi AssertUnitNotReservedAction).
 * Da chiamare dentro la transazione dell'impianto.
 */
class ReserveShipmentUnitAction
{
    use QueueableAction;

    public function execute(StockUnit $unit, bool $reserve = true): StockUnit
    {
        $target = $reserve ? StockUnitStatusEnum::RESERVED : StockUnitStatusEnum::STORED;
        $locked = app(LockStockUnitAction::class)->execute($unit);

        if (! $reserve && $locked->status !== StockUnitStatusEnum::RESERVED) {
            // Gia' libera (o uscita in modo definitivo): niente da rilasciare.
            $unit->syncFrom($locked);

            return $unit;
        }

        Assert::true($locked->status->canTransitionTo($target), $locked->transitionDeniedMessage($target));
        $locked->status = $target;
        $locked->save();
        $unit->syncFrom($locked);

        return $unit;
    }
}
