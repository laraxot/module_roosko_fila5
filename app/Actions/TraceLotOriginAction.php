<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Support\Collection;
use Modules\Processing\Actions\TraceUpstreamAction;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;

/**
 * Tracciabilita' a monte di un'unita' in termini di dominio: risale il lineage di Processing
 * (TraceUpstreamAction) e restituisce i lotti conferiti d'origine con conferimento e FIR.
 *
 * Un'unita' raggiungibile da piu' cammini (grafo a diamante) compare in TraceUpstreamAction una
 * volta per cammino: la sua quota e' la SOMMA delle quote dei cammini (audit database R17).
 * Lotti, conferimenti e FIR si caricano in blocco (una query per tipo), non per nodo (R13).
 *
 * @phpstan-type OriginRow array{unit: StockUnit, lot: WasteLot, delivery: Delivery|null, fir: Fir|null, share: float|null}
 */
class TraceLotOriginAction
{
    use QueueableAction;

    /**
     * @return Collection<int, OriginRow>
     */
    public function execute(StockUnit $unit): Collection
    {
        $nodes = app(TraceUpstreamAction::class)->execute($unit);

        /** @var array<int, float> $shares stock_unit_id => quota di massa cumulata (somma dei cammini) */
        $shares = [];
        foreach ($nodes as $node) {
            $shares[$node->stockUnitId] = round(($shares[$node->stockUnitId] ?? 0.0) + $node->cumulativeShare, 6);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, StockUnit> $units */
        $units = StockUnit::query()->withoutGlobalScopes()->whereIn('id', array_keys($shares))->with('subject')->get();
        $resolver = app(ResolveWasteLotAction::class);

        /** @var array<int, WasteLot> $lots stock_unit_id => lotto conferito */
        $lots = [];
        foreach ($units as $upstream) {
            $lot = $resolver->find($upstream);
            if ($lot instanceof WasteLot && $lot->delivery_id !== null) {
                $lots[$upstream->id] = $lot;
            }
        }

        $deliveries = Delivery::query()
            ->whereIn('id', array_values(array_unique(array_map(static fn (WasteLot $lot): int => (int) $lot->delivery_id, $lots))))
            ->get()
            ->keyBy('id');
        $firs = Fir::query()
            ->whereIn('id', $deliveries->pluck('fir_id')->unique()->values()->all())
            ->get()
            ->keyBy('id');

        /** @var Collection<int, OriginRow> $rows */
        $rows = $units
            ->filter(static fn (StockUnit $upstream): bool => isset($lots[$upstream->id]))
            ->map(static function (StockUnit $upstream) use ($lots, $deliveries, $firs, $shares): array {
                $lot = $lots[$upstream->id];
                $delivery = $deliveries->get((int) $lot->delivery_id);

                return [
                    'unit' => $upstream,
                    'lot' => $lot,
                    'delivery' => $delivery instanceof Delivery ? $delivery : null,
                    'fir' => $delivery instanceof Delivery ? $firs->get($delivery->fir_id) : null,
                    'share' => $shares[$upstream->id] ?? null,
                ];
            })
            ->values()
            ->toBase();

        return $rows;
    }
}
