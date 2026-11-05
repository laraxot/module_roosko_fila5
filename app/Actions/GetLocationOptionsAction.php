<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\Location;
use Spatie\QueueableAction\QueueableAction;

/**
 * Posizioni attive di un magazzino per le select (id => "codice — area").
 */
class GetLocationOptionsAction
{
    use QueueableAction;

    /**
     * @return array<int, string>
     */
    public function execute(int $warehouseId): array
    {
        $areas = Area::query()->where('warehouse_id', $warehouseId)->where('is_active', true)->pluck('code', 'id');

        $options = [];
        foreach (Location::query()->whereIn('area_id', $areas->keys()->all())->where('is_active', true)->orderBy('code')->get() as $location) {
            $options[$location->id] = $location->code.' — '.($areas[$location->area_id] ?? '');
        }

        return $options;
    }
}
