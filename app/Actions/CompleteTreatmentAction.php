<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Processing\Actions\CompleteProcessingOrderAction;
use Modules\Processing\Models\ProcessingOrder;
use Spatie\QueueableAction\QueueableAction;

/**
 * Chiude il trattamento: bilancio di massa, lineage e snapshot li calcola Processing.
 * `$force` supera la soglia di perdita (`processing.max_loss_pct`).
 *
 * Nessuna scrittura di registro qui: gli scarichi sono gia' stati scritti all'avvio
 * (StartTreatmentAction) e i carichi a ogni output (RegisterTreatmentOutputAction).
 */
class CompleteTreatmentAction
{
    use QueueableAction;

    public function execute(ProcessingOrder $order, bool $force = false): ProcessingOrder
    {
        return app(CompleteProcessingOrderAction::class)->execute($order, $force);
    }
}
