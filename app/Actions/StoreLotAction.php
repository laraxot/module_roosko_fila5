<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Exceptions\LotException;
use Modules\Warehouse\Actions\CheckPlacementAction;
use Modules\Warehouse\Actions\PutAwayStockUnitAction;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Stocca (o sposta) l'unita' di un lotto in una posizione: per la UI operatore
 * (scansione unita' + scansione posizione).
 *
 * Il motivo di un eventuale rifiuto dei guard (capacita', autorizzazione) si legge prima con
 * CheckPlacementAction e diventa una LotException con testo per l'operatore; il movimento lo
 * registra PutAwayStockUnitAction di Warehouse.
 */
class StoreLotAction
{
    use QueueableAction;

    public function execute(StockUnit $unit, Location $location): StockUnit
    {
        app(ResolveWasteLotAction::class)->execute($unit);

        $reason = app(CheckPlacementAction::class)->execute($unit, $location);
        if ($reason !== null) {
            throw LotException::placementDenied($unit->code, $location->code, $reason);
        }

        return app(PutAwayStockUnitAction::class)->execute($unit, $location);
    }
}
