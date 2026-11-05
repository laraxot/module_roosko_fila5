<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Pages;

use Modules\Roosko\Filament\Customer\Widgets\CustomerLatestDeliveriesWidget;
use Modules\Roosko\Filament\Customer\Widgets\CustomerMonthlyKgWidget;
use Modules\Xot\Filament\Pages\XotBaseDashboard;

/**
 * Home dell'area clienti: kg conferiti nel mese per cantiere e ultimi conferimenti.
 * Testi in `lang/{it,en}/customer_dashboard.php`.
 */
class CustomerDashboard extends XotBaseDashboard
{
    /**
     * @return array<class-string>
     */
    #[\Override]
    public function getWidgets(): array
    {
        return [
            CustomerMonthlyKgWidget::class,
            CustomerLatestDeliveriesWidget::class,
        ];
    }

    #[\Override]
    public function getColumns(): int|array
    {
        return 1;
    }

    #[\Override]
    public static function getNavigationLabel(): string
    {
        return self::text('navigation.label');
    }

    #[\Override]
    public function getTitle(): string
    {
        return self::text('title');
    }

    private static function text(string $key): string
    {
        $text = __('roosko::customer_dashboard.'.$key);

        return \is_string($text) ? $text : $key;
    }
}
