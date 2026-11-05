<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Weighing\Models\Weighing;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

class DeliveryInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'delivery' => Section::make('delivery')->schema([
                'grid' => Grid::make(4)->schema([
                    'code' => TextEntry::make('code')->copyable(),
                    'status' => TextEntry::make('status')->badge(),
                    'arrived_at' => TextEntry::make('arrived_at')->dateTime(),
                    'analysis_required' => IconEntry::make('analysis_required')->boolean(),
                    'fir_number' => TextEntry::make('fir_number')->state(static fn (Delivery $record): ?string => self::fir($record)?->number),
                    'ewc_code' => TextEntry::make('ewc_code')->state(static fn (Delivery $record): ?string => self::ewcLabel($record)),
                    'plant_site' => TextEntry::make('plantSite.name'),
                    'warehouse' => TextEntry::make('warehouse.name'),
                ]),
            ]),
            'weighing' => Section::make('weighing')->schema([
                'grid' => Grid::make(4)->schema([
                    'gross_kg' => TextEntry::make('gross_kg')->state(static fn (Delivery $record): ?float => self::weight($record, 'gross_kg'))->numeric(3)->suffix(' kg'),
                    'tare_kg' => TextEntry::make('tare_kg')->state(static fn (Delivery $record): ?float => self::weight($record, 'tare_kg'))->numeric(3)->suffix(' kg'),
                    'net_kg' => TextEntry::make('net_kg')->state(static fn (Delivery $record): ?float => self::weight($record, 'net_kg'))->numeric(3)->suffix(' kg'),
                    'fir_quantity' => TextEntry::make('fir_quantity')->state(static fn (Delivery $record): ?string => self::fir($record)?->quantity === null ? null : (string) self::fir($record)?->quantity),
                ]),
            ]),
            'acceptance' => Section::make('acceptance')->schema([
                'grid' => Grid::make(3)->schema([
                    'accepted_kg' => TextEntry::make('accepted_kg')->numeric(3)->suffix(' kg'),
                    'rejected_kg' => TextEntry::make('rejected_kg')->numeric(3)->suffix(' kg'),
                    'rejection_reason' => TextEntry::make('rejection_reason'),
                ]),
                'inspection_notes' => TextEntry::make('inspection_notes')->columnSpanFull(),
            ]),
        ];
    }

    private static function fir(Delivery $delivery): ?Fir
    {
        return Fir::query()->find($delivery->fir_id);
    }

    private static function ewcLabel(Delivery $delivery): ?string
    {
        $fir = self::fir($delivery);
        $ewc = $fir === null ? null : EwcCode::query()->find($fir->ewc_code_id);

        return $ewc instanceof EwcCode ? $ewc->code.($ewc->is_hazardous ? '*' : '').' — '.$ewc->description : null;
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
