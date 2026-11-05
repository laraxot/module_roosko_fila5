<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Modules\Roosko\Filament\Resources\WasteLotResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewWasteLot extends XotBaseViewRecord
{
    protected static string $resource = WasteLotResource::class;

    /**
     * @return array<string, Action|ActionGroup>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            'edit' => EditAction::make(),
        ];
    }
}
