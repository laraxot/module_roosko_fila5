<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonInterface;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;

/**
 * Kg accettati per cantiere: somma di `deliveries.accepted_kg` dei conferimenti il cui FIR ha
 * quel cantiere come luogo di produzione (`firs.producer_site_id`) **e** come produttore il
 * soggetto titolare del cantiere (`firs.producer_operator_id = operator_sites.operator_id`).
 *
 * La seconda condizione tiene i kg coerenti con l'elenco dei conferimenti del cliente, che
 * filtra per produttore: un FIR con dati incoerenti (cantiere di un'impresa, produttore di
 * un'altra) non entra nei totali di nessuno dei due, invece di attribuire a un cliente i kg
 * conferiti da un'altra impresa.
 *
 * FIR (connessione `waste`) e conferimenti (connessione `roosko`) possono stare su database
 * diversi: query separate, niente join tra moduli; la somma la fa il database (`SUM ... GROUP BY`),
 * non PHP sui singoli conferimenti. Gli id dei cantieri arrivano gia' filtrati per cliente.
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

        /** @var array<int, int> $operatorBySite id cantiere => id soggetto titolare */
        $operatorBySite = [];
        foreach (OperatorSite::query()->whereIn('id', $siteIds)->get(['id', 'operator_id']) as $site) {
            $operatorBySite[(int) $site->id] = (int) $site->operator_id;
        }

        /** @var array<int, int> $siteByFir id FIR => id cantiere */
        $siteByFir = [];
        $firs = Fir::query()
            ->whereIn('producer_site_id', array_keys($operatorBySite))
            ->get(['id', 'producer_site_id', 'producer_operator_id']);
        foreach ($firs as $fir) {
            $siteId = (int) $fir->producer_site_id;
            if (($operatorBySite[$siteId] ?? null) === (int) $fir->producer_operator_id) {
                $siteByFir[(int) $fir->id] = $siteId;
            }
        }
        if ($siteByFir === []) {
            return $totals;
        }

        $query = Delivery::query()
            ->whereIn('fir_id', array_keys($siteByFir))
            ->whereNotNull('accepted_kg')
            ->groupBy('fir_id')
            ->select('fir_id')
            ->selectRaw('SUM(accepted_kg) as kg');
        if ($since instanceof CarbonInterface) {
            $query->where('arrived_at', '>=', $since);
        }

        foreach ($query->toBase()->pluck('kg', 'fir_id') as $firId => $kg) {
            $siteId = $siteByFir[(int) $firId] ?? null;
            if ($siteId !== null && is_numeric($kg)) {
                $totals[$siteId] += (float) $kg;
            }
        }

        return $totals;
    }
}
