<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Modules\Roosko\Filament\Resources\ShipmentResource\Pages\EditShipment;
use Modules\Roosko\Filament\Resources\ShipmentResource\Pages\ListShipments;
use Modules\Roosko\Filament\Resources\ShipmentResource\Pages\ViewShipment;
use Modules\Roosko\Filament\Resources\ShipmentResource\RelationManagers\ShipmentItemsRelationManager;
use Modules\Roosko\Models\Shipment;
use Modules\Xot\Filament\Resources\XotBaseResource;

class ShipmentResource extends XotBaseResource
{
    protected static ?string $model = Shipment::class;

    /**
     * @return array<class-string<RelationManager>>
     */
    #[\Override]
    public static function getRelations(): array
    {
        return [
            ShipmentItemsRelationManager::class,
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'view' => ViewShipment::route('/{record}'),
            'edit' => EditShipment::route('/{record}/edit'),
        ];
    }
}
