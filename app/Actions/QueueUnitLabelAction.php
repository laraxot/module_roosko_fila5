<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Labeling\Actions\PrintLabelAction;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Accoda la stampa dell'etichetta di un'unita' (template e stampante di default di Labeling).
 * Da chiamare dopo il commit: il job rilegge l'unita' dal database.
 */
class QueueUnitLabelAction
{
    use QueueableAction;

    public function execute(StockUnit $unit, int $copies = 1): void
    {
        app(PrintLabelAction::class)
            ->onQueue(app(GetQueueNameAction::class)->execute('labels'))
            ->execute($unit, null, null, $copies);
    }
}
