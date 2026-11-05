<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Resources\ShipmentResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Prima della spedizione si completano documento e operazione; destinatario, magazzino e
 * tipo di documento si fissano alla creazione (CreateShipmentAction).
 */
class ShipmentForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [
            'code' => TextInput::make('code')->disabled()->dehydrated(false),
            'document_number' => TextInput::make('document_number')->maxLength(64),
            'operation' => Select::make('operation')->options(WasteOperationEnum::class),
            'notes' => Textarea::make('notes')->rows(3)->columnSpanFull(),
        ];
    }
}
