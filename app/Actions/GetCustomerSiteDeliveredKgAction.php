<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonInterface;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * Kg accettati per cantiere: somma di `deliveries.accepted_kg` dei conferimenti il cui FIR ha
 * quel cantiere come luogo di produzione (`firs.producer_site_id`).
 *
 * FIR (connessione `waste`) e conferimenti (connessione `roosko`) possono stare su database
 * diversi: due query, niente join tra moduli. Gli id dei cantieri arrivano gia' filtrati per
 * cliente (query delle resource del pannello cliente).
 */
class GetCustomerSiteDeliveredKgAction
{
    use QueueableAction;

    /**
     * @param  list<int>  $siteIds
     * @return array<int, float> kg per id del cantiere (0 se nessun conferimento)
     */
    public function execute(array $siteIds, ?CarbonInterface $since = null): array
    {
        /** @var array<int, float> $totals */
        $totals = array_fill_keys($siteIds, 0.0);
        if ($siteIds === []) {
            return $totals;
        }

        /** @var array<int, int> $siteByFir id FIR => id cantiere */
        $siteByFir = [];
        foreach (Fir::query()->whereIn('producer_site_id', $siteIds)->get(['id', 'producer_site_id']) as $fir) {
            if ($fir->producer_site_id !== null) {
                $siteByFir[$fir->id] = $fir->producer_site_id;
            }
        }
        if ($siteByFir === []) {
            return $totals;
        }

        $query = Delivery::query()->whereIn('fir_id', array_keys($siteByFir))->whereNotNull('accepted_kg');
        if ($since instanceof CarbonInterface) {
            $query->where('arrived_at', '>=', $since);
        }

        foreach ($query->get(['fir_id', 'accepted_kg']) as $delivery) {
            $siteId = $siteByFir[$delivery->fir_id] ?? null;
            if ($siteId !== null) {
                $totals[$siteId] = ($totals[$siteId] ?? 0.0) + ($delivery->acceptedKg() ?? 0.0);
            }
        }

        return $totals;
    }
}
