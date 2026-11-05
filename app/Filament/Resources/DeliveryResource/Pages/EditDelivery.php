<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\Pages;

use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseEditRecord;

/**
 * Si modificano solo i dati di ispezione: stato e pesi li scrivono le actions.
 */
class EditDelivery extends XotBaseEditRecord
{
    protected static string $resource = DeliveryResource::class;
}
