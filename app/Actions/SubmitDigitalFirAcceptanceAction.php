<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Rentri\Actions\SubmitFirAcceptanceAction;
use Modules\Rentri\Models\RentriCredential;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;

/**
 * Accoda l'invio a RENTRI dell'accettazione (o del respingimento) di un FIR, solo se il FIR
 * e' digitale e il sito ha una credenziale. Da chiamare dopo il commit.
 */
class SubmitDigitalFirAcceptanceAction
{
    use QueueableAction;

    public function execute(Fir $fir, OperatorSite $plant): bool
    {
        if (! (bool) $fir->is_digital) {
            return false;
        }

        $credential = app(FindRentriCredentialAction::class)->execute($plant);
        if (! $credential instanceof RentriCredential) {
            return false;
        }

        app(SubmitFirAcceptanceAction::class)
            ->onQueue(app(GetQueueNameAction::class)->execute('rentri'))
            ->execute($fir, $credential);

        return true;
    }
}
