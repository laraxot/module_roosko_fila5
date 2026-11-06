<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Widgets;

use Modules\Roosko\Models\WasteLot;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Roosko\Actions\GetPlantStockSummaryAction;
use Modules\Roosko\Datas\StockSummaryRowData;
use Modules\Warehouse\Models\Warehouse;
use Modules\Xot\Filament\Widgets\XotBaseStatsOverviewWidget;

/**
 * Uso dei limiti istantanei autorizzati: una stat per codice EER con limite, ordinate dalla
 * piu' vicina al limite. Colore dalle soglie `roosko.authorization_usage_thresholds`.
 */
class AuthorizationUsageWidget extends XotBaseStatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteLot::class) ?? false;
    }

    protected static ?int $sort = 2;

    /** Quante stat mostrare al massimo (le piu' critiche). */
    private const int MAX_STATS = 8;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $rows = collect();
        foreach (Warehouse::query()->where('is_active', true)->get() as $warehouse) {
            $rows = $rows->merge(app(GetPlantStockSummaryAction::class)->execute($warehouse));
        }

        return $rows
            ->filter(static fn (StockSummaryRowData $row): bool => $row->usagePct !== null)
            ->sortByDesc(static fn (StockSummaryRowData $row): float => (float) $row->usagePct)
            ->take(self::MAX_STATS)
            ->map(fn (StockSummaryRowData $row): Stat => Stat::make(
                $row->ewcCode.($row->isHazardous ? '*' : ''),
                number_format((float) $row->usagePct, 1, ',', '.').' %',
            )
                ->description($this->description($row))
                ->color(StockSummaryRowData::configuredUsageColor($row->usagePct)))
            ->values()
            ->all();
    }

    private function description(StockSummaryRowData $row): string
    {
        $text = __('roosko::authorization_usage.stats.description', [
            'stock' => number_format($row->stockKg, 0, ',', '.'),
            'limit' => number_format((float) $row->authorizedMaxKg, 0, ',', '.'),
        ]);

        return \is_string($text) ? $text : '';
    }
}
