<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Modules\Roosko\Actions\RunProductionChecksAction;
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
        $this->disableNativeProcessingMaterialActions();
        $this->configureDateFormats();
        $this->registerSchedulerHeartbeat();
        $this->registerErrorViews();
    }

    /**
     * Pagine d'errore di Roosko davanti a quelle del tema pubblico, che erano di un altro prodotto. Laravel
     * cerca `errors::{codice}` nelle cartelle `errors` di `view.paths`: qui c'e' solo `errors/`, quindi le
     * altre viste non cambiano.
     */
    private function registerErrorViews(): void
    {
        /** @var list<string> $paths */
        $paths = config('view.paths', []);
        $errorViews = module_path('Roosko', 'resources/error-views');
        if (! \in_array($errorViews, $paths, true)) {
            config(['view.paths' => [$errorViews, ...$paths]]);
        }
    }

    /**
     * Battito dello scheduler: `roosko:production-check` segnala se il cron di `schedule:run` non gira
     * (senza, gli esiti RENTRI non vengono mai verificati).
     */
    private function registerSchedulerHeartbeat(): void
    {
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->call(static fn (): bool => Cache::put(RunProductionChecksAction::SCHEDULER_HEARTBEAT_KEY, now()->toIso8601String(), 3600))
                ->name('roosko:scheduler-heartbeat')
                ->everyMinute();
        });
    }

    /**
     * Date e ore all'italiana in tutte le tabelle e le schede di Filament: il default di Filament
     * e' all'americana (`nov 10, 2026`), poco leggibile per chi compila registri e FIR.
     */
    private function configureDateFormats(): void
    {
        Table::configureUsing(static function (Table $table): void {
            $table->defaultDateDisplayFormat('d/m/Y')
                ->defaultDateTimeDisplayFormat('d/m/Y H:i')
                ->defaultTimeDisplayFormat('H:i');
        });
        Schema::configureUsing(static function (Schema $schema): void {
            $schema->defaultDateDisplayFormat('d/m/Y')
                ->defaultDateTimeDisplayFormat('d/m/Y H:i')
                ->defaultTimeDisplayFormat('H:i');
        });
    }

    /**
     * In Roosko il materiale di un trattamento passa solo dalle actions di Roosko (scarico di
     * registro, autorizzazione, lotto e QR): si spengono "Aggiungi input" e "Registra output"
     * del pannello Processing (punto di estensione `processing.material_actions_enabled`).
     */
    private function disableNativeProcessingMaterialActions(): void
    {
        config(['processing.material_actions_enabled' => false]);
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
