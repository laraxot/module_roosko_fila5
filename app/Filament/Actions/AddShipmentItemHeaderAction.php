<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\TextInput;
use Modules\Roosko\Actions\AddShipmentItemAction;
use Modules\Roosko\Actions\ResolveScannedCodeAction;
use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Roosko\Models\Shipment;
use Modules\Warehouse\Models\StockUnit;
use Modules\Xot\Actions\Cast\SafeStringCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * Carica un'unita' sull'uscita digitando o scansionando il suo codice (AddShipmentItemAction).
 * Si usa come header action del relation manager delle righe: l'uscita e' l'owner record.
 *
 * Etichetta/icona: `shipment_item.actions.load`; testi: `add_shipment_item_header.php`.
 */
class AddShipmentItemHeaderAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    protected ?Shipment $shipment = null;

    public static function getDefaultName(): ?string
    {
        return 'load';
    }

    public function shipment(Shipment $shipment): static
    {
        $this->shipment = $shipment;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-qr-code')
            ->color('primary')
            ->modalHeading(static fn (): string => static::transAction('modal_heading'))
            ->schema([
                'unit_code' => TextInput::make('unit_code')->autofocus()->required(),
            ])
            ->action(function (array $data): void {
                $shipment = $this->shipment;
                if (! $shipment instanceof Shipment) {
                    return;
                }
                $code = SafeStringCastAction::cast($data['unit_code'] ?? '');
                $this->runDomainAction(
                    static function () use ($shipment, $code): void {
                        $unit = app(ResolveScannedCodeAction::class)->execute($code);
                        if (! $unit instanceof StockUnit) {
                            throw ShipmentException::unitNotInStock($code);
                        }
                        app(AddShipmentItemAction::class)->execute($shipment, $unit);
                    },
                    static::transAction('success', ['code' => $code]),
                );
            })
            ->visible(fn (): bool => $this->shipment instanceof Shipment && $this->shipment->status->acceptsItems());
    }
}
