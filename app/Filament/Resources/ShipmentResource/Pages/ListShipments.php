<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Pages;

use Filament\Actions\Action;
use Modules\Roosko\Filament\Actions\CreateShipmentHeaderAction;
use Modules\Roosko\Filament\Resources\ShipmentResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

class ListShipments extends XotBaseListRecords
{
    protected static string $resource = ShipmentResource::class;

    /**
     * @return array<string, Action>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            'create_shipment' => CreateShipmentHeaderAction::make(),
        ];
    }
}
