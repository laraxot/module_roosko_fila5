<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Modules\Roosko\Actions\RemoveShipmentItemAction;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Toglie una riga dall'uscita (RemoveShipmentItemAction) e libera l'unita' (`reserved` → `stored`).
 * Sostituisce la DeleteAction nativa, che cancellerebbe la riga lasciando l'unita' riservata.
 *
 * Abilita' `delete` di ShipmentItemPolicy. Testi: `remove_shipment_item_table.php`.
 */
class RemoveShipmentItemTableAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'remove';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->authorize('delete')
            ->modalHeading(static::transAction('modal_heading'))
            ->action(function (ShipmentItem $record): void {
                $this->runDomainAction(
                    static fn () => app(RemoveShipmentItemAction::class)->execute($record),
                    static::transAction('success'),
                );
            })
            ->visible(static fn (ShipmentItem $record): bool => $record->shipment->status->acceptsItems());
    }
}
