<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Modules\Roosko\Actions\CancelShipmentAction;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\Shipment;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Annulla un'uscita non spedita (CancelShipmentAction): le unita' caricate tornano libere.
 *
 * Abilita' `cancel` di ShipmentPolicy. Testi: `cancel_shipment_header.php`.
 */
class CancelShipmentHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->authorize('cancel')
            ->modalHeading(static fn (Shipment $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->modalDescription(static fn (Shipment $record): string => static::transAction('modal_description', ['count' => $record->items()->count()]))
            ->action(function (Shipment $record): void {
                $this->runDomainAction(
                    static fn () => app(CancelShipmentAction::class)->execute($record),
                    static::transAction('success', ['code' => $record->code]),
                );
            })
            ->visible(static fn (Shipment $record): bool => $record->status->canTransitionTo(ShipmentStatusEnum::CANCELLED));
    }
}
