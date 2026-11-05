<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Models\WasteLot;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;

class WasteLotPolicy extends UserBasePolicy
{
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
}
