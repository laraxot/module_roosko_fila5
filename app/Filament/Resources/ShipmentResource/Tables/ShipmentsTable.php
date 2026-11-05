<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Models\Shipment;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class ShipmentsTable extends XotBaseResourceTable
{
    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'code' => TextColumn::make('code')->searchable()->sortable(),
            'recipient' => TextColumn::make('recipient.business_name'),
            'document_type' => TextColumn::make('document_type')->badge(),
            'document_number' => TextColumn::make('document_number')->searchable(),
            'status' => TextColumn::make('status')->badge()->sortable(),
            'items_count' => TextColumn::make('items_count')->counts('items'),
            'total_kg' => TextColumn::make('total_kg')->numeric(3)->state(static fn (Shipment $record): float => $record->totalKg()),
            'dispatched_at' => TextColumn::make('dispatched_at')->dateTime()->sortable(),
        ];
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getTableFilters(): array
    {
        return [
            'status' => SelectFilter::make('status')->options(ShipmentStatusEnum::class)->multiple(),
            'document_type' => SelectFilter::make('document_type')->options(ShipmentDocumentEnum::class),
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
}
