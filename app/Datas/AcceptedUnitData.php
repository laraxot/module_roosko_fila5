<?php

declare(strict_types=1);

namespace Modules\Roosko\Datas;

use Modules\Warehouse\Enums\ContainerTypeEnum;
use Spatie\LaravelData\Data;

/**
 * Una unita' (contenitore) creata in accettazione di un conferimento.
 */
class AcceptedUnitData extends Data
{
    public function __construct(
        public readonly ContainerTypeEnum $containerType,
        public readonly float $netWeightKg,
        public readonly ?float $tareWeightKg = null,
        public readonly ?int $locationId = null,
    ) {}
}
