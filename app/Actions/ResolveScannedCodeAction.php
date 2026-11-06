<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Illuminate\Database\Eloquent\Builder;
use Modules\Labeling\Actions\NormalizeScannedCodeAction;
use Modules\Labeling\Actions\ResolveIdentifiableAction;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Cosa ha scansionato l'operatore: un'unita' o una posizione.
 *
 * 1. Identificatori attivi di Labeling (QR, barcode, RFID, NFC, URL `/l/{code}`).
 * 2. Codice dell'etichetta senza identificatore assegnato, ripulito da NormalizeScannedCodeAction
 *    (caratteri di controllo dei lettori wedge, URL `/l/{code}` del QR):
 *    - unita': `SU-...` (StockUnit::getLabelCode() = codice dell'unita');
 *    - posizione: `LOC-{magazzino}-{codice}` (Location::getLabelCode()). Codice del magazzino e
 *      della posizione possono contenere `-`: si provano tutti i punti di separazione;
 *    - codice interno della posizione digitato a mano (es. `STO-01`).
 *
 * Le etichette delle posizioni codificano `/l/LOC-...` ma alle posizioni nessuno assegna un
 * identificatore: senza il passo 2 la scansione della posizione dal palmare non si risolve.
 */
class ResolveScannedCodeAction
{
    use QueueableAction;

    public function execute(string $scanned): StockUnit|Location|null
    {
        $scan = app(NormalizeScannedCodeAction::class)->execute($scanned);
        if ($scan->isEmpty()) {
            return null;
        }

        $model = app(ResolveIdentifiableAction::class)->execute($scanned);
        if ($model instanceof StockUnit || $model instanceof Location) {
            return $model;
        }

        $code = mb_strtoupper($scan->code);

        return StockUnit::query()->where('code', $code)->first()
            ?? $this->locationFromLabelCode($code)
            ?? Location::query()->where('code', $code)->first();
    }

    /**
     * `LOC-{magazzino}-{codice}` → posizione con quel codice in un'area del magazzino.
     */
    private function locationFromLabelCode(string $code): ?Location
    {
        $prefix = Location::LABEL_PREFIX.'-';
        if (! str_starts_with($code, $prefix)) {
            return null;
        }

        $parts = explode('-', substr($code, \strlen($prefix)));
        for ($split = 1, $count = \count($parts); $split < $count; $split++) {
            $warehouseCode = implode('-', \array_slice($parts, 0, $split));
            $locationCode = implode('-', \array_slice($parts, $split));

            $location = Location::query()
                ->where('code', $locationCode)
                ->whereHas('area.warehouse', static fn (Builder $query): Builder => $query->where('code', $warehouseCode))
                ->first();
            if ($location instanceof Location) {
                return $location;
            }
        }

        return null;
    }
}
