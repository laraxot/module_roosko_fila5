<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueueableAction\QueueableAction;

/**
 * `whereIn($column, <id di un altro modulo>)` senza liste di id che crescono senza limite.
 *
 * Se le due query usano la stessa istanza di connessione (moduli sullo stesso database: alias
 * del modulo Tenant) il filtro e' una subquery SQL (`where col in (select ...)`), con un numero
 * fisso di parametri. Solo con database davvero separati si ricade sugli id letti in PHP, perche'
 * una subquery fra connessioni diverse non e' possibile.
 */
class WhereInModuleQueryAction
{
    use QueueableAction;

    /**
     * @template TModel of Model
     * @template TOther of Model
     *
     * @param  Builder<TModel>  $query
     * @param  Builder<TOther>  $ids  query (filtrata) dei record dell'altro modulo
     * @param  string  $idColumn  colonna di `$ids` da confrontare con `$column`
     * @return Builder<TModel>
     */
    public function execute(Builder $query, string $column, Builder $ids, string $idColumn = 'id'): Builder
    {
        $ids = $ids->clone()->toBase()->select($idColumn);

        if ($query->getConnection() === $ids->getConnection()) {
            return $query->whereIn($column, $ids);
        }

        return $query->whereIn($column, $ids->pluck($idColumn)->all());
    }
}
