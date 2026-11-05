<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Filament\Customer\Resources\CustomerFirResource\Pages\ListCustomerFirs;
use Modules\Roosko\Filament\Customer\Resources\CustomerFirResource\Pages\ViewCustomerFir;
use Modules\Waste\Models\Fir;

/**
 * Formulari del cliente, in sola lettura: FIR in cui il produttore e' un suo soggetto.
 */
class CustomerFirResource extends CustomerBaseResource
{
    protected static ?string $model = Fir::class;

    protected static ?string $slug = 'firs';

    protected static ?string $recordTitleAttribute = 'number';

    #[\Override]
    public static function scopeToCustomer(Builder $query, array $operatorIds): Builder
    {
        return $query->whereIn('producer_operator_id', $operatorIds);
    }

    #[\Override]
    protected static function getCustomerAbilities(): array
    {
        return ['viewAny', 'view'];
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
            'index' => ListCustomerFirs::route('/'),
            'view' => ViewCustomerFir::route('/{record}'),
        ];
    }
}
