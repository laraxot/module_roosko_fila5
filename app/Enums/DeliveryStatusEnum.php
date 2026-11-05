<?php

declare(strict_types=1);

namespace Modules\Roosko\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Stato di un conferimento. Etichette, colori e icone in `lang/{it,en}/delivery_status_enum.php`.
 *
 * arrived ─► weighed ─► inspecting ─► accepted | partially_accepted | rejected ─► closed
 *    └──────────┴──────────┴─(bilancia a pianale: si accetta senza pesata a ponte)
 */
enum DeliveryStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case ARRIVED = 'arrived';
    case WEIGHED = 'weighed';
    case INSPECTING = 'inspecting';
    case ACCEPTED = 'accepted';
    case PARTIALLY_ACCEPTED = 'partially_accepted';
    case REJECTED = 'rejected';
    case CLOSED = 'closed';

    public function canTransitionTo(self $to): bool
    {
        return \in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::ARRIVED => [self::WEIGHED, self::INSPECTING, self::ACCEPTED, self::PARTIALLY_ACCEPTED, self::REJECTED],
            self::WEIGHED => [self::INSPECTING, self::ACCEPTED, self::PARTIALLY_ACCEPTED, self::REJECTED],
            self::INSPECTING => [self::ACCEPTED, self::PARTIALLY_ACCEPTED, self::REJECTED],
            self::ACCEPTED, self::PARTIALLY_ACCEPTED, self::REJECTED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    /** Il conferimento attende ancora una decisione di accettazione. */
    public function isOpen(): bool
    {
        return \in_array($this, [self::ARRIVED, self::WEIGHED, self::INSPECTING], true);
    }
}
