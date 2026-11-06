<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\RelationManagers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Roosko\Actions\ResolveWasteLotAction;
use Modules\Roosko\Filament\Actions\AddShipmentItemHeaderAction;
use Modules\Roosko\Filament\Actions\RemoveShipmentItemTableAction;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Models\StockUnit;
use Modules\Xot\Filament\Resources\RelationManagers\XotBaseRelationManager;

/**
 * Righe dell'uscita: carico con scansione/codice (AddShipmentItemHeaderAction), rimozione
 * finche' l'uscita e' aperta (RemoveShipmentItemTableAction, che libera l'unita').
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
            'remove' => RemoveShipmentItemTableAction::make(),
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

    /**
     * Unita', lotto ed EER in eager loading: una query per tipo e per pagina (audit R13).
     */
    #[\Override]
    public function table(Table $table): Table
    {
        return parent::table($table)->modifyQueryUsing(static fn (Builder $query): Builder => $query->with(['stockUnit.subject' => static function (Relation $relation): void {
            if ($relation instanceof MorphTo) {
                $relation->morphWith([WasteLot::class => ['ewcCode']]);
            }
        }]));
    }

    private static function ewc(ShipmentItem $item): ?string
    {
        $unit = $item->stockUnit;
        $lot = $unit instanceof StockUnit ? app(ResolveWasteLotAction::class)->find($unit) : null;

        return $lot instanceof WasteLot ? $lot->ewcCode?->code : null;
    }
}
