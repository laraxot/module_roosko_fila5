<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Kg di un codice EER presenti nelle aree indicate (unita' `pending|stored|reserved` con
 * posizione, stesso criterio di carico di Warehouse) con un lotto Roosko come contenuto.
 *
 * Due query su connessioni diverse (lotti su `roosko`, unita' su `warehouse`): niente join
 * tra moduli.
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

        $lotIds = WasteLot::query()->where('ewc_code_id', $ewcCodeId)->pluck('id')->all();
        if ($lotIds === []) {
            return 0.0;
        }

        $locationIds = Location::query()->whereIn('area_id', $areaIds)->pluck('id')->all();

        $sum = StockUnit::query()
            ->where('subject_type', (new WasteLot)->getMorphClass())
            ->whereIn('subject_id', $lotIds)
            ->whereIn('location_id', $locationIds)
            ->whereIn('status', self::occupyingStatuses())
            ->when($exceptUnitId !== null, static fn ($query) => $query->whereKeyNot($exceptUnitId))
            ->sum('net_weight_kg');

        return round((float) $sum, 3);
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
