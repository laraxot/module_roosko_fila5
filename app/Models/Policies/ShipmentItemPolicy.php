<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Models\ShipmentItem;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;

class ShipmentItemPolicy extends UserBasePolicy
{
    #[\Override]
    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('shipment_item.viewAny');
    }

    public function view(UserContract $user, ShipmentItem $_shipmentItem): bool
    {
        return $user->hasPermissionTo('shipment_item.view');
    }

    /** Le righe si aggiungono con AddShipmentItemAction, non a mano. */
    public function create(UserContract $_user): bool
    {
        return false;
    }

    public function update(UserContract $_user, ShipmentItem $_shipmentItem): bool
    {
        return false;
    }

    public function delete(UserContract $user, ShipmentItem $shipmentItem): bool
    {
        return $shipmentItem->shipment->status->acceptsItems() && $user->hasPermissionTo('shipment_item.delete');
    }
}
