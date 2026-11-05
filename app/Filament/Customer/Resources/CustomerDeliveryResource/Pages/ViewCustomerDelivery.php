<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Pages;

use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewCustomerDelivery extends XotBaseViewRecord
{
    protected static string $resource = CustomerDeliveryResource::class;
}
