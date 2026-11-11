<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Widgets\Concerns;

use Filament\Actions\Action;

/**
 * Tabelle di riepilogo in sola lettura (dashboard dell'impianto e dell'area clienti).
 *
 * XotBaseTableWidget aggiunge di default «Nuovo», il cambio di layout e l'eliminazione multipla
 * (HasXotTable): su un riepilogo non hanno senso, e nell'area clienti il cliente vedeva «Nuovo».
 */
trait ReadOnlyTableWidget
{
    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableActions(): array
    {
        return [];
    }

    /**
     * @return array<string, never>
     */
    #[\Override]
    public function getTableBulkActions(): array
    {
        return [];
    }
}
