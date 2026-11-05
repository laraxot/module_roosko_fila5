<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages;

use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseEditRecord;

/**
 * Il form di modifica non ha soggetto ne' `is_plant`: il cantiere resta del soggetto con cui
 * e' nato. L'eliminazione e' negata da CustomerBaseResource (abilita' `delete` assente).
 */
class EditCustomerSite extends XotBaseEditRecord
{
    protected static string $resource = CustomerSiteResource::class;
}
