<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\AreaWasteRuleResource\Tables;

use Modules\Waste\Models\AuthorizedWaste;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Waste\Models\EwcCode;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class AreaWasteRulesTable extends XotBaseResourceTable
{
    /**
     * Area, codice autorizzato ed EER in eager loading (audit R13).
     */
    #[\Override]
    public function table(Table $table): Table
    {
        return parent::table($table)->modifyQueryUsing(static fn (Builder $query): Builder => $query->with(['area', 'authorizedWaste.ewcCode']));
    }

    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'area' => TextColumn::make('area.code')->sortable(),
            'ewc_code' => TextColumn::make('ewc_code')->state(static fn (AreaWasteRule $record): ?string => self::ewc($record)),
            'operation' => TextColumn::make('operation')->badge(),
            'max_storage_kg' => TextColumn::make('max_storage_kg')->numeric(3),
            'authorized_max_storage_kg' => TextColumn::make('authorized_max_storage_kg')->numeric(3)
                ->state(static fn (AreaWasteRule $record): ?string => $record->authorizedWaste?->max_storage_kg),
            'notes' => TextColumn::make('notes')->limit(40)->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return array<string, EditAction|DeleteAction>
     */
    public function getTableActions(): array
    {
        return [
            'edit' => EditAction::make(),
            'delete' => DeleteAction::make(),
        ];
    }

    /**
     * @return array<string, DeleteBulkAction>
     */
    public function getTableBulkActions(): array
    {
        return [
            'delete' => DeleteBulkAction::make(),
        ];
    }

    private static function ewc(AreaWasteRule $rule): ?string
    {
        // Relazioni caricate in eager loading; un codice autorizzato eliminato lascia la cella vuota.
        $authorized = $rule->getRelationValue('authorizedWaste');
        $ewc = $authorized instanceof AuthorizedWaste ? $authorized->getRelationValue('ewcCode') : null;

        return $ewc instanceof EwcCode ? $ewc->code.($ewc->is_hazardous ? '*' : '') : null;
    }
}
