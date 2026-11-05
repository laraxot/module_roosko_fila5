<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\Roosko\Models\Delivery;
use Modules\Weighing\Models\Weighing;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

/**
 * Dettaglio per il cliente: FIR, cantiere, pesata ed esito. Niente note di ispezione interne,
 * magazzino o lotti dell'impianto.
 */
class CustomerDeliveryInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'delivery' => Section::make('delivery')->schema([
                'grid' => Grid::make(3)->schema([
                    'code' => TextEntry::make('code')->copyable(),
                    'status' => TextEntry::make('status')->badge(),
                    'arrived_at' => TextEntry::make('arrived_at')->dateTime(),
                    'fir_number' => TextEntry::make('fir.number'),
                    'producer_site' => TextEntry::make('fir.producerSite.name'),
                    'ewc_code' => TextEntry::make('fir.ewcCode.display_code'),
                ]),
            ]),
            'weighing' => Section::make('weighing')->schema([
                'grid' => Grid::make(4)->schema([
                    'fir_quantity' => TextEntry::make('fir.quantity')->numeric(3),
                    'gross_kg' => TextEntry::make('gross_kg')->state(static fn (Delivery $record): ?float => self::weight($record, 'gross_kg'))->numeric(3)->suffix(' kg'),
                    'tare_kg' => TextEntry::make('tare_kg')->state(static fn (Delivery $record): ?float => self::weight($record, 'tare_kg'))->numeric(3)->suffix(' kg'),
                    'net_kg' => TextEntry::make('net_kg')->state(static fn (Delivery $record): ?float => self::weight($record, 'net_kg'))->numeric(3)->suffix(' kg'),
                ]),
            ]),
            'acceptance' => Section::make('acceptance')->schema([
                'grid' => Grid::make(3)->schema([
                    'accepted_kg' => TextEntry::make('accepted_kg')->numeric(3)->suffix(' kg'),
                    'rejected_kg' => TextEntry::make('rejected_kg')->numeric(3)->suffix(' kg'),
                    'rejection_reason' => TextEntry::make('rejection_reason'),
                ]),
            ]),
        ];
    }

    private static function weight(Delivery $delivery, string $attribute): ?float
    {
        $weighing = $delivery->weighing;
        if (! $weighing instanceof Weighing) {
            return null;
        }
        $value = $weighing->getAttribute($attribute);

        return is_numeric($value) ? (float) $value : null;
    }
}
