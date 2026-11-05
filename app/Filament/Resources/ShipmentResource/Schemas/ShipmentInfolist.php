<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\Roosko\Models\Shipment;
use Modules\Waste\Models\Fir;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

class ShipmentInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'shipment' => Section::make('shipment')->schema([
                'grid' => Grid::make(4)->schema([
                    'code' => TextEntry::make('code')->copyable(),
                    'status' => TextEntry::make('status')->badge(),
                    'document_type' => TextEntry::make('document_type')->badge(),
                    'document_number' => TextEntry::make('document_number'),
                    'recipient' => TextEntry::make('recipient.business_name'),
                    'recipient_site' => TextEntry::make('recipientSite.name'),
                    'operation' => TextEntry::make('operation')->badge(),
                    'fir_number' => TextEntry::make('fir_number')->state(static fn (Shipment $record): ?string => $record->fir_id === null ? null : Fir::query()->find($record->fir_id)?->number),
                    'total_kg' => TextEntry::make('total_kg')->state(static fn (Shipment $record): float => $record->totalKg())->numeric(3)->suffix(' kg'),
                    'dispatched_at' => TextEntry::make('dispatched_at')->dateTime(),
                ]),
                'notes' => TextEntry::make('notes')->columnSpanFull(),
            ]),
        ];
    }
}
