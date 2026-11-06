<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Collection;
use Modules\Processing\Enums\OutputKindEnum;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Actions\GetLocationOptionsAction;
use Modules\Roosko\Actions\RegisterTreatmentOutputAction;
use Modules\Roosko\Actions\ResolveOrderWarehouseAction;
use Modules\Roosko\Datas\TreatmentOutputData;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Models\EwcCode;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * "Registra prodotto" su un trattamento in corso (RegisterTreatmentOutputAction): contenuto del
 * lotto (EER, stato fisico, HP, classificazione, destino) e contenitore (tipo, pesi, posizione).
 * Le classi HP compaiono solo per un EER pericoloso, il codice di lotto EoW solo per End of Waste;
 * gli altri vincoli di dominio li verifica la action.
 *
 * Etichetta/icona: `treatment.actions.register_product`; testi: `register_treatment_product_table.php`.
 */
class RegisterTreatmentProductTableAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'register_product';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-plus-circle')
            ->authorize('registerOutput')
            ->color('success')
            ->button()
            ->modalWidth('3xl')
            ->modalHeading(static fn (ProcessingOrder $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->modalDescription(static fn (): string => static::transAction('modal_description'))
            ->fillForm(static fn (): array => ['kind' => OutputKindEnum::PRODUCT->value])
            ->schema(static fn (ProcessingOrder $record): array => [
                'content' => Grid::make(2)->schema([
                    'ewc_code_id' => Select::make('ewc_code_id')
                        ->options(static function (): array {
                            /** @var Collection<int, EwcCode> $ewcCodes */
                            $ewcCodes = EwcCode::query()
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get();

                            return $ewcCodes
                                ->mapWithKeys(static fn (EwcCode $ewc): array => [$ewc->id => $ewc->display_code.' – '.$ewc->description])
                                ->all();
                        })
                        ->searchable()
                        ->live()
                        ->required(),
                    'physical_state' => Select::make('physical_state')->options(PhysicalStateEnum::class)->required(),
                    'hazard_properties' => Select::make('hazard_properties')
                        ->options(HazardPropertyEnum::class)
                        ->multiple()
                        ->visible(static fn (Get $get): bool => self::isHazardousEwc($get('ewc_code_id')))
                        ->required(static fn (Get $get): bool => self::isHazardousEwc($get('ewc_code_id')))
                        ->columnSpanFull(),
                    'classification' => Select::make('classification')->options(LotClassificationEnum::class)->live()->required(),
                    'destination' => Select::make('destination')->options(LotDestinationEnum::class)->required(),
                    'eow_batch_code' => TextInput::make('eow_batch_code')
                        ->maxLength(64)
                        ->visible(static fn (Get $get): bool => self::isEndOfWaste($get('classification')))
                        ->required(static fn (Get $get): bool => self::isEndOfWaste($get('classification'))),
                    'kind' => Select::make('kind')->options(OutputKindEnum::class)->required(),
                ]),
                'container' => Grid::make(4)->schema([
                    'container_type' => Select::make('container_type')->options(ContainerTypeEnum::class)->required(),
                    'net_weight_kg' => TextInput::make('net_weight_kg')->numeric()->minValue(0.001)->step(0.001)->suffix('kg')->required(),
                    'tare_weight_kg' => TextInput::make('tare_weight_kg')->numeric()->minValue(0)->step(0.001)->suffix('kg'),
                    'location_id' => Select::make('location_id')
                        ->options(static function () use ($record): array {
                            $warehouse = app(ResolveOrderWarehouseAction::class)->find($record);

                            return $warehouse instanceof Warehouse ? app(GetLocationOptionsAction::class)->execute($warehouse->id) : [];
                        })
                        ->searchable(),
                ]),
            ])
            ->action(function (array $data, ProcessingOrder $record): void {
                /** @var array<string, mixed> $formData */
                $formData = $data;
                $this->runDomainAction(
                    static fn (): StockUnit => app(RegisterTreatmentOutputAction::class)->execute($record, TreatmentOutputData::fromFormState($formData)),
                    static fn (StockUnit $unit): string => static::transAction('success', ['code' => $unit->code, 'order' => $record->code]),
                );
            })
            ->visible(static fn (ProcessingOrder $record): bool => $record->status->acceptsMaterial());
    }

    private static function isHazardousEwc(mixed $ewcCodeId): bool
    {
        return is_numeric($ewcCodeId)
            && EwcCode::query()->whereKey((int) $ewcCodeId)->where('is_hazardous', true)->exists();
    }

    private static function isEndOfWaste(mixed $classification): bool
    {
        $value = $classification instanceof LotClassificationEnum ? $classification : LotClassificationEnum::tryFrom(\is_string($classification) ? $classification : '');

        return $value === LotClassificationEnum::END_OF_WASTE;
    }
}
