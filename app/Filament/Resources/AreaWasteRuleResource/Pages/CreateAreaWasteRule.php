<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\AreaWasteRuleResource\Pages;

use Modules\Roosko\Filament\Resources\AreaWasteRuleResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseCreateRecord;

class CreateAreaWasteRule extends XotBaseCreateRecord
{
    protected static string $resource = AreaWasteRuleResource::class;
}
