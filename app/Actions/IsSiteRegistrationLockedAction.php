<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;

/**
 * I dati regolatori di un cantiere (numero di iscrizione RENTRI dell'unita' locale) sono del
 * cliente solo finche' nessun FIR lo indica come luogo di produzione: da quel momento finiscono
 * nei payload RENTRI dell'impianto e la variazione si chiede all'impianto (audit sicurezza M6).
 */
class IsSiteRegistrationLockedAction
{
    use QueueableAction;

    public function execute(OperatorSite $site): bool
    {
        return Fir::query()->where('producer_site_id', $site->id)->exists();
    }
}
