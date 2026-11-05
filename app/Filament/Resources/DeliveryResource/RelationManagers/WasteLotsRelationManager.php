<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Modules\Roosko\Filament\Resources\WasteLotResource;
use Modules\Roosko\Models\WasteLot;
use Modules\Xot\Filament\Resources\RelationManagers\XotBaseRelationManager;

/**
 * Lotti (e unita') nati dall'accettazione del conferimento. Sola lettura.
 */
class WasteLotsRelationManager extends XotBaseRelationManager
{
    protected static string $relationship = 'wasteLots';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * @return array<string, Column>
     */
    #[\Override]
    public function getTableColumns(): array
    {
        return [
            'unit_code' => TextColumn::make('unit_code')->state(static fn (WasteLot $record): ?string => $record->stockUnit?->code),
            'unit_status' => TextColumn::make('unit_status')->badge()->state(static fn (WasteLot $record): mixed => $record->stockUnit?->status),
            'net_weight_kg' => TextColumn::make('net_weight_kg')->numeric(3)->state(static fn (WasteLot $record): ?string => $record->stockUnit?->net_weight_kg),
            'location' => TextColumn::make('location')->state(static fn (WasteLot $record): ?string => $record->stockUnit?->location?->code),
            'classification' => TextColumn::make('classification')->badge(),
            'destination' => TextColumn::make('destination')->badge(),
        ];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableActions(): array
    {
        return [
            'view' => ViewAction::make()->url(static fn (WasteLot $record): string => WasteLotResource::getUrl('view', ['record' => $record])),
        ];
    }

    /**
     * @return array<string, never>
     */
    #[\Override]
    public function getTableBulkActions(): array
    {
        return [];
    }
}
