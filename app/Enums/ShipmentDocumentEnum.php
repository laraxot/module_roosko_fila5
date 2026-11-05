<?php

declare(strict_types=1);

namespace Modules\Roosko\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Documento che accompagna un'uscita: FIR per i rifiuti, DDT o dichiarazione di
 * conformita' per End of Waste e sottoprodotti (che non sono piu' rifiuti).
 */
enum ShipmentDocumentEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case FIR = 'fir';
    case TRANSPORT_DOCUMENT = 'transport_document';
    case EOW_DECLARATION = 'eow_declaration';

    /** Il documento e' un formulario rifiuti: l'uscita trasporta solo lotti `waste`. */
    public function carriesWaste(): bool
    {
        return $this === self::FIR;
    }

    public function accepts(LotClassificationEnum $classification): bool
    {
        return match ($this) {
            self::FIR => $classification === LotClassificationEnum::WASTE,
            self::EOW_DECLARATION => $classification === LotClassificationEnum::END_OF_WASTE,
            self::TRANSPORT_DOCUMENT => $classification !== LotClassificationEnum::WASTE,
        };
    }
}
