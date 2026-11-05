<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Textarea;
use Modules\Roosko\Actions\RejectDeliveryAction;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\Delivery;
use Modules\Xot\Actions\Cast\SafeStringCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Respingimento integrale del carico (RejectDeliveryAction).
 *
 * Etichetta/icona: `delivery.actions.reject`; testi: `reject_delivery_header.php`.
 */
class RejectDeliveryHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'reject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(static fn (Delivery $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->modalDescription(static fn (): string => static::transAction('modal_description'))
            ->schema([
                'reason' => Textarea::make('reason')->rows(3)->required(),
            ])
            ->action(function (array $data, Delivery $record): void {
                $this->runDomainAction(
                    static fn () => app(RejectDeliveryAction::class)->execute($record, SafeStringCastAction::cast($data['reason'] ?? '')),
                    static::transAction('success', ['code' => $record->code]),
                );
            })
            ->visible(static fn (Delivery $record): bool => $record->status->canTransitionTo(DeliveryStatusEnum::REJECTED));
    }
}
