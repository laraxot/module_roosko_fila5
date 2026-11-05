<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Pages;

use Modules\Roosko\Filament\Widgets\AuthorizationUsageWidget;
use Modules\Roosko\Filament\Widgets\PlantStockByEwcWidget;
use Modules\Roosko\Filament\Widgets\TodayDeliveriesWidget;
use Modules\Xot\Filament\Pages\XotBaseDashboard;

/**
 * Dashboard dell'impianto: uso delle autorizzazioni, giacenza per EER, conferimenti del giorno.
 */
class Dashboard extends XotBaseDashboard
{
    /**
     * @return array<class-string>
     */
    #[\Override]
    public function getWidgets(): array
    {
        return [
            AuthorizationUsageWidget::class,
            PlantStockByEwcWidget::class,
            TodayDeliveriesWidget::class,
        ];
    }

    #[\Override]
    public function getColumns(): int|array
    {
        return 1;
    }
}
