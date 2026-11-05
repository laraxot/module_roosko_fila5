<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

/**
 * Sola lettura: i conferimenti li registra l'accettazione dell'impianto.
 */
class ListCustomerDeliveries extends XotBaseListRecords
{
    protected static string $resource = CustomerDeliveryResource::class;

    /**
     * @return array<string, Action|ActionGroup>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
