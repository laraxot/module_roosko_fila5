<?php

declare(strict_types=1);

namespace Modules\Roosko\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Qualifica giuridica del contenuto di un lotto: rifiuto (registro di carico/scarico),
 * End of Waste (cessazione della qualifica, art. 184-ter) o sottoprodotto (art. 184-bis).
 */
enum LotClassificationEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case WASTE = 'waste';
    case END_OF_WASTE = 'end_of_waste';
    case BY_PRODUCT = 'by_product';

    /** Solo i rifiuti generano scritture sul registro di carico/scarico. */
    public function isWaste(): bool
    {
        return $this === self::WASTE;
    }
}
