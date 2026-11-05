<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Modules\Roosko\Actions\GetCustomerSiteDeliveredKgAction;
use Modules\Waste\Models\OperatorSite;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;

class CustomerSiteInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'site' => Section::make('site')
                ->schema([
                    'name' => TextEntry::make('name'),
                    'operator' => TextEntry::make('operator.business_name'),
                    'rentri_site_number' => TextEntry::make('rentri_site_number'),
                    'is_active' => IconEntry::make('is_active')->boolean(),
                    'address' => TextEntry::make('address.full_address')->columnSpanFull(),
                ])
                ->columns(2),
            'deliveries' => Section::make('deliveries')
                ->schema([
                    'delivered_kg' => TextEntry::make('delivered_kg')
                        ->state(static fn (OperatorSite $record): float => app(GetCustomerSiteDeliveredKgAction::class)->execute([$record->id])[$record->id] ?? 0.0)
                        ->numeric(3)
                        ->suffix(' kg'),
                ]),
        ];
    }
}
