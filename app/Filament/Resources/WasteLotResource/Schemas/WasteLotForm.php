<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\WasteLotResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Del lotto si correggono solo destino e riferimenti End of Waste. Codice EER, stato fisico,
 * pericolosita' e classificazione sono scritti sul registro: si cambiano con una rettifica.
 */
class WasteLotForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [
            'destination' => Select::make('destination')->options(LotDestinationEnum::class)->required(),
            'eow_batch_code' => TextInput::make('eow_batch_code')->maxLength(64),
            'eow_declaration_number' => TextInput::make('eow_declaration_number')->maxLength(64),
            'notes' => Textarea::make('notes')->rows(3)->columnSpanFull(),
        ];
    }
}
