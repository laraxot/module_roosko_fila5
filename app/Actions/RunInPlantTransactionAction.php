<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Closure;
use Illuminate\Support\Facades\DB;
use Modules\Labeling\Models\Identifier;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Models\StockUnit;
use Modules\Weighing\Models\Weighing;
use Spatie\QueueableAction\QueueableAction;

/**
 * Transazione annidata sulle connessioni dei moduli toccati dall'orchestrazione
 * (Roosko, Warehouse, Labeling, Weighing, Processing, Waste).
 *
 * Una connessione per modulo: si apre una transazione per ogni connessione DISTINTA, una
 * dentro l'altra. Con lo stesso database (caso tipico) le interne diventano savepoint; con
 * database diversi l'atomicita' e' best-effort (niente 2PC): un errore prima del commit
 * annulla tutto, un errore durante i commit esterni no. RENTRI e stampa non entrano:
 * si accodano dopo il commit.
 */
class RunInPlantTransactionAction
{
    use QueueableAction;

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function execute(Closure $callback): mixed
    {
        $connections = array_values(array_unique(array_map(
            static fn (string $modelClass): string => (new $modelClass)->getConnectionName() ?? DB::getDefaultConnection(),
            [Delivery::class, StockUnit::class, Identifier::class, Weighing::class, ProcessingOrder::class, RegisterEntry::class],
        )));

        $wrapped = array_reduce(
            array_reverse($connections),
            static fn (Closure $inner, string $connection): Closure => static fn (): mixed => DB::connection($connection)->transaction($inner),
            $callback,
        );

        /** @var TReturn */
        return $wrapped();
    }
}
