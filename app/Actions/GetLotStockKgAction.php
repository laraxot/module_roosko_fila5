<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Builder;
=======
>>>>>>> laraxot/dev
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

<<<<<<< HEAD
        /** @var Builder<StockUnit> $query */
        $query = StockUnit::query()
            ->where('subject_type', (new WasteLot)->getMorphClass())
            ->whereIn('subject_id', $lotIds)
            ->whereIn('location_id', $locationIds)
            ->whereIn('status', self::occupyingStatuses());

        if ($exceptUnitId !== null) {
            $query->whereKeyNot($exceptUnitId);
        }

        $sum = $query->sum('net_weight_kg');

        return round(is_numeric($sum) ? (float) $sum : 0.0, 3);
=======
        $sum = StockUnit::query()
            ->where('subject_type', (new WasteLot)->getMorphClass())
            ->whereIn('subject_id', $lotIds)
            ->whereIn('location_id', $locationIds)
            ->whereIn('status', self::occupyingStatuses())
            ->when($exceptUnitId !== null, static fn ($query) => $query->whereKeyNot($exceptUnitId))
            ->sum('net_weight_kg');

        return round((float) $sum, 3);
>>>>>>> laraxot/dev
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
