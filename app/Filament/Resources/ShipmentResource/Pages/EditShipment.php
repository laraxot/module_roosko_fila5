<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Pages;

use Modules\Roosko\Filament\Resources\ShipmentResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseEditRecord;

class EditShipment extends XotBaseEditRecord
{
    protected static string $resource = ShipmentResource::class;
}
