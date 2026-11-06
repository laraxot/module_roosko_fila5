<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Tables;

use Modules\Waste\Models\EwcCode;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Models\WasteLot;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class WasteLotsTable extends XotBaseResourceTable
{
    /**
     * Unita' (con posizione) ed EER in eager loading: niente query per riga (audit R13).
     */
    #[\Override]
    public function table(Table $table): Table
    {
        return parent::table($table)->modifyQueryUsing(static fn (Builder $query): Builder => $query->with(['stockUnit.location', 'ewcCode']));
    }

    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'unit_code' => TextColumn::make('unit_code')->state(static fn (WasteLot $record): ?string => $record->stockUnit?->code),
            'ewc_code' => TextColumn::make('ewc_code')->state(static fn (WasteLot $record): ?string => ($ewc = $record->getRelationValue('ewcCode')) instanceof EwcCode ? $ewc->code : null),
            'classification' => TextColumn::make('classification')->badge()->sortable(),
            'destination' => TextColumn::make('destination')->badge()->sortable(),
            'unit_status' => TextColumn::make('unit_status')->badge()->state(static fn (WasteLot $record): mixed => $record->stockUnit?->status),
            'net_weight_kg' => TextColumn::make('net_weight_kg')->numeric(3)->state(static fn (WasteLot $record): ?string => $record->stockUnit?->net_weight_kg),
            'eow_batch_code' => TextColumn::make('eow_batch_code')->searchable()->toggleable(isToggledHiddenByDefault: true),
            'created_at' => TextColumn::make('created_at')->dateTime()->sortable(),
        ];
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getTableFilters(): array
    {
        return [
            'classification' => SelectFilter::make('classification')->options(LotClassificationEnum::class),
            'destination' => SelectFilter::make('destination')->options(LotDestinationEnum::class),
        ];
    }

    /**
     * @return array<string, ViewAction|EditAction>
     */
    public function getTableActions(): array
    {
        return [
            'view' => ViewAction::make(),
            'edit' => EditAction::make(),
        ];
    }

    /**
     * @return array<string, never>
     */
    public function getTableBulkActions(): array
    {
        return [];
    }
}
