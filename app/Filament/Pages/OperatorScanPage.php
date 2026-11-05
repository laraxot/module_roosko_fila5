<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Modules\Processing\Models\Machine;
use Modules\Roosko\Actions\AddShipmentItemAction;
use Modules\Roosko\Actions\GetLotStockKgAction;
use Modules\Roosko\Actions\QueueUnitLabelAction;
use Modules\Roosko\Actions\ResolveScannedCodeAction;
use Modules\Roosko\Actions\ResolveWasteLotAction;
use Modules\Roosko\Actions\StartTreatmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Actions\AdjustStockUnitWeightAction;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\EwcCode;
use Modules\Weighing\Exceptions\WeighingException;
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Modules\Xot\Actions\Cast\SafeStringCastAction;
use Modules\Xot\Filament\Pages\XotBasePage;

/**
 * UI operatore di piazzale (mobile-first).
 *
 * Un campo di scansione sempre a fuoco riceve i codici dai lettori barcode/RFID in modalita'
 * keyboard wedge (palmari Zebra/Chainway: il lettore "digita" il codice e un Invio); il bottone
 * fotocamera usa il componente Alpine `roosko-scanner` (npm `qr-scanner`). Una scansione mostra
 * la scheda dell'unita' o, se e' una posizione, le unita' presenti.
 *
 * Stato tra le richieste: solo id (locked, non modificabili dal client) e la fase "attendo la
 * posizione" per lo stoccaggio a due scansioni; unita' e posizione si rileggono a ogni richiesta.
 * Tutte le operazioni chiamano le Actions di dominio.
 */
