<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages;

use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Schemas\CustomerSiteForm;
use Modules\Waste\Models\OperatorSite;
use Modules\Xot\Filament\Resources\Pages\XotBaseEditRecord;

/**
 * Il form di modifica non ha soggetto ne' `is_plant`: il cantiere resta del soggetto con cui
 * e' nato. L'eliminazione e' negata da CustomerBaseResource (abilita' `delete` assente).
 *
 * Il numero RENTRI di un cantiere gia' indicato da un FIR non si salva, anche se arrivasse nei
 * dati del form (richiesta Livewire manipolata): lo decide il server, non il campo disabilitato.
 */
class EditCustomerSite extends XotBaseEditRecord
{
    protected static string $resource = CustomerSiteResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        if ($record instanceof OperatorSite && CustomerSiteForm::registrationLocked($record)) {
            unset($data['rentri_site_number']);
        }

        return $data;
    }
}
