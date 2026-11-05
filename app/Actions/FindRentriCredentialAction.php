<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Rentri\Actions\ResolveCredentialAction;
use Modules\Rentri\Exceptions\RentriCredentialException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Waste\Models\OperatorSite;
use Spatie\QueueableAction\QueueableAction;

/**
 * Credenziale RENTRI del sito, se esiste: Roosko invia a RENTRI solo quando c'e'.
 *
 * La selezione (attiva, non scaduta, ambiente preferito) resta in ResolveCredentialAction di
 * Rentri, che segnala l'assenza con RentriCredentialException: qui diventa null.
 */
class FindRentriCredentialAction
{
    use QueueableAction;

    public function execute(OperatorSite $site): ?RentriCredential
    {
        try {
            return app(ResolveCredentialAction::class)->execute($site);
        } catch (RentriCredentialException) {
            return null;
        }
    }
}
