<?php

declare(strict_types=1);

namespace Modules\Roosko\Models\Policies;

use Modules\Roosko\Models\AreaWasteRule;
use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;

class AreaWasteRulePolicy extends UserBasePolicy
{
    #[\Override]
    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('area_waste_rule.viewAny');
    }

    public function view(UserContract $user, AreaWasteRule $_areaWasteRule): bool
    {
        return $user->hasPermissionTo('area_waste_rule.view');
    }

    public function create(UserContract $user): bool
    {
        return $user->hasPermissionTo('area_waste_rule.create');
    }

    public function update(UserContract $user, AreaWasteRule $_areaWasteRule): bool
    {
        return $user->hasPermissionTo('area_waste_rule.update');
    }

    public function delete(UserContract $user, AreaWasteRule $_areaWasteRule): bool
    {
        return $user->hasPermissionTo('area_waste_rule.delete');
    }
}
