<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\Roosko\Actions\TraceLotOriginAction;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

/**
 * Lotto + unita' che lo contiene (codice, stato, peso, posizione) + tracciabilita' a monte:
 * per i lotti conferiti conferimento e FIR, per quelli prodotti i lotti d'origine risaliti
 * con il lineage di Processing.
 */
class WasteLotInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'lot' => Section::make('lot')->schema([
                'grid' => Grid::make(4)->schema([
                    'ewc_code' => TextEntry::make('ewc_code')->state(static fn (WasteLot $record): ?string => self::ewcLabel($record->ewc_code_id)),
                    'physical_state' => TextEntry::make('physical_state')->badge(),
                    'hazard_properties' => TextEntry::make('hazard_properties')
                        ->badge()
                        ->state(static fn (WasteLot $record): array => array_map(static fn (HazardPropertyEnum $property): string => $property->value, $record->hazardPropertyList())),
                    'is_hazardous' => IconEntry::make('is_hazardous')->boolean()->state(static fn (WasteLot $record): bool => $record->isHazardous()),
                    'classification' => TextEntry::make('classification')->badge(),
                    'destination' => TextEntry::make('destination')->badge(),
                    'eow_batch_code' => TextEntry::make('eow_batch_code'),
                    'eow_declaration_number' => TextEntry::make('eow_declaration_number'),
                ]),
                'notes' => TextEntry::make('notes')->columnSpanFull(),
            ]),
            'unit' => Section::make('unit')->schema([
                'grid' => Grid::make(4)->schema([
                    'unit_code' => TextEntry::make('stockUnit.code')->copyable(),
                    'unit_status' => TextEntry::make('stockUnit.status')->badge(),
                    'unit_container' => TextEntry::make('stockUnit.container_type')->badge(),
                    'unit_net_weight_kg' => TextEntry::make('stockUnit.net_weight_kg')->numeric(3)->suffix(' kg'),
                    'unit_location' => TextEntry::make('stockUnit.location.code'),
                    'unit_received_at' => TextEntry::make('stockUnit.received_at')->dateTime(),
                ]),
            ]),
            'traceability' => Section::make('traceability')->schema([
                'origin' => TextEntry::make('origin')
                    ->state(static fn (WasteLot $record): array => self::origin($record))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->columnSpanFull(),
            ]),
        ];
    }

    private static function ewcLabel(int $ewcCodeId): ?string
    {
        $ewc = EwcCode::query()->find($ewcCodeId);

        return $ewc instanceof EwcCode ? $ewc->code.($ewc->is_hazardous ? '*' : '').' — '.$ewc->description : null;
    }

    /**
     * Righe di tracciabilita' a monte: "unita' · EER · conferimento · FIR (quota %)".
     *
     * @return list<string>
     */
    private static function origin(WasteLot $lot): array
    {
        if ($lot->delivery_id !== null) {
            $delivery = Delivery::query()->find($lot->delivery_id);

            return $delivery instanceof Delivery ? [self::originLine(null, $lot, $delivery, Fir::query()->find($delivery->fir_id), null)] : [];
        }

        $unit = $lot->stockUnit;
        if (! $unit instanceof StockUnit) {
            return [];
        }

        return array_values(app(TraceLotOriginAction::class)->execute($unit)
            ->map(static fn (array $row): string => self::originLine($row['unit'], $row['lot'], $row['delivery'], $row['fir'], $row['share']))
            ->values()
            ->all());
    }

    private static function originLine(?StockUnit $unit, WasteLot $lot, ?Delivery $delivery, ?Fir $fir, ?float $share): string
    {
        $parts = array_filter([
            $unit?->code,
            self::ewcLabel($lot->ewc_code_id),
            $delivery?->code,
            $fir === null ? null : 'FIR '.($fir->number ?? '#'.$fir->id),
            $share === null ? null : number_format($share * 100, 1, ',', '.').' %',
        ]);

        return implode(' · ', $parts);
    }
}
