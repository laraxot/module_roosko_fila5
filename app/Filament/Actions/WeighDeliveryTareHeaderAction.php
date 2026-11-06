<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\TextInput;
use Modules\Roosko\Actions\WeighDeliveryAction;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\Delivery;
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Seconda pesata (tara) del conferimento (WeighDeliveryAction).
 *
 * Etichetta/icona: `delivery.actions.weigh_tare`; testi: `weigh_delivery_tare_header.php`.
 */
class WeighDeliveryTareHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'weigh_tare';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-scale')
            ->authorize('weigh')
            ->color('gray')
            ->modalHeading(static fn (Delivery $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->schema([
                'tare_kg' => TextInput::make('tare_kg')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.001)
                    ->suffix('kg')
                    ->required(),
            ])
            ->action(function (array $data, Delivery $record): void {
                $this->runDomainAction(
                    static fn () => app(WeighDeliveryAction::class)->execute($record, SafeFloatCastAction::cast($data['tare_kg'] ?? null)),
                    static::transAction('success', ['code' => $record->code]),
                );
            })
            ->visible(static fn (Delivery $record): bool => $record->weighing_id !== null
                && $record->status->canTransitionTo(DeliveryStatusEnum::WEIGHED));
    }
}
