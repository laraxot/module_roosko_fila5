<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Models\EwcCode;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class WasteLotsTable extends XotBaseResourceTable
{
    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'unit_code' => TextColumn::make('unit_code')->state(static fn (WasteLot $record): ?string => $record->stockUnit?->code),
            'ewc_code' => TextColumn::make('ewc_code')->state(static fn (WasteLot $record): ?string => EwcCode::query()->find($record->ewc_code_id)?->code),
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
