<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Exceptions\AuthorizationException;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\Register;
use Spatie\QueueableAction\QueueableAction;

/**
 * Registro cronologico di carico/scarico attivo del sito d'impianto.
 */
class ResolvePlantRegisterAction
{
    use QueueableAction;

    public function execute(OperatorSite $site): Register
    {
        $register = Register::query()
            ->where('operator_site_id', $site->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $register instanceof Register) {
            throw AuthorizationException::registerNotFound($site->name);
        }

        return $register;
    }
}
