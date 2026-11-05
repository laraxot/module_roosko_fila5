<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Modules\Roosko\Actions\ResolveWasteLotAction;
use Modules\Roosko\Filament\Actions\AddShipmentItemHeaderAction;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Models\EwcCode;
use Modules\Warehouse\Models\StockUnit;
use Modules\Xot\Filament\Resources\RelationManagers\XotBaseRelationManager;

/**
 * Righe dell'uscita: carico con scansione/codice (AddShipmentItemHeaderAction), rimozione
 * finche' l'uscita e' aperta.
 */
class ShipmentItemsRelationManager extends XotBaseRelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * @return array<string, Column>
     */
    #[\Override]
    public function getTableColumns(): array
    {
        return [
            'unit_code' => TextColumn::make('stockUnit.code'),
            'ewc_code' => TextColumn::make('ewc_code')->state(static fn (ShipmentItem $record): ?string => self::ewc($record)),
            'quantity_kg' => TextColumn::make('quantity_kg')->numeric(3),
            'unit_status' => TextColumn::make('stockUnit.status')->badge(),
        ];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableHeaderActions(): array
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof Shipment
            ? ['load' => AddShipmentItemHeaderAction::make()->shipment($owner)]
            : [];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableActions(): array
    {
        return [
            'delete' => DeleteAction::make()->visible(static fn (Model $record): bool => $record instanceof ShipmentItem && $record->shipment->status->acceptsItems()),
        ];
    }

    /**
     * @return array<string, never>
     */
    #[\Override]
    public function getTableBulkActions(): array
    {
        return [];
    }

    private static function ewc(ShipmentItem $item): ?string
    {
        $unit = StockUnit::query()->find($item->stock_unit_id);
        $lot = $unit instanceof StockUnit ? app(ResolveWasteLotAction::class)->find($unit) : null;

        return $lot instanceof WasteLot ? EwcCode::query()->find($lot->ewc_code_id)?->code : null;
    }
}
