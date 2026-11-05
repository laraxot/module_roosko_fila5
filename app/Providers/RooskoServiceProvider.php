<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers;

use Modules\Roosko\Guards\WasteAuthorizationPlacementGuard;
use Modules\Xot\Providers\XotBaseServiceProvider;
use Override;

class RooskoServiceProvider extends XotBaseServiceProvider
{
    public string $name = 'Roosko';

    protected string $module_dir = __DIR__;

    protected string $module_ns = __NAMESPACE__;

    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->registerPlacementGuard();
    }

    #[Override]
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path('roosko.php'),
        ], 'config');

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), 'roosko');
    }

    /**
     * Aggancia la regola delle autorizzazioni al punto di estensione di Warehouse.
     * Idempotente: il provider puo' essere avviato piu' volte (test, Octane).
     */
    private function registerPlacementGuard(): void
    {
        $guards = config('warehouse.placement_guards', []);
        if (\is_array($guards) && \in_array(WasteAuthorizationPlacementGuard::class, $guards, true)) {
            return;
        }

        config()->push('warehouse.placement_guards', WasteAuthorizationPlacementGuard::class);
    }
}
