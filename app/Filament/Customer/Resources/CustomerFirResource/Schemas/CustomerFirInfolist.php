<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerFirResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

class CustomerFirInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'identification' => Section::make('identification')
                ->schema([
                    'number' => TextEntry::make('number')->copyable(),
                    'status' => TextEntry::make('status')->badge(),
                    'is_digital' => IconEntry::make('is_digital')->boolean(),
                ])
                ->columns(3),
            'subjects' => Section::make('subjects')
                ->schema([
                    'producer' => TextEntry::make('producer.business_name'),
                    'producer_site' => TextEntry::make('producerSite.name'),
                    'transporter' => TextEntry::make('transporter.business_name'),
                    'recipient' => TextEntry::make('recipient.business_name'),
                ])
                ->columns(2),
            'waste' => Section::make('waste')
                ->schema([
                    'ewc_code' => TextEntry::make('ewcCode.display_code'),
                    'ewc_description' => TextEntry::make('ewcCode.description'),
                    'operation' => TextEntry::make('operation')->badge(),
                    'quantity' => TextEntry::make('quantity')->numeric(decimalPlaces: 3),
                    'quantity_unit' => TextEntry::make('quantity_unit'),
                    'is_quantity_estimated' => IconEntry::make('is_quantity_estimated')->boolean(),
                ])
                ->columns(2),
            'acceptance_section' => Section::make('acceptance_section')
                ->schema([
                    'transport_started_at' => TextEntry::make('transport_started_at')->dateTime(),
                    'arrived_at' => TextEntry::make('arrived_at')->dateTime(),
                    'acceptance' => TextEntry::make('acceptance')->badge(),
                    'accepted_quantity_kg' => TextEntry::make('accepted_quantity_kg')->numeric(decimalPlaces: 3)->suffix(' kg'),
                    'rejection_reason' => TextEntry::make('rejection_reason')->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
