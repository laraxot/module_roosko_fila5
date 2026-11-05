<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\Pages;

use Filament\Actions\Action;
use Modules\Roosko\Filament\Actions\ReceiveDeliveryHeaderAction;
use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

/**
 * I conferimenti nascono da ReceiveDeliveryAction, non da un form di creazione.
 */
class ListDeliveries extends XotBaseListRecords
{
    protected static string $resource = DeliveryResource::class;

    /**
     * @return array<string, Action>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            'receive' => ReceiveDeliveryHeaderAction::make(),
        ];
    }
}
