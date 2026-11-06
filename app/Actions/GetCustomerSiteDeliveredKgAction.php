<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
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
 * Con i moduli sullo stesso database (stessa istanza di connessione, alias del modulo Tenant)
 * e' UNA query aggregata (join conferimenti → FIR → cantieri, `SUM ... GROUP BY` cantiere), per
 * tutti i cantieri chiesti insieme: le tabelle la chiamano una volta per pagina, non per riga
 * (audit database R12). Con database separati: query separate per connessione, somma nel database
 * per FIR. Gli id dei cantieri arrivano gia' filtrati per cliente; somme arrotondate ai grammi.
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

        $connection = (new Delivery)->getConnection();
        if ($connection === (new Fir)->getConnection() && $connection === (new OperatorSite)->getConnection()) {
            return $this->singleQuery($totals, $siteIds, $since);
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
                $totals[$siteId] = round($totals[$siteId] + (float) $kg, 3);
            }
        }

        return $totals;
    }

    /**
     * @param  array<int, float>  $totals
     * @param  list<int>  $siteIds
     * @return array<int, float>
     */
    private function singleQuery(array $totals, array $siteIds, ?CarbonInterface $since): array
    {
        $deliveries = (new Delivery)->getTable();
        $firs = (new Fir)->getTable();
        $sites = (new OperatorSite)->getTable();

        $rows = Delivery::query()
            ->join($firs, $firs.'.id', '=', $deliveries.'.fir_id')
            ->join($sites, static fn (JoinClause $join): JoinClause => $join
                ->on($sites.'.id', '=', $firs.'.producer_site_id')
                ->on($sites.'.operator_id', '=', $firs.'.producer_operator_id'))
            ->whereIn($sites.'.id', $siteIds)
            ->whereNotNull($deliveries.'.accepted_kg')
            ->when($since instanceof CarbonInterface, static fn (Builder $query): Builder => $query->where($deliveries.'.arrived_at', '>=', $since))
            ->groupBy($sites.'.id')
            ->select($sites.'.id as site_id')
            // `accepted_kg` esiste solo in deliveries: non ambiguo nella join.
            ->selectRaw('SUM(accepted_kg) as kg')
            ->toBase()
            ->pluck('kg', 'site_id');

        foreach ($rows as $siteId => $kg) {
            if (is_numeric($kg) && \array_key_exists((int) $siteId, $totals)) {
                $totals[(int) $siteId] = round((float) $kg, 3);
            }
        }

        return $totals;
    }
}
