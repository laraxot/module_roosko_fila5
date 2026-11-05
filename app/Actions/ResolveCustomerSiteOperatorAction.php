<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\QueueableAction\QueueableAction;

/**
 * Soggetto a cui intestare un cantiere creato dal cliente: lo decide il server, mai il form.
 *
 * - un solo soggetto del cliente: e' quello, qualunque cosa arrivi dal form;
 * - piu' soggetti: quello scelto, purche' sia del cliente;
 * - nessun soggetto (o scelta non valida): errore di validazione sul campo `operator_id`.
 */
class ResolveCustomerSiteOperatorAction
{
    use QueueableAction;

    /**
     * @throws ValidationException
     */
    public function execute(?Model $tenant, int|string|null $requestedOperatorId = null): int
    {
        $operatorIds = app(GetCustomerOperatorIdsAction::class)->execute($tenant);

        if ($operatorIds === []) {
            throw ValidationException::withMessages([
                'data.operator_id' => __('roosko::customer_site.errors.no_operator'),
            ]);
        }

        if (\count($operatorIds) === 1) {
            return $operatorIds[0];
        }

        $requested = is_numeric($requestedOperatorId) ? (int) $requestedOperatorId : null;
        if ($requested !== null && \in_array($requested, $operatorIds, true)) {
            return $requested;
        }

        throw ValidationException::withMessages([
            'data.operator_id' => __('roosko::customer_site.errors.operator_required'),
        ]);
    }
}
