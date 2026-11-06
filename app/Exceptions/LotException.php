<?php

declare(strict_types=1);

namespace Modules\Roosko\Exceptions;

/**
 * Operazione su un'unita' che non contiene un lotto Roosko, o lotto incompleto,
 * o posizionamento negato dai guard di Warehouse.
 */
class LotException extends RooskoException
{
    public static function notAWasteLot(string $unitCode): self
    {
        return new self(self::translated('lot.not_a_waste_lot', ['unit' => $unitCode]));
    }

    public static function placementDenied(string $unitCode, string $locationCode, string $reason): self
    {
        return new self(self::translated('lot.placement_denied', [
            'unit' => $unitCode,
            'location' => $locationCode,
            'reason' => $reason,
        ]));
    }

    public static function unitReserved(string $unitCode): self
    {
        return new self(self::translated('lot.unit_reserved', ['unit' => $unitCode]));
    }

    public static function eowBatchCodeRequired(): self
    {
        return new self(self::translated('lot.eow_batch_code_required'));
    }

    public static function noUnitsToTreat(): self
    {
        return new self(self::translated('lot.no_units_to_treat'));
    }

    public static function orderWithoutInputs(string $orderCode): self
    {
        return new self(self::translated('lot.order_without_inputs', ['order' => $orderCode]));
    }
}
