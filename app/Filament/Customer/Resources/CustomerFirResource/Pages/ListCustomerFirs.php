<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerFirResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Modules\Roosko\Filament\Customer\Resources\CustomerFirResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

/**
 * Sola lettura: i FIR li emette il produttore e li registra l'impianto.
 */
class ListCustomerFirs extends XotBaseListRecords
{
    protected static string $resource = CustomerFirResource::class;

    /**
     * @return array<string, Action|ActionGroup>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
