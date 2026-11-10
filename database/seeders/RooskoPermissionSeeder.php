<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\Permission;
use Modules\User\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permessi delle policy di Roosko e ruoli operativi del prodotto. Idempotente (findOrCreate,
 * syncPermissions), guard `web`.
 *
 * I nomi sono quelli che le policy controllano (`{modello}.{abilita'}`), anche per i modelli
 * degli altri moduli: ogni permesso assegnato a un ruolo si crea qui se manca, cosi' il seeder
 * non dipende dall'ordine dei seeder dei moduli di dominio. Matrice ruoli × permessi documentata
 * in docs/README.md ("Ruoli operativi").
 *
 * I ruoli operativi danno le abilita', non l'accesso ai pannelli: per entrare in un pannello
 * serve il ruolo col nome del pannello (`roosko::admin`, `waste::admin`, ...; vedi
 * BaseUser::canAccessPanel). PANEL_ROLES elenca i pannelli che ogni ruolo operativo usa.
 */
class RooskoPermissionSeeder extends Seeder
{
    public const string OPERATOR = 'roosko.operator';

    public const string ACCEPTANCE = 'roosko.acceptance';

    public const string TECHNICAL_MANAGER = 'roosko.technical_manager';

    private const string GUARD = 'web';

    /** Permessi delle policy di Roosko. */
    public const array ROOSKO_PERMISSIONS = [
        'delivery' => ['viewAny', 'view', 'create', 'update', 'delete', 'weigh', 'accept', 'reject'],
        'waste_lot' => ['viewAny', 'view', 'update', 'store', 'treat', 'reprintLabel', 'adjustWeight'],
        'shipment' => ['viewAny', 'view', 'create', 'update', 'delete', 'load', 'dispatch', 'cancel'],
        'shipment_item' => ['viewAny', 'view', 'delete'],
        'area_waste_rule' => ['viewAny', 'view', 'create', 'update', 'delete'],
    ];

    /** Pannelli usati da ciascun ruolo operativo (ruolo = id del pannello). */
    public const array PANEL_ROLES = [
        self::OPERATOR => ['roosko::admin'],
        self::ACCEPTANCE => ['roosko::admin', 'waste::admin', 'weighing::admin'],
        self::TECHNICAL_MANAGER => ['roosko::admin', 'waste::admin', 'warehouse::admin', 'processing::admin', 'labeling::admin', 'weighing::admin', 'rentri::admin'],
    ];

    /**
     * Operatore di piazzale: pagina Operatore (stocca/sposta, avvia trattamento, carica su
     * un'uscita, ristampa), Lavorazioni in corso (registra prodotto, completa senza forzare).
     */
    private const array OPERATOR_PERMISSIONS = [
        'delivery' => ['viewAny', 'view'],
        'waste_lot' => ['viewAny', 'view', 'store', 'treat', 'reprintLabel'],
        'shipment' => ['viewAny', 'view', 'load'],
        'shipment_item' => ['viewAny', 'view'],
        'stock_unit' => ['viewAny', 'view', 'move'],
        'warehouse' => ['viewAny', 'view'],
        'area' => ['viewAny', 'view'],
        'location' => ['viewAny', 'view'],
        'identifier' => ['viewAny', 'view'],
        'label_print' => ['viewAny', 'view', 'create'],
        'machine' => ['viewAny', 'view'],
        'processing_order' => ['viewAny', 'view', 'registerOutput', 'complete'],
    ];

    /**
     * Ufficio accettazione e pesatura: arrivo, tara, accettazione/respingimento, FIR e soggetti,
     * lettura della bilancia e annullo delle pesate, uscite (creazione, carico, spedizione,
 * annullamento), rettifica del peso delle unita'.
     */
    private const array ACCEPTANCE_PERMISSIONS = [
        'delivery' => ['viewAny', 'view', 'create', 'update', 'weigh', 'accept', 'reject'],
        'waste_lot' => ['viewAny', 'view', 'update', 'reprintLabel', 'adjustWeight'],
        'shipment' => ['viewAny', 'view', 'create', 'update', 'load', 'dispatch', 'cancel'],
        'shipment_item' => ['viewAny', 'view', 'delete'],
        'fir' => ['viewAny', 'view', 'create', 'update', 'change_status', 'record_acceptance'],
        'operator' => ['viewAny', 'view', 'create', 'update'],
        'operator_site' => ['viewAny', 'view', 'create', 'update'],
        'ewc_code' => ['viewAny', 'view'],
        'register' => ['viewAny', 'view'],
        'register_entry' => ['viewAny', 'view'],
        'weighing' => ['viewAny', 'view', 'create', 'update', 'void'],
        'scale' => ['viewAny', 'view', 'readScale'],
        'stock_unit' => ['viewAny', 'view'],
        'warehouse' => ['viewAny', 'view'],
        'location' => ['viewAny', 'view'],
        'label_print' => ['viewAny', 'view', 'create'],
        'rentri_transmission' => ['viewAny', 'view'],
    ];

    /**
     * Responsabile tecnico: tutto quello dei due ruoli operativi, piu' autorizzazioni e codici
     * autorizzati, regole d'area, rettifiche del registro, chiusura forzata e annullamento dei
     * trattamenti, eliminazioni consentite dalle policy, ritrasmissione e firma RENTRI.
     */
    private const array TECHNICAL_MANAGER_PERMISSIONS = [
        'delivery' => ['delete'],
        'shipment' => ['delete'],
        'area_waste_rule' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'authorized_waste' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'plant_authorization' => ['viewAny', 'view', 'create', 'update', 'delete'],
        'register_entry' => ['rectify'],
        'processing_order' => ['create', 'update', 'start', 'addInput', 'forceComplete', 'cancel'],
        'processing_input' => ['viewAny', 'view'],
        'processing_output' => ['viewAny', 'view'],
        'lineage_link' => ['viewAny', 'view'],
        'stock_movement' => ['viewAny', 'view'],
        'stock_unit' => ['update', 'move', 'adjustWeight', 'void'],
        'ewc_code' => ['import'],
        'area' => ['update'],
        'location' => ['update'],
        'rentri_transmission' => ['retry', 'sign'],
        'rentri_credential' => ['viewAny', 'view'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::expand(self::ROOSKO_PERMISSIONS) as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        foreach (self::matrix() as $roleName => $permissions) {
            foreach ($permissions as $name) {
                Permission::findOrCreate($name, self::GUARD);
            }
            Role::findOrCreate($roleName, self::GUARD)->syncPermissions($permissions);

            foreach (self::PANEL_ROLES[$roleName] as $panelRole) {
                Role::findOrCreate($panelRole, self::GUARD);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Ruolo operativo => permessi (il responsabile tecnico eredita operatore e accettazione).
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $operator = self::expand(self::OPERATOR_PERMISSIONS);
        $acceptance = self::expand(self::ACCEPTANCE_PERMISSIONS);
        $technical = array_values(array_unique([...$operator, ...$acceptance, ...self::expand(self::TECHNICAL_MANAGER_PERMISSIONS)]));
        sort($technical);

        return [
            self::OPERATOR => $operator,
            self::ACCEPTANCE => $acceptance,
            self::TECHNICAL_MANAGER => $technical,
        ];
    }

    /**
     * @param  array<string, list<string>>  $byModel
     * @return list<string>
     */
    private static function expand(array $byModel): array
    {
        $names = [];
        foreach ($byModel as $model => $abilities) {
            foreach ($abilities as $ability) {
                $names[] = $model.'.'.$ability;
            }
        }

        return $names;
    }
}
