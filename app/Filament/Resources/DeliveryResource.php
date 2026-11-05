<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Modules\Roosko\Filament\Resources\DeliveryResource\Pages\EditDelivery;
use Modules\Roosko\Filament\Resources\DeliveryResource\Pages\ListDeliveries;
use Modules\Roosko\Filament\Resources\DeliveryResource\Pages\ViewDelivery;
use Modules\Roosko\Filament\Resources\DeliveryResource\RelationManagers\WasteLotsRelationManager;
use Modules\Roosko\Models\Delivery;
use Modules\Xot\Filament\Resources\XotBaseResource;

class DeliveryResource extends XotBaseResource
{
    protected static ?string $model = Delivery::class;

    /**
     * @return array<class-string<RelationManager>>
     */
    #[\Override]
    public static function getRelations(): array
    {
        return [
            WasteLotsRelationManager::class,
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListDeliveries::route('/'),
            'view' => ViewDelivery::route('/{record}'),
            'edit' => EditDelivery::route('/{record}/edit'),
        ];
    }
}
