<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\DeliveryResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Solo ispezione e analisi: FIR, impianto, pesi e stato non si editano a mano.
 */
class DeliveryForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [
            'code' => TextInput::make('code')->disabled()->dehydrated(false),
            'analysis_required' => Toggle::make('analysis_required'),
            'inspection_notes' => Textarea::make('inspection_notes')->rows(4)->columnSpanFull(),
        ];
    }
}
