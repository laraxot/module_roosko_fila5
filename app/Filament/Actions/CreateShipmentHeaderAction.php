<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Modules\Roosko\Actions\CreateShipmentAction;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Filament\Resources\ShipmentResource;
use Modules\Roosko\Models\Shipment;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Models\Warehouse;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Nuova uscita in bozza (CreateShipmentAction), poi si caricano le unita'.
 *
 * Etichetta/icona: `shipment.actions.create_shipment`; testi: `shipment_header.php` (GetTransKeyAction toglie il prefisso `create_` dal nome della classe).
 */
class CreateShipmentHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'create_shipment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-truck')
            ->authorize('create')
            ->color('primary')
            ->modalHeading(static fn (): string => static::transAction('modal_heading'))
            ->schema([
                'warehouse_id' => Select::make('warehouse_id')
                    ->options(static fn (): array => Warehouse::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->required(),
                'recipient_operator_id' => Select::make('recipient_operator_id')
                    ->options(static fn (): array => Operator::query()->where('is_active', true)->orderBy('business_name')->pluck('business_name', 'id')->all())
                    ->searchable()
                    ->live()
                    ->required(),
                'recipient_site_id' => Select::make('recipient_site_id')
                    ->options(static fn (Get $get): array => OperatorSite::query()
                        ->where('operator_id', SafeIntCastAction::cast($get('recipient_operator_id')))
                        ->orderBy('name')->pluck('name', 'id')->all()),
                'document_type' => Select::make('document_type')
                    ->options(ShipmentDocumentEnum::class)
                    ->default(ShipmentDocumentEnum::FIR->value)
                    ->live()
                    ->required(),
                'operation' => Select::make('operation')
                    ->options(WasteOperationEnum::class)
                    ->visible(static fn (Get $get): bool => self::isFir($get('document_type')))
                    ->required(static fn (Get $get): bool => self::isFir($get('document_type'))),
                'document_number' => TextInput::make('document_number')->maxLength(64),
                'notes' => Textarea::make('notes')->rows(2),
            ])
            ->action(function (array $data): void {
                $shipment = null;
                $this->runDomainAction(
                    static function () use ($data, &$shipment): void {
                        $site = $data['recipient_site_id'] ?? null;
                        $operation = $data['operation'] ?? null;
                        $document = $data['document_type'] ?? null;
                        $number = $data['document_number'] ?? null;
                        $notes = $data['notes'] ?? null;
                        $shipment = app(CreateShipmentAction::class)->execute(
                            Warehouse::query()->findOrFail(SafeIntCastAction::cast($data['warehouse_id'] ?? null)),
                            Operator::query()->findOrFail(SafeIntCastAction::cast($data['recipient_operator_id'] ?? null)),
                            $document instanceof ShipmentDocumentEnum ? $document : ShipmentDocumentEnum::from(\is_string($document) ? $document : ''),
                            $operation instanceof WasteOperationEnum ? $operation : (\is_string($operation) && $operation !== '' ? WasteOperationEnum::from($operation) : null),
                            $site === null || $site === '' ? null : OperatorSite::query()->findOrFail(SafeIntCastAction::cast($site)),
                            \is_string($number) ? $number : null,
                            \is_string($notes) ? $notes : null,
                        );
                    },
                    static::transAction('success'),
                );

                if ($shipment instanceof Shipment) {
                    $this->redirect(ShipmentResource::getUrl('view', ['record' => $shipment]));
                }
            });
    }

    private static function isFir(mixed $documentType): bool
    {
        return $documentType === ShipmentDocumentEnum::FIR || $documentType === ShipmentDocumentEnum::FIR->value;
    }
}
