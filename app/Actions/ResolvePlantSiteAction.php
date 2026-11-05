<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Exceptions\AuthorizationException;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\PlantAuthorization;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Spatie\QueueableAction\QueueableAction;

/**
 * Sito d'impianto (OperatorSite di Waste) a cui appartiene un magazzino.
 *
 * Nessun modulo lega un Warehouse a un sito: il legame e' l'autorizzazione. Le aree del
 * magazzino hanno regole (AreaWasteRule) su codici autorizzati (AuthorizedWaste) di
 * un'autorizzazione (PlantAuthorization) di un sito. Un magazzino deve ricondurre a UN sito.
 */
class ResolvePlantSiteAction
{
    use QueueableAction;

    public function execute(Warehouse $warehouse): OperatorSite
    {
        $areaIds = Area::query()->where('warehouse_id', $warehouse->id)->pluck('id')->all();
        $authorizedWasteIds = AreaWasteRule::query()->whereIn('area_id', $areaIds)->distinct()->pluck('authorized_waste_id')->all();
        $authorizationIds = AuthorizedWaste::query()->whereIn('id', $authorizedWasteIds)->distinct()->pluck('plant_authorization_id')->all();
        $siteIds = array_values(PlantAuthorization::query()->whereIn('id', $authorizationIds)->distinct()->pluck('operator_site_id')->all());

        if ($siteIds === []) {
            throw AuthorizationException::plantSiteNotResolved($warehouse->code);
        }
        if (\count($siteIds) > 1) {
            throw AuthorizationException::plantSiteAmbiguous($warehouse->code);
        }

        return OperatorSite::query()->findOrFail(SafeIntCastAction::cast($siteIds[0]));
    }
}
