<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages\CreateCustomerSite;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages\EditCustomerSite;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages\ListCustomerSites;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages\ViewCustomerSite;
use Modules\Waste\Models\OperatorSite;

/**
 * Cantieri del cliente: unita' locali (`OperatorSite`, mai `is_plant`) dei suoi soggetti.
 * Il cliente li crea e li modifica; il soggetto e `is_plant = false` li fissa il server
 * (CreateCustomerSite + ResolveCustomerSiteOperatorAction).
 */
class CustomerSiteResource extends CustomerBaseResource
{
    protected static ?string $model = OperatorSite::class;

    protected static ?string $slug = 'sites';

    protected static ?string $recordTitleAttribute = 'name';

    #[\Override]
    public static function scopeToCustomer(Builder $query, array $operatorIds): Builder
    {
        return $query->whereIn('operator_id', $operatorIds)->where('is_plant', false);
    }

    #[\Override]
    protected static function getCustomerAbilities(): array
    {
        return ['viewAny', 'view', 'create', 'update'];
    }

    /**
     * @return array<class-string<RelationManager>>
     */
    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListCustomerSites::route('/'),
            'create' => CreateCustomerSite::route('/create'),
            'view' => ViewCustomerSite::route('/{record}'),
            'edit' => EditCustomerSite::route('/{record}/edit'),
        ];
    }
}
