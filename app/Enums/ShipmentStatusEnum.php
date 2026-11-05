<?php

declare(strict_types=1);

namespace Modules\Roosko\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Stato di un'uscita. Etichette, colori e icone in `lang/{it,en}/shipment_status_enum.php`.
 *
 * draft ─► loading ─► ready ─► dispatched ─► delivered
 *   └────────┴──────────┴─► cancelled (solo prima della spedizione)
 */
enum ShipmentStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case DRAFT = 'draft';
    case LOADING = 'loading';
    case READY = 'ready';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

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
            self::DRAFT => [self::LOADING, self::CANCELLED],
            self::LOADING => [self::READY, self::DISPATCHED, self::CANCELLED],
            self::READY => [self::LOADING, self::DISPATCHED, self::CANCELLED],
            self::DISPATCHED => [self::DELIVERED],
            self::DELIVERED, self::CANCELLED => [],
        };
    }

    /** Si possono ancora caricare unità. */
    public function acceptsItems(): bool
    {
        return \in_array($this, [self::DRAFT, self::LOADING, self::READY], true);
    }
}
