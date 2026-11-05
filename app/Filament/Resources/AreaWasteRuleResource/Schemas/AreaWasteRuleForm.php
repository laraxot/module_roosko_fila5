<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\AreaWasteRuleResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
<<<<<<< HEAD
use Illuminate\Support\Collection;
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\Warehouse;
=======
>>>>>>> laraxot/dev
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\PlantAuthorization;
<<<<<<< HEAD
=======
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\Warehouse;
>>>>>>> laraxot/dev
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

class AreaWasteRuleForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [
            'area_id' => Select::make('area_id')->options(static fn (): array => self::areaOptions())->searchable()->required(),
            'authorized_waste_id' => Select::make('authorized_waste_id')->options(static fn (): array => self::authorizedWasteOptions())->searchable()->required(),
            'operation' => Select::make('operation')
                ->options(static fn (): array => self::storageOperationOptions())
                ->required(),
            'max_storage_kg' => TextInput::make('max_storage_kg')->numeric()->minValue(0)->step(0.001)->suffix('kg'),
            'notes' => Textarea::make('notes')->rows(2)->columnSpanFull(),
        ];
    }

    /**
     * Solo messa in riserva / deposito preliminare (R13, D15): le aree di stoccaggio.
     *
     * @return array<string, string>
     */
    private static function storageOperationOptions(): array
    {
        $options = [];
        foreach (WasteOperationEnum::cases() as $operation) {
            if ($operation->isPreliminaryStorage()) {
                $options[$operation->value] = $operation->value.' — '.$operation->getLabel();
            }
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private static function areaOptions(): array
    {
<<<<<<< HEAD
        /** @var Collection<int, string> $warehouses */
        $warehouses = Warehouse::query()->pluck('code', 'id');

        /** @var \Illuminate\Database\Eloquent\Collection<int, Area> $areas */
        $areas = Area::query()->where('is_active', true)->orderBy('warehouse_id')->orderBy('code')->get();

        $options = [];
        foreach ($areas as $area) {
            $options[$area->id] = ($warehouses->get($area->warehouse_id) ?? '?').' / '.$area->code.' — '.$area->name;
=======
        $warehouses = Warehouse::query()->pluck('code', 'id');
        $options = [];
        foreach (Area::query()->where('is_active', true)->orderBy('warehouse_id')->orderBy('code')->get() as $area) {
            $options[$area->id] = ($warehouses[$area->warehouse_id] ?? '?').' / '.$area->code.' — '.$area->name;
>>>>>>> laraxot/dev
        }

        return $options;
    }

    /**
     * Codici autorizzati delle autorizzazioni attive: id => "EER — autorizzazione".
     *
     * @return array<int, string>
     */
    private static function authorizedWasteOptions(): array
    {
<<<<<<< HEAD
        /** @var Collection<int, string> $authorizations */
        $authorizations = PlantAuthorization::query()->where('is_active', true)->pluck('number', 'id');

        /** @var \Illuminate\Database\Eloquent\Collection<int, AuthorizedWaste> $authorized */
        $authorized = AuthorizedWaste::query()->whereIn('plant_authorization_id', $authorizations->keys()->all())->get();

        /** @var Collection<int, EwcCode> $ewc */
=======
        $authorizations = PlantAuthorization::query()->where('is_active', true)->pluck('number', 'id');
        $authorized = AuthorizedWaste::query()->whereIn('plant_authorization_id', $authorizations->keys()->all())->get();
>>>>>>> laraxot/dev
        $ewc = EwcCode::query()->whereIn('id', $authorized->pluck('ewc_code_id')->all())->get()->keyBy('id');

        $options = [];
        foreach ($authorized as $item) {
            $code = $ewc->get($item->ewc_code_id);
            $options[$item->id] = ($code instanceof EwcCode ? $code->code.($code->is_hazardous ? '*' : '') : '?')
<<<<<<< HEAD
                .' — '.($authorizations->get($item->plant_authorization_id) ?? '');
=======
                .' — '.($authorizations[$item->plant_authorization_id] ?? '');
>>>>>>> laraxot/dev
        }

        return $options;
    }
}
