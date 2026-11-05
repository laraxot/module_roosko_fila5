<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class DeliveriesTable extends XotBaseResourceTable
{
    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'code' => TextColumn::make('code')->searchable()->sortable(),
            'arrived_at' => TextColumn::make('arrived_at')->dateTime()->sortable(),
            'fir_number' => TextColumn::make('fir_number')->state(static fn (Delivery $record): ?string => Fir::query()->find($record->fir_id)?->number),
            'status' => TextColumn::make('status')->badge()->sortable(),
            'accepted_kg' => TextColumn::make('accepted_kg')->numeric(3)->sortable(),
            'rejected_kg' => TextColumn::make('rejected_kg')->numeric(3)->toggleable(isToggledHiddenByDefault: true),
            'analysis_required' => IconColumn::make('analysis_required')->boolean(),
        ];
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getTableFilters(): array
    {
        return [
            'status' => SelectFilter::make('status')->options(DeliveryStatusEnum::class)->multiple(),
            'analysis_required' => TernaryFilter::make('analysis_required'),
        ];
    }

    /**
     * @return array<string, ViewAction>
     */
    public function getTableActions(): array
    {
        return [
            'view' => ViewAction::make(),
        ];
    }

    /**
     * @return array<string, never>
     */
    public function getTableBulkActions(): array
    {
        return [];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'arrived_at';
    }
}
