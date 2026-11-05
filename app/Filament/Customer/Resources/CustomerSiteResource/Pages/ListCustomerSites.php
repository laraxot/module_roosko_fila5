<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages;

use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

class ListCustomerSites extends XotBaseListRecords
{
    protected static string $resource = CustomerSiteResource::class;
}
