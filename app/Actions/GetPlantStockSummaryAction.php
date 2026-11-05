<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Support\Collection;
use Modules\Roosko\Datas\StockSummaryRowData;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Roosko\Models\WasteLot;
<<<<<<< HEAD
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
=======
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;
>>>>>>> laraxot/dev
use Spatie\QueueableAction\QueueableAction;

/**
 * Giacenza del magazzino per codice EER: kg e unita' presenti (con posizione, stati
 * `pending|stored|reserved`), pericolosita', limite istantaneo autorizzato (somma dei
 * `max_storage_kg` dei codici autorizzati mappati sulle aree del magazzino) e % d'uso.
 *
 * Compaiono anche i codici autorizzati senza giacenza, per vedere lo spazio disponibile.
 */
class GetPlantStockSummaryAction
{
    use QueueableAction;

    /**
     * @return Collection<int, StockSummaryRowData>
     */
    public function execute(Warehouse $warehouse): Collection
    {
        $stockByEwc = $this->stockByEwc($warehouse);
        $limitsByEwc = $this->limitsByEwc($warehouse);

        $ewcIds = array_values(array_unique([...array_keys($stockByEwc), ...array_keys($limitsByEwc)]));
<<<<<<< HEAD

        /** @var \Illuminate\Database\Eloquent\Collection<int, EwcCode> $ewcCodes */
=======
>>>>>>> laraxot/dev
        $ewcCodes = EwcCode::query()->whereIn('id', $ewcIds)->orderBy('code')->get();

        return $ewcCodes
            ->map(static fn (EwcCode $ewc): StockSummaryRowData => StockSummaryRowData::make(
                warehouseId: $warehouse->id,
                ewcCodeId: $ewc->id,
                ewcCode: $ewc->code,
                description: (string) $ewc->description,
                isHazardous: (bool) $ewc->is_hazardous,
                unitsCount: $stockByEwc[$ewc->id]['units'] ?? 0,
                stockKg: $stockByEwc[$ewc->id]['kg'] ?? 0.0,
                authorizedMaxKg: $limitsByEwc[$ewc->id] ?? null,
            ))
            ->values();
    }

    /**
     * @return array<int, array{units: int, kg: float}>
     */
    private function stockByEwc(Warehouse $warehouse): array
    {
<<<<<<< HEAD
        /** @var \Illuminate\Database\Eloquent\Collection<int, StockUnit> $units */
=======
>>>>>>> laraxot/dev
        $units = StockUnit::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('subject_type', (new WasteLot)->getMorphClass())
            ->whereNotNull('location_id')
            ->whereIn('status', GetLotStockKgAction::occupyingStatuses())
            ->get(['id', 'subject_id', 'net_weight_kg']);

<<<<<<< HEAD
        /** @var Collection<int, int> $lotEwc */
=======
>>>>>>> laraxot/dev
        $lotEwc = WasteLot::query()
            ->whereIn('id', $units->pluck('subject_id')->all())
            ->pluck('ewc_code_id', 'id');

        $result = [];
        foreach ($units as $unit) {
<<<<<<< HEAD
            $subjectId = $unit->subject_id;
            if ($subjectId === null) {
                continue;
            }
            $ewcId = $lotEwc->get($subjectId) ?? 0;
=======
            $ewcId = (int) ($lotEwc[$unit->subject_id] ?? 0);
>>>>>>> laraxot/dev
            if ($ewcId === 0) {
                continue;
            }
            $result[$ewcId] ??= ['units' => 0, 'kg' => 0.0];
            $result[$ewcId]['units']++;
            $result[$ewcId]['kg'] += (float) $unit->net_weight_kg;
        }

        return $result;
    }

    /**
     * @return array<int, float|null>
     */
    private function limitsByEwc(Warehouse $warehouse): array
    {
        $areaIds = Area::query()->where('warehouse_id', $warehouse->id)->pluck('id')->all();
        $authorizedIds = AreaWasteRule::query()->whereIn('area_id', $areaIds)->distinct()->pluck('authorized_waste_id')->all();

<<<<<<< HEAD
        /** @var \Illuminate\Database\Eloquent\Collection<int, AuthorizedWaste> $authorizedWastes */
        $authorizedWastes = AuthorizedWaste::query()->whereIn('id', $authorizedIds)->get();

        $result = [];
        foreach ($authorizedWastes as $authorized) {
            $ewcId = $authorized->ewc_code_id;
=======
        $result = [];
        foreach (AuthorizedWaste::query()->whereIn('id', $authorizedIds)->get() as $authorized) {
            $ewcId = (int) $authorized->ewc_code_id;
>>>>>>> laraxot/dev
            $limit = $authorized->max_storage_kg === null ? null : (float) $authorized->max_storage_kg;
            if (! \array_key_exists($ewcId, $result)) {
                $result[$ewcId] = $limit;

                continue;
            }
            $result[$ewcId] = $result[$ewcId] === null || $limit === null ? null : $result[$ewcId] + $limit;
        }

        return $result;
    }
}
