<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Roosko\Actions\GetPlantStockSummaryAction;
use Modules\Roosko\Datas\StockSummaryRowData;
use Modules\Warehouse\Models\Warehouse;
use Modules\Xot\Filament\Widgets\XotBaseTableWidget;

/**
 * Giacenza per codice EER di tutti i magazzini attivi, con limite autorizzato e % d'uso
 * (GetPlantStockSummaryAction). Tabella a dati custom (`records()`): le righe non sono modelli.
 */
class PlantStockByEwcWidget extends XotBaseTableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows())
            ->columns(array_values($this->getTableColumns()))
            ->paginated(false);
    }

    /**
     * @return array<string, TextColumn|IconColumn>
     */
    #[\Override]
    protected function getTableColumns(): array
    {
        return [
            'warehouse' => TextColumn::make('warehouse'),
            'ewc_code' => TextColumn::make('ewc_code'),
            'description' => TextColumn::make('description')->limit(60)->wrap(),
            'is_hazardous' => IconColumn::make('is_hazardous')->boolean(),
            'units_count' => TextColumn::make('units_count')->numeric(),
            'stock_kg' => TextColumn::make('stock_kg')->numeric(3),
            'authorized_max_kg' => TextColumn::make('authorized_max_kg')->numeric(3),
            'usage_pct' => TextColumn::make('usage_pct')
                ->numeric(1)
                ->suffix(' %')
                ->badge()
                ->color(static fn (mixed $state): string => StockSummaryRowData::configuredUsageColor(is_numeric($state) ? (float) $state : null)),
        ];
    }

    /**
     * @return array<string, array<string, string|int|float|bool|null>>
     */
    private function rows(): array
    {
        $rows = [];
        /** @var \Illuminate\Database\Eloquent\Collection<int, Warehouse> $warehouses */
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('code')->get();
        foreach ($warehouses as $warehouse) {
            /** @var Collection<int, StockSummaryRowData> $summary */
            $summary = app(GetPlantStockSummaryAction::class)->execute($warehouse);
            foreach ($summary as $row) {
                $rows[$row->key()] = [
                    'warehouse' => $warehouse->code,
                    'ewc_code' => $row->ewcCode.($row->isHazardous ? '*' : ''),
                    'description' => $row->description,
                    'is_hazardous' => $row->isHazardous,
                    'units_count' => $row->unitsCount,
                    'stock_kg' => $row->stockKg,
                    'authorized_max_kg' => $row->authorizedMaxKg,
                    'usage_pct' => $row->usagePct,
                ];
            }
        }

        return $rows;
    }
}
