<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Pages;

use Filament\Actions\Action;
use Modules\Roosko\Filament\Resources\WasteLotResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseListRecords;

/**
 * I lotti nascono dalle actions (accettazione, output di trattamento): nessun "crea".
 */
class ListWasteLots extends XotBaseListRecords
{
    protected static string $resource = WasteLotResource::class;

    /**
     * @return array<string, Action>
     */
    #[\Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
