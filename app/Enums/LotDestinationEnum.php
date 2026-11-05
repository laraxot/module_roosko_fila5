<?php

declare(strict_types=1);

namespace Modules\Roosko\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Destino previsto di un lotto: decide quali uscite lo possono caricare.
 */
enum LotDestinationEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case INTERNAL_TREATMENT = 'internal_treatment';
    case SALE = 'sale';
    case EXTERNAL_RECOVERY = 'external_recovery';
    case DISPOSAL = 'disposal';
    case REMEDIATION = 'remediation';

    /** Il lotto lascia l'impianto (non resta in lavorazione interna). */
    public function leavesPlant(): bool
    {
        return $this !== self::INTERNAL_TREATMENT;
    }
}
