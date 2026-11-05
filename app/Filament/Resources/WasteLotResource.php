<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Modules\Roosko\Filament\Resources\WasteLotResource\Pages\EditWasteLot;
use Modules\Roosko\Filament\Resources\WasteLotResource\Pages\ListWasteLots;
use Modules\Roosko\Filament\Resources\WasteLotResource\Pages\ViewWasteLot;
use Modules\Roosko\Models\WasteLot;
use Modules\Xot\Filament\Resources\XotBaseResource;

class WasteLotResource extends XotBaseResource
{
    protected static ?string $model = WasteLot::class;

    /**
     * @return array<class-string<RelationManager>>
     */
    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWasteLots::route('/'),
            'view' => ViewWasteLot::route('/{record}'),
            'edit' => EditWasteLot::route('/{record}/edit'),
        ];
    }
}
