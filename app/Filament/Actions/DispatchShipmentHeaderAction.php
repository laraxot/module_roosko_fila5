<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Modules\Roosko\Actions\DispatchShipmentAction;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\Shipment;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Spedisce l'uscita (DispatchShipmentAction): unita' in uscita, FIR e scarichi di registro.
 *
 * Etichetta/icona: `shipment.actions.dispatch`; testi: `dispatch_shipment_header.php`.
 */
class DispatchShipmentHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'dispatch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->authorize('dispatch')
            ->modalHeading(static fn (Shipment $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->modalDescription(static fn (Shipment $record): string => static::transAction('modal_description', [
                'count' => $record->items()->count(),
                'kg' => number_format($record->totalKg(), 3, ',', '.'),
            ]))
            ->action(function (Shipment $record): void {
                $this->runDomainAction(
                    static fn () => app(DispatchShipmentAction::class)->execute($record),
                    static::transAction('success', ['code' => $record->code]),
                );
            })
            ->visible(static fn (Shipment $record): bool => $record->status->canTransitionTo(ShipmentStatusEnum::DISPATCHED));
    }
}
