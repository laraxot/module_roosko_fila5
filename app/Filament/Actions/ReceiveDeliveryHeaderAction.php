<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Models\Delivery;
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Weighing\Models\Scale;
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Registra l'arrivo di un carico (ReceiveDeliveryAction) e apre la pagina del conferimento.
 *
 * Etichetta/icona: `delivery.actions.receive`; testi del modale: `receive_delivery_header.php`.
 */
class ReceiveDeliveryHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'receive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-down-tray')
            ->authorize('create')
            ->color('primary')
            ->modalHeading(static fn (): string => static::transAction('modal_heading'))
            ->modalDescription(static fn (): string => static::transAction('modal_description'))
            ->schema([
                'fir_id' => Select::make('fir_id')
                    ->options(static fn (): array => self::firOptions())
                    ->searchable()
                    ->required(),
                'plant_site_id' => Select::make('plant_site_id')
                    ->options(static fn (): array => OperatorSite::query()->where('is_plant', true)->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->required(),
                'warehouse_id' => Select::make('warehouse_id')
                    ->options(static fn (): array => Warehouse::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->required(),
                'gross_kg' => TextInput::make('gross_kg')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.001)
                    ->suffix('kg'),
                'scale_id' => Select::make('scale_id')
                    ->options(static function (): array {
                        /** @var Builder<Scale> $query */
                        $query = Scale::query();

                        return $query->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
                    }),
            ])
            ->action(function (array $data): void {
                $delivery = null;
                $this->runDomainAction(
                    static function () use ($data, &$delivery): void {
                        $scaleId = $data['scale_id'] ?? null;
                        $gross = $data['gross_kg'] ?? null;
                        $scale = null;
                        if ($scaleId !== null && $scaleId !== '') {
                            /** @var Builder<Scale> $scaleQuery */
                            $scaleQuery = Scale::query();
                            $scale = $scaleQuery->findOrFail(SafeIntCastAction::cast($scaleId));
                        }
                        $delivery = app(ReceiveDeliveryAction::class)->execute(
                            Fir::query()->findOrFail(SafeIntCastAction::cast($data['fir_id'] ?? null)),
                            OperatorSite::query()->findOrFail(SafeIntCastAction::cast($data['plant_site_id'] ?? null)),
                            Warehouse::query()->findOrFail(SafeIntCastAction::cast($data['warehouse_id'] ?? null)),
                            $gross === null || $gross === '' ? null : SafeFloatCastAction::cast($gross),
                            $scale,
                        );
                    },
                    static::transAction('success'),
                );

                if ($delivery instanceof Delivery) {
                    $this->redirect(DeliveryResource::getUrl('view', ['record' => $delivery]));
                }
            });
    }

    /**
     * FIR conferibili, piu' recenti per primi: id => numero.
     *
     * Solo FIR non in stato finale (bozza, emesso, in viaggio, consegnato) destinati a un impianto
     * attivo (sito di destino, o in mancanza soggetto destinatario titolare di un impianto: cosi'
     * restano fuori i FIR in uscita creati dalle spedizioni), esclusi quelli con un conferimento ancora aperto: i
     * conferimenti aperti sono il lavoro in corso del piazzale (insieme limitato), non la storia
     * (audit database R5). ReceiveDeliveryAction ricontrolla tutto sotto lock.
     *
     * @return array<int, string>
     */
    private static function firOptions(): array
    {
        $openStatuses = array_map(static fn (DeliveryStatusEnum $status): string => $status->value, array_filter(
            DeliveryStatusEnum::cases(),
            static fn (DeliveryStatusEnum $status): bool => $status->isOpen(),
        ));
        $receivable = array_map(static fn (FirStatusEnum $status): string => $status->value, array_filter(
            FirStatusEnum::cases(),
            static fn (FirStatusEnum $status): bool => ! $status->isFinal(),
        ));

        $inProgress = Delivery::query()->whereIn('status', array_values($openStatuses))->pluck('fir_id')->all();

        /** @var Collection<int, Fir> $firs */
        $firs = Fir::query()
            ->whereIn('status', array_values($receivable))
            ->whereNotIn('id', $inProgress)
            ->where(static fn (Builder $query): Builder => $query
                ->whereIn('recipient_site_id', self::plantSites()->select('id'))
                ->orWhere(static fn (Builder $query): Builder => $query
                    ->whereNull('recipient_site_id')
                    ->whereIn('recipient_operator_id', self::plantSites()->select('operator_id'))))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $firs
            ->mapWithKeys(static fn (Fir $fir): array => [$fir->id => (string) ($fir->number ?? '#'.$fir->id)])
            ->all();
    }

    /**
     * @return Builder<OperatorSite>
     */
    private static function plantSites(): Builder
    {
        return OperatorSite::query()->where('is_plant', true)->where('is_active', true);
    }
}
