<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Modules\Labeling\Models\Identifier;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\RegisterEntry;
use Modules\Warehouse\Models\StockUnit;
use Modules\Weighing\Models\Weighing;
use Spatie\QueueableAction\QueueableAction;

/**
 * Transazione sulle connessioni dei moduli toccati dall'orchestrazione
 * (Roosko, Warehouse, Labeling, Weighing, Processing, Waste).
 *
 * Ogni modulo ha una connessione con nome proprio. Il modulo Tenant registra i nomi clonati
 * dalla connessione di default come alias della STESSA istanza `Connection` (DB::extend):
 * quando i moduli condividono il database c'e' una sola istanza, quindi una sola transazione
 * vera e le eventuali interne sono savepoint. L'atomicita' dipende da quell'alias, non dai
 * nomi: per questo le connessioni si deduplicano per istanza (spl_object_id), non per nome.
 *
 * Con database davvero separati (istanze diverse) si apre una transazione per istanza, una
 * dentro l'altra: un errore prima del commit annulla tutto, un errore durante i commit esterni
 * no (niente 2PC, atomicita' best-effort; la piu' interna, Waste, committa per prima).
 * RENTRI e stampa non entrano: si accodano dopo il commit.
 *
 * La transazione piu' esterna si ritenta fino a ATTEMPTS volte su deadlock o errore di
 * serializzazione (Connection::transaction ritenta solo al livello 1): le actions annidate (es.
 * numerazione dei ticket di pesata con upsert) non possono ritentare dentro una transazione
 * aperta da qui. Il callback deve quindi essere rieseguibile: tutto cio' che scrive passa dal
 * database (rollback completo), gli effetti esterni (code, RENTRI) stanno fuori.
 */
class RunInPlantTransactionAction
{
    use QueueableAction;

    public const int ATTEMPTS = 3;

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function execute(Closure $callback): mixed
    {
        /** @var array<int, Connection> $connections istanza => connessione, nell'ordine dei modelli */
        $connections = [];
        foreach ([Delivery::class, StockUnit::class, Identifier::class, Weighing::class, ProcessingOrder::class, RegisterEntry::class] as $modelClass) {
            $connection = DB::connection((new $modelClass)->getConnectionName());
            $connections[spl_object_id($connection)] ??= $connection;
        }

        $ordered = array_values($connections);
        $outermost = array_shift($ordered);

        $wrapped = array_reduce(
            array_reverse($ordered),
            static fn (Closure $inner, Connection $connection): Closure => static fn (): mixed => $connection->transaction($inner),
            $callback,
        );
        if ($outermost instanceof Connection) {
            $inner = $wrapped;
            $wrapped = static fn (): mixed => $outermost->transaction($inner, $outermost->transactionLevel() === 0 ? self::ATTEMPTS : 1);
        }

        /** @var TReturn */
        return $wrapped();
    }
}
