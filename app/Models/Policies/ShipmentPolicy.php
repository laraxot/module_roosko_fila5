<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Models\Shipment;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;

class ShipmentPolicy extends UserBasePolicy
{
    #[\Override]
    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('shipment.viewAny');
    }

    public function view(UserContract $user, Shipment $_shipment): bool
    {
        return $user->hasPermissionTo('shipment.view');
    }

    public function create(UserContract $user): bool
    {
        return $user->hasPermissionTo('shipment.create');
    }

    public function update(UserContract $user, Shipment $shipment): bool
    {
        return $shipment->status->acceptsItems() && $user->hasPermissionTo('shipment.update');
    }

    public function delete(UserContract $user, Shipment $shipment): bool
    {
        return $shipment->status === ShipmentStatusEnum::DRAFT && $user->hasPermissionTo('shipment.delete');
    }
}
