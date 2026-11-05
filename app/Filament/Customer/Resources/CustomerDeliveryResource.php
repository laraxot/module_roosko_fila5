<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Pages\ListCustomerDeliveries;
use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Pages\ViewCustomerDelivery;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;

/**
 * Conferimenti del cliente, in sola lettura: quelli il cui FIR e' del cliente
 * (stesso scope di CustomerFirResource). FIR e conferimenti stanno su connessioni diverse:
 * prima gli id dei FIR, poi i conferimenti.
 */
class CustomerDeliveryResource extends CustomerBaseResource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $slug = 'deliveries';

    protected static ?string $recordTitleAttribute = 'code';

    #[\Override]
    public static function scopeToCustomer(Builder $query, array $operatorIds): Builder
    {
        $firIds = CustomerFirResource::scopeToCustomer(Fir::query(), $operatorIds)->pluck('id')->all();

        return $query->whereIn('fir_id', $firIds);
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
            'index' => ListCustomerDeliveries::route('/'),
            'view' => ViewCustomerDelivery::route('/{record}'),
        ];
    }
}
