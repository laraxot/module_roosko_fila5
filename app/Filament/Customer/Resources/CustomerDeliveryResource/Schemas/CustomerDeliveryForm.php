<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Schemas;

use Filament\Schemas\Components\Component;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Nessun campo: il cliente non modifica i conferimenti. Esiste perche' la pagina di dettaglio
 * costruisce comunque il form della resource; senza questa classe XotBaseResource ripiegherebbe
 * sul form amministrativo (DeliveryForm).
 */
class CustomerDeliveryForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [];
    }
}
