<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Support\Collection;
use Modules\Processing\Actions\TraceUpstreamAction;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\WasteLot;
<<<<<<< HEAD
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Models\Fir;
=======
use Modules\Waste\Models\Fir;
use Modules\Warehouse\Models\StockUnit;
>>>>>>> laraxot/dev
use Spatie\QueueableAction\QueueableAction;

/**
 * Tracciabilita' a monte di un'unita' in termini di dominio: risale il lineage di Processing
 * (TraceUpstreamAction) e restituisce i lotti conferiti d'origine con conferimento e FIR.
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

        /** @var array<int, float|null> $shares stock_unit_id => quota di massa cumulata */
        $shares = [];
        foreach ($nodes as $node) {
            $shares[$node->stockUnitId] = $node->cumulativeShare;
        }

<<<<<<< HEAD
        /** @var \Illuminate\Database\Eloquent\Collection<int, StockUnit> $units */
=======
>>>>>>> laraxot/dev
        $units = StockUnit::query()->withoutGlobalScopes()->whereIn('id', array_keys($shares))->get();
        $resolver = app(ResolveWasteLotAction::class);

        /** @var Collection<int, OriginRow> $rows */
        $rows = $units
            ->map(static function (StockUnit $upstream) use ($resolver, $shares): ?array {
                $lot = $resolver->find($upstream);
                if (! $lot instanceof WasteLot || $lot->delivery_id === null) {
                    return null;
                }
                $delivery = Delivery::query()->find($lot->delivery_id);

                return [
                    'unit' => $upstream,
                    'lot' => $lot,
                    'delivery' => $delivery,
                    'fir' => $delivery instanceof Delivery ? Fir::query()->find($delivery->fir_id) : null,
                    'share' => $shares[$upstream->id] ?? null,
                ];
            })
            ->filter()
            ->values();

        return $rows;
    }
}
