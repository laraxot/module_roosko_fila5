<?php

declare(strict_types=1);

namespace Modules\Roosko\Guards;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Roosko\Actions\GetLotStockKgAction;
use Modules\Roosko\Actions\ResolveWasteLotAction;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Contracts\PlacementGuardContract;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Xot\Actions\Cast\SafeIntCastAction;

/**
 * Un lotto di rifiuto si stocca solo dove l'autorizzazione lo consente.
 *
 * Per una StockUnit con subject WasteLot e la Location di destinazione:
 * 1. l'area deve avere almeno una AreaWasteRule (area autorizzata allo stoccaggio rifiuti);
 * 2. un lotto pericoloso richiede un'area con regole per codici pericolosi (segregazione);
 * 3. ci deve essere una regola per il codice EER del lotto;
 * 4. carico dell'area per quel codice + unita' <= `max_storage_kg` della regola;
 * 5. carico dell'impianto per quel codice autorizzato (tutte le aree mappate sullo stesso
 *    AuthorizedWaste) + unita' <= `authorized_wastes.max_storage_kg`.
 *
 * Le unita' senza lotto Roosko non sono affar suo (null). L'unita' stessa e' esclusa dai
 * carichi: uno spostamento non la conta due volte.
 */
class WasteAuthorizationPlacementGuard implements PlacementGuardContract
{
    public function deny(StockUnit $unit, Location $location): ?string
    {
        $lot = app(ResolveWasteLotAction::class)->find($unit);
        if (! $lot instanceof WasteLot) {
            return null;
        }

        /** @var Collection<int, AreaWasteRule> $rules */
        $rules = AreaWasteRule::query()->where('area_id', $location->area_id)->get();
        if ($rules->isEmpty()) {
            return $this->reason('area_not_authorized', ['location' => $location->code]);
        }

        /** @var \Illuminate\Support\Collection<array-key, AuthorizedWaste> $authorizedWastes */
        // Solo titoli validi oggi; le righe autorizzative si bloccano (dopo area e posizione, gia'
        // bloccate da Warehouse) perche' due posizionamenti concorrenti non superino lo stesso limite.
        $authorizedWastes = AuthorizedWaste::query()
            ->whereIn('id', $rules->pluck('authorized_waste_id')->all())
            ->validOn(CarbonImmutable::today())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $ewc = EwcCode::query()->findOrFail($lot->ewc_code_id);

        if ($lot->isHazardous() && ! $this->areaAcceptsHazardous($authorizedWastes)) {
            return $this->reason('hazardous_not_segregated', ['ewc' => $ewc->code, 'location' => $location->code]);
        }

        $rule = $rules->first(static fn (AreaWasteRule $rule): bool => $authorizedWastes->get($rule->authorized_waste_id)?->ewc_code_id === $lot->ewc_code_id);
        if (! $rule instanceof AreaWasteRule) {
            return $this->reason('ewc_not_allowed', ['ewc' => $ewc->code, 'location' => $location->code]);
        }

        $unitKg = (float) $unit->net_weight_kg;
        $exceptUnitId = $unit->exists ? $unit->id : null;
        $stock = app(GetLotStockKgAction::class);

        $areaLimit = $rule->maxStorageKg();
        if ($areaLimit !== null) {
            $areaKg = $stock->execute($lot->ewc_code_id, [$location->area_id], $exceptUnitId);
            if ($areaKg + $unitKg > $areaLimit + 0.0005) {
                return $this->reason('area_limit_exceeded', $this->limitReplace($ewc->code, $areaKg, $unitKg, $areaLimit));
            }
        }

        /** @var AuthorizedWaste $authorized */
        $authorized = $authorizedWastes->get($rule->authorized_waste_id);
        $plantLimit = $authorized->max_storage_kg === null ? null : (float) $authorized->max_storage_kg;
        if ($plantLimit !== null) {
            $plantAreaIds = array_values(AreaWasteRule::query()->where('authorized_waste_id', $authorized->id)->distinct()->pluck('area_id')
                ->map(static fn (mixed $id): int => SafeIntCastAction::cast($id))->all());
            $plantKg = $stock->execute($lot->ewc_code_id, $plantAreaIds, $exceptUnitId);
            if ($plantKg + $unitKg > $plantLimit + 0.0005) {
                return $this->reason('authorization_limit_exceeded', $this->limitReplace($ewc->code, $plantKg, $unitKg, $plantLimit));
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<array-key, AuthorizedWaste>  $authorizedWastes
     */
    private function areaAcceptsHazardous(\Illuminate\Support\Collection $authorizedWastes): bool
    {
        $ewcIds = $authorizedWastes->pluck('ewc_code_id')->all();

        return EwcCode::query()->whereIn('id', $ewcIds)->where('is_hazardous', true)->exists();
    }

    /**
     * @return array<string, string>
     */
    private function limitReplace(string $ewc, float $currentKg, float $unitKg, float $limitKg): array
    {
        return [
            'ewc' => $ewc,
            'current' => number_format($currentKg, 3, ',', '.'),
            'unit' => number_format($unitKg, 3, ',', '.'),
            'limit' => number_format($limitKg, 3, ',', '.'),
        ];
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function reason(string $key, array $replace): string
    {
        $message = __('roosko::placement_guard.denied.'.$key, $replace);

        return \is_string($message) ? $message : 'roosko::placement_guard.denied.'.$key;
    }
}
