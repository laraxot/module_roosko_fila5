<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Labeling\Actions\ResolveIdentifiableAction;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Cosa ha scansionato l'operatore: un'unita' o una posizione.
 *
 * Prima gli identificatori attivi di Labeling (QR, barcode, RFID, NFC, URL `/l/{code}`), poi il
 * codice leggibile stampato (digitato a mano o etichetta senza identificatore assegnato).
 */
class ResolveScannedCodeAction
{
    use QueueableAction;

    public function execute(string $scanned): StockUnit|Location|null
    {
        $scanned = trim($scanned);
        if ($scanned === '') {
            return null;
        }

        $model = app(ResolveIdentifiableAction::class)->execute($scanned);
        if ($model instanceof StockUnit || $model instanceof Location) {
            return $model;
        }

        $code = mb_strtoupper($scanned);

        return StockUnit::query()->where('code', $code)->first()
            ?? Location::query()->where('code', $code)->first();
    }
}
