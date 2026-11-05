<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers\Filament;

use Filament\Panel;
use Modules\Xot\Providers\Filament\XotBasePanelProvider;
use Override;

/**
 * Area clienti `roosko::customer` (path `roosko/customer/{cliente}`): il cliente dell'impianto
 * vede solo i suoi cantieri, conferimenti e formulari.
 *
 * Tenancy Filament: tenant = `XotData::getTenantClass()` (Tenant del modulo User) per `slug`;
 * entrano gli utenti con ruolo `roosko::customer` membri del cliente (`tenant_user`).
 * Componenti solo da `app/Filament/Customer`: nessuna resource amministrativa del modulo.
 * Il legame record → cliente e' indiretto (record → soggetto Waste → cliente): lo scope e'
 * nelle resource (`CustomerBaseResource`), non nello scope automatico di Filament.
 * Nessuna registrazione del cliente: i clienti li crea l'amministratore nel modulo User.
 */
class CustomerPanelProvider extends XotBasePanelProvider
{
    protected string $module = 'Roosko';

    protected ?string $panelId = 'roosko::customer';

    protected ?string $panelPath = 'roosko/customer';

    protected bool $hasTenancy = true;

    protected ?string $componentsDirectory = 'Customer';

    #[Override]
    public function panel(Panel $panel): Panel
    {
        // Roosko e' il main module: senza `default(false)` anche l'area clienti si candiderebbe
        // come pannello predefinito accanto a `roosko::admin`.
        return parent::panel($panel)
            ->default(false)
            ->brandName(static function (): string {
                $brand = __('roosko::customer_panel.brand');

                return \is_string($brand) ? $brand : 'Roosko';
            });
    }
}
