<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseViewRecord;

class ViewCustomerSite extends XotBaseViewRecord
{
    protected static string $resource = CustomerSiteResource::class;

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
