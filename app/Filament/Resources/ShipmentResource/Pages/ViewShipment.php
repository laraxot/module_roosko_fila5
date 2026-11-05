<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Modules\Roosko\Filament\Actions\DispatchShipmentHeaderAction;
use Modules\Roosko\Filament\Resources\ShipmentResource;
use Modules\Roosko\Models\Shipment;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewShipment extends XotBaseViewRecord
{
    protected static string $resource = ShipmentResource::class;

    /**
     * @return array<string, Action|ActionGroup>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            'dispatch' => DispatchShipmentHeaderAction::make(),
            'edit' => EditAction::make()->visible(static fn (Shipment $record): bool => $record->status->acceptsItems()),
        ];
    }
}
