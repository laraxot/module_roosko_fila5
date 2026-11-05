<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Models\Delivery;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;

class DeliveryPolicy extends UserBasePolicy
{
    #[\Override]
    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('delivery.viewAny');
    }

    public function view(UserContract $user, Delivery $_delivery): bool
    {
        return $user->hasPermissionTo('delivery.view');
    }

    public function create(UserContract $user): bool
    {
        return $user->hasPermissionTo('delivery.create');
    }

    public function update(UserContract $user, Delivery $_delivery): bool
    {
        return $user->hasPermissionTo('delivery.update');
    }

    /** Un conferimento con lotti o scritture di registro non si cancella: solo se ancora aperto. */
    public function delete(UserContract $user, Delivery $delivery): bool
    {
        return $delivery->status->isOpen() && $user->hasPermissionTo('delivery.delete');
    }
}
