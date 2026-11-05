<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers\Filament;

use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Vite;
use Modules\Roosko\Filament\Pages\OperatorScanPage;
<<<<<<< HEAD
use Modules\Xot\Datas\XotData;
=======
>>>>>>> laraxot/dev
use Modules\Xot\Providers\Filament\XotBasePanelProvider;
use Override;

/**
 * Pannello di prodotto `roosko::admin` (path `roosko/admin`): dashboard dell'impianto,
 * resource di conferimenti/lotti/uscite/regole e la UI operatore mobile.
 *
 * Gli asset della UI operatore (bundle vite `scanner.js`, manifest PWA, registrazione del
 * service worker) si caricano solo sulla pagina dell'operatore.
 */
class AdminPanelProvider extends XotBasePanelProvider
{
    protected string $module = 'Roosko';

    #[Override]
    public function panel(Panel $panel): Panel
    {
<<<<<<< HEAD
        $panel = parent::panel($panel);
        $tenant_class = XotData::make()->getTenantClass();
        $panel->tenant($tenant_class, slugAttribute: 'slug');

        return $panel
=======
        return parent::panel($panel)
>>>>>>> laraxot/dev
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                static fn (): string => view('roosko::filament.pages.partials.pwa-head', [
                    'manifestUrl' => asset('assets/roosko/manifest.webmanifest'),
                    'serviceWorkerUrl' => route('roosko.service-worker'),
                    'scannerScript' => app(Vite::class)(['resources/js/scanner.js'], 'assets/roosko')->toHtml(),
                ])->render(),
                scopes: OperatorScanPage::class,
            );
    }
}
