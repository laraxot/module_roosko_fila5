<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Models\WasteLot;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;
use Modules\Xot\Models\Policies\Concerns\DeniesUndeclaredFilamentAbilities;

class WasteLotPolicy extends UserBasePolicy
{
    use DeniesUndeclaredFilamentAbilities;

    #[\Override]
    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('waste_lot.viewAny');
    }

    public function view(UserContract $user, WasteLot $_wasteLot): bool
    {
        return $user->hasPermissionTo('waste_lot.view');
    }

    /** I lotti nascono solo dalle actions (accettazione, output di trattamento). */
    public function create(UserContract $_user): bool
    {
        return false;
    }

    public function update(UserContract $user, WasteLot $_wasteLot): bool
    {
        return $user->hasPermissionTo('waste_lot.update');
    }

    public function delete(UserContract $_user, WasteLot $_wasteLot): bool
    {
        return false;
    }

    /** Stocca/Sposta dalla pagina Operatore (StoreLotAction). */
    public function store(UserContract $user, WasteLot $_wasteLot): bool
    {
        return $user->hasPermissionTo('waste_lot.store');
    }

    /** Avvia trattamento dalla pagina Operatore (StartTreatmentAction: scarico di registro). */
    public function treat(UserContract $user, WasteLot $_wasteLot): bool
    {
        return $user->hasPermissionTo('waste_lot.treat');
    }

    /** Ristampa dell'etichetta dell'unita' del lotto. */
    public function reprintLabel(UserContract $user, WasteLot $_wasteLot): bool
    {
        return $user->hasPermissionTo('waste_lot.reprintLabel');
    }

    /** Rettifica del peso netto dell'unita' del lotto (movimento di rettifica in magazzino). */
    public function adjustWeight(UserContract $user, WasteLot $_wasteLot): bool
    {
        return $user->hasPermissionTo('waste_lot.adjustWeight');
    }
}
