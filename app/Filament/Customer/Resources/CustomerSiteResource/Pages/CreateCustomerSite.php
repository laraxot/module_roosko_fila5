<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages;

use Filament\Facades\Filament;
use Modules\Roosko\Actions\ResolveCustomerSiteOperatorAction;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseCreateRecord;

/**
 * Il cantiere nasce intestato a un soggetto del cliente e mai come impianto: `operator_id` e
 * `is_plant` li decide il server, qualunque cosa contenga il form.
 */
class CreateCustomerSite extends XotBaseCreateRecord
{
    protected static string $resource = CustomerSiteResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $requested = $data['operator_id'] ?? null;

        $data['operator_id'] = app(ResolveCustomerSiteOperatorAction::class)->execute(
            Filament::getTenant(),
            \is_int($requested) || \is_string($requested) ? $requested : null,
        );
        $data['is_plant'] = false;

        return $data;
    }
}
