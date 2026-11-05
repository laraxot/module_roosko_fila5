<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Seeders;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Warehouse\Enums\AreaTypeEnum;
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\PlantAuthorization;

/**
 * Regole demo: i codici autorizzati dell'impianto (seeder Waste) mappati sulle aree del primo
 * magazzino attivo (seeder Warehouse).
 *
 * - codici non pericolosi → aree `storage`;
 * - codici pericolosi → aree `quarantine` (segregazione), mai in `storage`.
 *
 * Operazione R13 se autorizzata per il codice, altrimenti D15. Idempotente. Se mancano i
 * dati dei seeder prerequisiti avvisa e non fa nulla.
 */
class AreaWasteRuleSeeder extends Seeder
{
    public function run(): void
    {
        $plant = OperatorSite::query()->where('is_plant', true)->where('is_active', true)->orderBy('id')->first();
        $warehouse = Warehouse::query()->where('is_active', true)->orderBy('id')->first();
        if (! $plant instanceof OperatorSite || ! $warehouse instanceof Warehouse) {
            $this->command?->warn('AreaWasteRuleSeeder: impianto o magazzino assenti, eseguire prima i seeder di Waste e Warehouse.');

            return;
        }

        $authorizationIds = PlantAuthorization::query()->where('operator_site_id', $plant->id)->where('is_active', true)->pluck('id')->all();

        /** @var Collection<int, AuthorizedWaste> $authorizedWastes */
        $authorizedWastes = AuthorizedWaste::query()->whereIn('plant_authorization_id', $authorizationIds)->get();

        $storage = Area::query()->where('warehouse_id', $warehouse->id)->where('type', AreaTypeEnum::STORAGE->value)->get();
        $quarantine = Area::query()->where('warehouse_id', $warehouse->id)->where('type', AreaTypeEnum::QUARANTINE->value)->get();

        $created = 0;
        foreach ($authorizedWastes as $authorized) {
            $ewc = EwcCode::query()->find($authorized->ewc_code_id);
            if (! $ewc instanceof EwcCode) {
                continue;
            }
            $operation = $this->storageOperation($authorized);
            if ($operation === null) {
                continue;
            }

            foreach ($ewc->is_hazardous ? $quarantine : $storage as $area) {
                $rule = AreaWasteRule::query()->firstOrCreate(
                    ['area_id' => $area->id, 'authorized_waste_id' => $authorized->id],
                    ['operation' => $operation, 'notes' => 'demo'],
                );
                $created += $rule->wasRecentlyCreated ? 1 : 0;
            }
        }

        $this->command?->info('AreaWasteRuleSeeder: '.$created.' regole create.');
    }

    private function storageOperation(AuthorizedWaste $authorized): ?WasteOperationEnum
    {
        $operations = $authorized->operations;
        $values = [];
        if (is_iterable($operations)) {
            foreach ($operations as $operation) {
                $values[] = $operation instanceof WasteOperationEnum ? $operation->value : (\is_string($operation) ? $operation : '');
            }
        }

        foreach (['R13', 'D15'] as $candidate) {
            if (\in_array($candidate, $values, true)) {
                return WasteOperationEnum::from($candidate);
            }
        }

        return null;
    }
}
