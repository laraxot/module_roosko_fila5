<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Kg di un codice EER presenti nelle aree indicate (unita' `pending|stored|reserved` con
 * posizione, stesso criterio di carico di Warehouse) con un lotto Roosko come contenuto.
 *
 * Si parte dalle unita' che occupano le posizioni delle aree (insieme limitato dalla giacenza
 * fisica attuale), non dai lotti dell'EER: i lotti storici (consumati, spediti) crescono senza
 * limite e finivano tutti in un `whereIn` (audit database R5). Poi si tengono i lotti dell'EER
 * fra quelli delle unita' trovate (WhereInModuleQueryAction: subquery con lo stesso database).
 */
class GetLotStockKgAction
{
    use QueueableAction;

    /**
     * @param  list<int>  $areaIds
     */
    public function execute(int $ewcCodeId, array $areaIds, ?int $exceptUnitId = null): float
    {
        if ($areaIds === []) {
            return 0.0;
        }

        $units = StockUnit::query()
            ->where('subject_type', (new WasteLot)->getMorphClass())
            ->whereIn('location_id', Location::query()->select('id')->whereIn('area_id', $areaIds))
            ->whereIn('status', self::occupyingStatuses())
            ->when($exceptUnitId !== null, static fn (Builder $query): Builder => $query->whereKeyNot($exceptUnitId));

        $lots = app(WhereInModuleQueryAction::class)->execute(
            WasteLot::query()->where('ewc_code_id', $ewcCodeId),
            'id',
            $units,
            'subject_id',
        );

        $sum = app(WhereInModuleQueryAction::class)->execute(
            $units,
            'subject_id',
            $lots,
        )->sum('net_weight_kg');

        return round(is_numeric($sum) ? (float) $sum : 0.0, 3);
    }

    /**
     * @return list<string>
     */
    public static function occupyingStatuses(): array
    {
        return array_map(
            static fn (StockUnitStatusEnum $status): string => $status->value,
            StockUnitStatusEnum::occupying(),
        );
    }
}
