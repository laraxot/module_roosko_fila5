<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Support\Collection;
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
        /** @var Collection<int, string> $areas */
        $areas = Area::query()->where('warehouse_id', $warehouseId)->where('is_active', true)->pluck('code', 'id');

        /** @var \Illuminate\Database\Eloquent\Collection<int, Location> $locations */
        $locations = Location::query()->whereIn('area_id', $areas->keys()->all())->where('is_active', true)->orderBy('code')->get();

        $options = [];
        foreach ($locations as $location) {
            $options[$location->id] = $location->code.' — '.($areas->get($location->area_id) ?? '');
        }

        return $options;
    }
}
