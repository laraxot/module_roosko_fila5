<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Models\Shipment;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;
use Modules\Xot\Models\Policies\Concerns\DeniesUndeclaredFilamentAbilities;

class ShipmentPolicy extends UserBasePolicy
{
    use DeniesUndeclaredFilamentAbilities;

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

    /** Annulla un'uscita non spedita (CancelShipmentAction): libera le unita' riservate. */
    public function cancel(UserContract $user, Shipment $shipment): bool
    {
        return $shipment->status->canTransitionTo(ShipmentStatusEnum::CANCELLED) && $user->hasPermissionTo('shipment.cancel');
    }

    public function delete(UserContract $user, Shipment $shipment): bool
    {
        return $shipment->status === ShipmentStatusEnum::DRAFT && $user->hasPermissionTo('shipment.delete');
    }

    /** Carico di un'unita' (AddShipmentItemHeaderAction, pagina Operatore). */
    public function load(UserContract $user, Shipment $shipment): bool
    {
        return $shipment->status->acceptsItems() && $user->hasPermissionTo('shipment.load');
    }

    /** Spedizione: FIR, scarichi di registro, unita' in uscita (DispatchShipmentHeaderAction). */
    public function dispatch(UserContract $user, Shipment $shipment): bool
    {
        return $shipment->status->canTransitionTo(ShipmentStatusEnum::DISPATCHED) && $user->hasPermissionTo('shipment.dispatch');
    }
}
