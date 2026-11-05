<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Pages;

use Modules\Roosko\Filament\Resources\WasteLotResource;
use Modules\Xot\Filament\Resources\Pages\XotBaseEditRecord;

class EditWasteLot extends XotBaseEditRecord
{
    protected static string $resource = WasteLotResource::class;
}
