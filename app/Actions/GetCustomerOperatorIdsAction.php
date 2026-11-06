<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Waste\Models\Operator;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Spatie\QueueableAction\QueueableAction;

/**
 * Soggetti Waste (`Operator`) di un cliente dell'area clienti: l'unico punto in cui si decide
 * che cosa appartiene al cliente. Cantieri, FIR e conferimenti del pannello `roosko::customer`
 * si filtrano tutti da questi id (`operators.tenant_id` e' l'unica fonte del legame).
 *
 * Senza cliente (`null`) restituisce una lista vuota: le query che la usano non vedono nulla.
 *
 * Memoizzata per richiesta (`once()` su classe e chiave del cliente): lo scope delle resource
 * del pannello cliente la chiama per ogni record (autorizzazione, link, badge), senza memo
 * erano una o piu' query per riga (audit database R12).
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

        return self::forTenant($tenant);
    }

    /**
     * Metodo statico: `once()` memorizza sull'istanza Once della richiesta (non sull'action, che
     * il container ricrea a ogni `app()`), con chiave = istanza del cliente (Filament::getTenant()
     * restituisce la stessa istanza per tutta la richiesta).
     *
     * @return list<int>
     */
    private static function forTenant(Model $tenant): array
    {
        return once(static function () use ($tenant): array {
            $ids = Operator::query()->ofTenant($tenant)->orderBy('id')->pluck('id')->all();

            return array_values(array_map(SafeIntCastAction::cast(...), $ids));
        });
    }
}
