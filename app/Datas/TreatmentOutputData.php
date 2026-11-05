<?php

declare(strict_types=1);

namespace Modules\Roosko\Datas;

use Modules\Processing\Enums\OutputKindEnum;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Spatie\LaravelData\Data;

/**
 * Un'unita' prodotta da un trattamento: contenuto (lotto) + contenitore (StockUnit).
 */
class TreatmentOutputData extends Data
{
    /**
     * @param  list<HazardPropertyEnum>  $hazardProperties
     */
    public function __construct(
        public readonly int $ewcCodeId,
        public readonly PhysicalStateEnum $physicalState,
        public readonly array $hazardProperties,
        public readonly LotClassificationEnum $classification,
        public readonly LotDestinationEnum $destination,
        public readonly ContainerTypeEnum $containerType,
        public readonly float $netWeightKg,
        public readonly OutputKindEnum $kind = OutputKindEnum::PRODUCT,
        public readonly ?float $tareWeightKg = null,
        public readonly ?int $locationId = null,
        public readonly ?string $eowBatchCode = null,
    ) {}
}
