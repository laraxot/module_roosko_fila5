<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Il lotto contenuto in una StockUnit (morph `subject`), o eccezione se l'unita' non e' Roosko.
 */
class ResolveWasteLotAction
{
    use QueueableAction;

    public function execute(StockUnit $unit): WasteLot
    {
        $lot = $this->find($unit);
        if ($lot === null) {
            throw LotException::notAWasteLot($unit->code);
        }

        return $lot;
    }

    /** Come execute(), ma null se l'unita' non contiene un lotto (es. nei guard). */
    public function find(StockUnit $unit): ?WasteLot
    {
        $subject = $unit->subject;

        return $subject instanceof WasteLot ? $subject : null;
    }
}