class OperatorScanPage extends XotBasePage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?int $navigationSort = 1;

    /** URL stabile `/roosko/admin/operator`: e' lo `start_url` del manifest PWA. */
    protected static ?string $slug = 'operator';

    protected string $view = 'roosko::filament.pages.operator-scan';

    public string $scan = '';

    #[Locked]
    public ?int $unitId = null;

    #[Locked]
    public ?int $locationId = null;

    /** `location` quando dopo "Stocca/Sposta" si attende la scansione della posizione. */
    #[Locked]
    public ?string $awaiting = null;

    public static function getNavigationLabel(): string
    {
        return static::trans('navigation.label');
    }

    public function getTitle(): string|Htmlable
    {
        return static::trans('title');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::trans('navigation.group');
    }

    /** Invio dal campo di scansione (lettore keyboard wedge o tastiera). */
    public function submitScan(): void
    {
        $code = $this->scan;
        $this->scan = '';
        $this->handleScan($code);
    }

    /** Scansione dalla fotocamera (componente Alpine) o dal campo. */
    public function handleScan(string $code): void
    {
        $resolved = app(ResolveScannedCodeAction::class)->execute(mb_substr($code, 0, 512));

        if ($this->awaiting === 'location') {
            $this->storeAt($resolved);

            return;
        }

        if ($resolved instanceof StockUnit) {
            $this->unitId = $resolved->id;
            $this->locationId = null;

            return;
        }

        if ($resolved instanceof Location) {
            $this->locationId = $resolved->id;
            $this->unitId = null;

            return;
        }

        $this->notify('danger', 'scan.not_found', ['code' => trim($code)]);
    }

    /** "Stocca/Sposta": la prossima scansione e' la posizione di destinazione. */
    public function startStore(): void
    {
        if ($this->unit() instanceof StockUnit) {
            $this->awaiting = 'location';
        }
    }

    public function cancelPending(): void
    {
        $this->awaiting = null;
    }

    /** Dalla lista delle unita' di una posizione. */
    public function selectUnit(int $unitId): void
    {
        $unit = StockUnit::query()->find($unitId);
        if ($unit instanceof StockUnit && $this->locationId !== null && $unit->location_id === $this->locationId) {
            $this->unitId = $unit->id;
            $this->locationId = null;
        }
    }

    public function clear(): void
    {
        $this->unitId = null;
        $this->locationId = null;
        $this->awaiting = null;
    }

    public function unit(): ?StockUnit
    {
        return $this->unitId === null ? null : StockUnit::query()->find($this->unitId);
    }

    public function lot(): ?WasteLot
    {
        $unit = $this->unit();

        return $unit instanceof StockUnit ? app(ResolveWasteLotAction::class)->find($unit) : null;
    }

    public function scannedLocation(): ?Location
    {
        return $this->locationId === null ? null : Location::query()->find($this->locationId);
    }

    /**
     * @return Collection<int, StockUnit>
     */
    public function locationUnits(): Collection
    {
        return StockUnit::query()
            ->where('location_id', $this->locationId ?? 0)
            ->whereIn('status', GetLotStockKgAction::occupyingStatuses())
            ->orderBy('code')
            ->get();
    }

    public function ewcLabel(?WasteLot $lot): ?string
    {
        if (! $lot instanceof WasteLot) {
            return null;
        }
        $ewc = EwcCode::query()->find($lot->ewc_code_id);

        return $ewc instanceof EwcCode ? $ewc->code.($ewc->is_hazardous ? '*' : '') : null;
    }

    public function startTreatmentAction(): Action
    {
        return Action::make('startTreatment')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('warning')
            ->schema([
                'machine_id' => Select::make('machine_id')
                    ->options(static fn (): array => Machine::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->required(),
                'operation' => Select::make('operation')->options(WasteOperationEnum::class)->required(),
            ])
            ->action(function (array $data): void {
                $unit = $this->unit();
                if (! $unit instanceof StockUnit) {
                    return;
                }
                $operation = $data['operation'] ?? null;
                $this->runOperation(function () use ($unit, $data, $operation): void {
                    app(StartTreatmentAction::class)->execute(
                        Machine::query()->findOrFail(SafeIntCastAction::cast($data['machine_id'] ?? null)),
                        $operation instanceof WasteOperationEnum ? $operation : WasteOperationEnum::from(SafeStringCastAction::cast($operation)),
                        collect([$unit]),
                    );
                }, 'treatment_started', ['code' => $unit->code]);
            })
            ->visible(fn (): bool => $this->unit()?->status === StockUnitStatusEnum::STORED);
    }

    public function addToShipmentAction(): Action
    {
        return Action::make('addToShipment')
            ->icon('heroicon-o-truck')
            ->color('primary')
            ->schema([
                'shipment_id' => Select::make('shipment_id')
                    ->options(fn (): array => Shipment::query()
                        ->where('warehouse_id', $this->unit()?->warehouse_id ?? 0)
                        ->whereIn('status', [ShipmentStatusEnum::DRAFT->value, ShipmentStatusEnum::LOADING->value, ShipmentStatusEnum::READY->value])
                        ->orderByDesc('id')
                        ->pluck('code', 'id')
                        ->all())
                    ->required(),
            ])
            ->action(function (array $data): void {
                $unit = $this->unit();
                if (! $unit instanceof StockUnit) {
                    return;
                }
                $shipment = Shipment::query()->findOrFail(SafeIntCastAction::cast($data['shipment_id'] ?? null));
                $this->runOperation(
                    static fn () => app(AddShipmentItemAction::class)->execute($shipment, $unit),
                    'added_to_shipment',
                    ['code' => $unit->code, 'shipment' => $shipment->code],
                );
            })
            ->visible(fn (): bool => \in_array($this->unit()?->status, [StockUnitStatusEnum::STORED, StockUnitStatusEnum::RESERVED], true));
    }

    public function reprintLabelAction(): Action
    {
        return Action::make('reprintLabel')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->schema([
                'copies' => TextInput::make('copies')->numeric()->integer()->minValue(1)->maxValue(10)->default(1)->required(),
            ])
            ->action(function (array $data): void {
                $unit = $this->unit();
                if (! $unit instanceof StockUnit) {
                    return;
                }
                $copies = SafeIntCastAction::cast($data['copies'] ?? 1, 1);
                $this->runOperation(static fn () => app(QueueUnitLabelAction::class)->execute($unit, $copies), 'label_queued', ['code' => $unit->code]);
            })
            ->visible(fn (): bool => $this->unit() instanceof StockUnit);
    }

    public function adjustWeightAction(): Action
    {
        return Action::make('adjustWeight')
            ->icon('heroicon-o-scale')
            ->color('gray')
            ->fillForm(fn (): array => ['new_net_kg' => $this->unit()?->net_weight_kg])
            ->schema([
                'new_net_kg' => TextInput::make('new_net_kg')->numeric()->minValue(0)->step(0.001)->suffix('kg')->required(),
                'reason' => TextInput::make('reason')->maxLength(255)->required(),
            ])
            ->action(function (array $data): void {
                $unit = $this->unit();
                if (! $unit instanceof StockUnit) {
                    return;
                }
                $kg = SafeFloatCastAction::cast($data['new_net_kg'] ?? null);
                $reason = SafeStringCastAction::cast($data['reason'] ?? '');
                $this->runOperation(
                    static fn () => app(AdjustStockUnitWeightAction::class)->execute($unit, $kg, $reason),
                    'weight_adjusted',
                    ['code' => $unit->code],
                );
            })
            ->visible(fn (): bool => \in_array($this->unit()?->status, [StockUnitStatusEnum::STORED, StockUnitStatusEnum::RESERVED, StockUnitStatusEnum::PENDING], true));
    }

    private function storeAt(StockUnit|Location|null $resolved): void
    {
        $unit = $this->unit();
        if (! $resolved instanceof Location || ! $unit instanceof StockUnit) {
            $this->notify('danger', 'scan.location_expected');

            return;
        }

        $this->awaiting = null;
        $this->runOperation(
            static fn () => app(StoreLotAction::class)->execute($unit, $resolved),
            'stored',
            ['code' => $unit->code, 'location' => $resolved->code],
        );
    }

    /**
     * @param  \Closure(): mixed  $operation
     * @param  array<string, string>  $replace
     */
    private function runOperation(\Closure $operation, string $successKey, array $replace): void
    {
        try {
            $operation();
        } catch (\DomainException|InvalidArgumentException|WeighingException $exception) {
            Notification::make()->danger()->title(static::trans('notifications.error'))->body($exception->getMessage())->send();

            return;
        }

        $this->notify('success', 'notifications.'.$successKey, $replace);
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function notify(string $status, string $key, array $replace = []): void
    {
        Notification::make()->status($status)->title(static::trans($key, false, $replace))->send();
    }
}
