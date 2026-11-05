<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

<<<<<<< HEAD
use Illuminate\Support\Collection;
=======
>>>>>>> laraxot/dev
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
<<<<<<< HEAD
        /** @var Collection<int, string> $areas */
        $areas = Area::query()->where('warehouse_id', $warehouseId)->where('is_active', true)->pluck('code', 'id');

        /** @var \Illuminate\Database\Eloquent\Collection<int, Location> $locations */
        $locations = Location::query()->whereIn('area_id', $areas->keys()->all())->where('is_active', true)->orderBy('code')->get();

        $options = [];
        foreach ($locations as $location) {
            $options[$location->id] = $location->code.' — '.($areas->get($location->area_id) ?? '');
=======
        $areas = Area::query()->where('warehouse_id', $warehouseId)->where('is_active', true)->pluck('code', 'id');

        $options = [];
        foreach (Location::query()->whereIn('area_id', $areas->keys()->all())->where('is_active', true)->orderBy('code')->get() as $location) {
            $options[$location->id] = $location->code.' — '.($areas[$location->area_id] ?? '');
>>>>>>> laraxot/dev
        }

        return $options;
    }
}
