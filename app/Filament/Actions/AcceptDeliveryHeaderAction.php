<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Modules\Roosko\Actions\AcceptDeliveryAction;
use Modules\Roosko\Actions\GetLocationOptionsAction;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\Delivery;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Weighing\Models\Weighing;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Accettazione del conferimento (AcceptDeliveryAction): kg accettati/respinti, operazione e
 * repeater delle unita' (contenitore, peso netto, tara, posizione). La coerenza somma unita'
 * = kg accettati la verifica la action di dominio; qui solo i vincoli di formato.
 *
 * Etichetta/icona: `delivery.actions.accept`; testi: `accept_delivery_header.php`.
 */
class AcceptDeliveryHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'accept';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalWidth('4xl')
            ->modalHeading(static fn (Delivery $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->modalDescription(static fn (): string => static::transAction('modal_description'))
            ->fillForm(static fn (Delivery $record): array => [
                'accepted_kg' => self::weighedNetKg($record),
                'rejected_kg' => 0,
                'operation' => WasteOperationEnum::R13->value,
                'units' => [['container_type' => ContainerTypeEnum::BIG_BAG->value, 'net_weight_kg' => self::weighedNetKg($record)]],
            ])
            ->schema(static fn (Delivery $record): array => [
                'totals' => Grid::make(3)->schema([
                    'accepted_kg' => TextInput::make('accepted_kg')->numeric()->minValue(0)->step(0.001)->suffix('kg')->required(),
                    'rejected_kg' => TextInput::make('rejected_kg')->numeric()->minValue(0)->step(0.001)->suffix('kg')->default(0),
                    'operation' => Select::make('operation')->options(WasteOperationEnum::class)->required(),
                ]),
                'rejection_reason' => Textarea::make('rejection_reason')
                    ->rows(2)
                    ->required(static fn (Get $get): bool => is_numeric($get('rejected_kg')) && (float) $get('rejected_kg') > 0),
                'units' => Repeater::make('units')
                    ->schema([
                        'container_type' => Select::make('container_type')->options(ContainerTypeEnum::class)->required(),
                        'net_weight_kg' => TextInput::make('net_weight_kg')->numeric()->minValue(0.001)->step(0.001)->suffix('kg')->required(),
                        'tare_weight_kg' => TextInput::make('tare_weight_kg')->numeric()->minValue(0)->step(0.001)->suffix('kg'),
                        'location_id' => Select::make('location_id')
                            ->options(static fn (): array => app(GetLocationOptionsAction::class)->execute($record->warehouse_id))
                            ->searchable(),
                    ])
                    ->columns(4)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->reorderable(false),
            ])
            ->action(function (array $data, Delivery $record): void {
                /** @var array<string, mixed> $formData */
                $formData = $data;
                $this->runDomainAction(
                    static fn () => app(AcceptDeliveryAction::class)->execute($record, DeliveryAcceptanceData::fromFormState($formData)),
                    static::transAction('success', ['code' => $record->code]),
                );
            })
            ->visible(static fn (Delivery $record): bool => $record->status->canTransitionTo(DeliveryStatusEnum::ACCEPTED));
    }

    private static function weighedNetKg(Delivery $delivery): ?float
    {
        $weighing = $delivery->weighing;

        return $weighing instanceof Weighing && $weighing->net_kg !== null ? (float) $weighing->net_kg : null;
    }
}
