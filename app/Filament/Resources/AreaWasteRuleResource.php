<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources;

use Filament\Resources\RelationManagers\RelationManager;
use Modules\Roosko\Filament\Resources\AreaWasteRuleResource\Pages\CreateAreaWasteRule;
use Modules\Roosko\Filament\Resources\AreaWasteRuleResource\Pages\EditAreaWasteRule;
use Modules\Roosko\Filament\Resources\AreaWasteRuleResource\Pages\ListAreaWasteRules;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Xot\Filament\Resources\XotBaseResource;

class AreaWasteRuleResource extends XotBaseResource
{
    protected static ?string $model = AreaWasteRule::class;

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
            'index' => ListAreaWasteRules::route('/'),
            'create' => CreateAreaWasteRule::route('/create'),
            'edit' => EditAreaWasteRule::route('/{record}/edit'),
        ];
    }
}
