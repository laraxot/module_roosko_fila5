<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Waste\Models\Operator;
use Spatie\QueueableAction\QueueableAction;

/**
 * Soggetti Waste (`Operator`) di un cliente dell'area clienti: l'unico punto in cui si decide
 * che cosa appartiene al cliente. Cantieri, FIR e conferimenti del pannello `roosko::customer`
 * si filtrano tutti da questi id (`operators.tenant_id` e' l'unica fonte del legame).
 *
 * Senza cliente (`null`) restituisce una lista vuota: le query che la usano non vedono nulla.
 */
class GetCustomerOperatorIdsAction
{
    use QueueableAction;

    /**
     * @return list<int>
     */
    public function execute(?Model $tenant): array
    {
        if (! $tenant instanceof Model) {
            return [];
        }

        $ids = Operator::query()->ofTenant($tenant)->orderBy('id')->pluck('id')->all();

        return array_values(array_map(intval(...), $ids));
    }
}
