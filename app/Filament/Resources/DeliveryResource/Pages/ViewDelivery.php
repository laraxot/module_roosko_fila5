<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Modules\Roosko\Filament\Actions\AcceptDeliveryHeaderAction;
use Modules\Roosko\Filament\Actions\RejectDeliveryHeaderAction;
use Modules\Roosko\Filament\Actions\WeighDeliveryTareHeaderAction;
use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewDelivery extends XotBaseViewRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * @return array<string, Action|ActionGroup>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            'weigh_tare' => WeighDeliveryTareHeaderAction::make(),
            'accept' => AcceptDeliveryHeaderAction::make(),
            'reject' => RejectDeliveryHeaderAction::make(),
            'edit' => EditAction::make(),
        ];
    }
}
