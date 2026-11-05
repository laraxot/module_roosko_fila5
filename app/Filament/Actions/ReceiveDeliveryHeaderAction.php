<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
=======
>>>>>>> laraxot/dev
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Roosko\Models\Delivery;
<<<<<<< HEAD
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
=======
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Models\Warehouse;
>>>>>>> laraxot/dev
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
<<<<<<< HEAD
                    ->options(static function (): array {
                        /** @var Builder<Scale> $query */
                        $query = Scale::query();

                        return $query->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
                    }),
=======
                    ->options(static fn (): array => Scale::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
>>>>>>> laraxot/dev
            ])
            ->action(function (array $data): void {
                $delivery = null;
                $this->runDomainAction(
                    static function () use ($data, &$delivery): void {
                        $scaleId = $data['scale_id'] ?? null;
                        $gross = $data['gross_kg'] ?? null;
<<<<<<< HEAD
                        $scale = null;
                        if ($scaleId !== null && $scaleId !== '') {
                            /** @var Builder<Scale> $scaleQuery */
                            $scaleQuery = Scale::query();
                            $scale = $scaleQuery->findOrFail(SafeIntCastAction::cast($scaleId));
                        }
=======
>>>>>>> laraxot/dev
                        $delivery = app(ReceiveDeliveryAction::class)->execute(
                            Fir::query()->findOrFail(SafeIntCastAction::cast($data['fir_id'] ?? null)),
                            OperatorSite::query()->findOrFail(SafeIntCastAction::cast($data['plant_site_id'] ?? null)),
                            Warehouse::query()->findOrFail(SafeIntCastAction::cast($data['warehouse_id'] ?? null)),
                            $gross === null || $gross === '' ? null : SafeFloatCastAction::cast($gross),
<<<<<<< HEAD
                            $scale,
=======
                            $scaleId === null || $scaleId === '' ? null : Scale::query()->findOrFail(SafeIntCastAction::cast($scaleId)),
>>>>>>> laraxot/dev
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
     * FIR non ancora conferiti (o solo respinti), piu' recenti per primi: id => numero.
     *
     * @return array<int, string>
     */
    private static function firOptions(): array
    {
        $received = Delivery::query()->where('status', '!=', 'rejected')->pluck('fir_id')->all();

<<<<<<< HEAD
        /** @var Collection<int, Fir> $firs */
        $firs = Fir::query()
            ->whereNotIn('id', $received)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $firs
=======
        return Fir::query()
            ->whereNotIn('id', $received)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
>>>>>>> laraxot/dev
            ->mapWithKeys(static fn (Fir $fir): array => [$fir->id => (string) ($fir->number ?? '#'.$fir->id)])
            ->all();
    }
}
