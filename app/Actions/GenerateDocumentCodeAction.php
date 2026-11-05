<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\Shipment;
use Spatie\QueueableAction\QueueableAction;

/**
 * Progressivo annuale dei documenti Roosko: `CNF-2026-000001` (conferimenti),
 * `USC-2026-000001` (uscite).
 *
 * Va chiamata dentro una transazione: legge l'ultimo codice dell'anno con `lockForUpdate`
 * (anche tra i cancellati, un codice non si riusa). L'indice unique su `code` resta l'ultima
 * difesa contro le corse sul primo codice dell'anno.
 */
class GenerateDocumentCodeAction
{
    use QueueableAction;

    public const int DIGITS = 6;

    /**
     * @param  class-string<Delivery|Shipment>  $modelClass
     */
    public function execute(string $modelClass, string $prefix, ?CarbonImmutable $at = null): string
    {
        $yearPrefix = $prefix.'-'.($at ?? CarbonImmutable::now())->format('Y').'-';

        /** @var Model $model */
        $model = new $modelClass;
        $last = $model->newQueryWithoutScopes()
            ->where('code', 'like', $yearPrefix.'%')
            ->orderByDesc('code')
            ->lockForUpdate()
            ->value('code');

        $next = \is_string($last) ? ((int) substr($last, \strlen($yearPrefix))) + 1 : 1;

        return self::format($yearPrefix, $next);
    }

    public static function format(string $yearPrefix, int $progressive): string
    {
        return $yearPrefix.str_pad((string) $progressive, self::DIGITS, '0', STR_PAD_LEFT);
    }
}
