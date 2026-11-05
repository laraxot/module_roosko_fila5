<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerFirResource\Pages;

use Modules\Roosko\Filament\Customer\Resources\CustomerFirResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewCustomerFir extends XotBaseViewRecord
{
    protected static string $resource = CustomerFirResource::class;
}
