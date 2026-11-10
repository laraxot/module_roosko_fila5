<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers\Filament;

use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Log;
use Modules\Roosko\Filament\Pages\OperatorScanPage;
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
        // Pannello del personale dell'impianto: nessuna tenancy. L'unico pannello tenant-aware e'
        // l'area clienti `roosko::customer` (story 01.area-cliente-cantieri). Con `->tenant()` qui il
        // personale, che non e' membro di un cliente, non poteva aprire dashboard e pagina operatore.
        $panel = parent::panel($panel);

        return $panel
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                static fn (): string => view('roosko::filament.pages.partials.pwa-head', [
                    'manifestUrl' => asset('assets/roosko/manifest.webmanifest'),
                    'serviceWorkerUrl' => route('roosko.service-worker'),
                    'scannerScript' => self::scannerScript(),
                ])->render(),
                scopes: OperatorScanPage::class,
            );
    }

    /**
     * Script della fotocamera e della scansione. Senza la build (`npm run build` nel modulo) la pagina
     * operatore andava in 500 per il manifest Vite mancante: ora si apre lo stesso, si scansiona col
     * palmare o a mano, e nel log resta l'avviso per chi installa.
     */
    private static function scannerScript(): string
    {
        if (! is_file(public_path('assets/roosko/manifest.json'))) {
            Log::warning('Roosko: build Vite della pagina operatore assente (npm run build in Modules/Roosko); fotocamera non disponibile.');

            return '';
        }

        return app(Vite::class)(['resources/js/scanner.js'], 'assets/roosko')->toHtml();
    }
}
