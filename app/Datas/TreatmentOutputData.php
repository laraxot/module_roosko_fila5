<?php

declare(strict_types=1);

namespace Modules\Roosko\Datas;

use Modules\Processing\Enums\OutputKindEnum;
<<<<<<< .merge_file_Idf6B6
<<<<<<< HEAD
<<<<<<< HEAD
use Modules\Roosko\Actions\CastHazardPropertiesAction;
=======
>>>>>>> laraxot/dev
=======
use Modules\Roosko\Actions\CastHazardPropertiesAction;
>>>>>>> laraxot/dev
=======
use Modules\Roosko\Actions\CastHazardPropertiesAction;
>>>>>>> .merge_file_BJj2ge
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Warehouse\Enums\ContainerTypeEnum;
<<<<<<< .merge_file_Idf6B6
<<<<<<< HEAD
<<<<<<< HEAD
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
=======
>>>>>>> laraxot/dev
=======
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
>>>>>>> laraxot/dev
=======
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
>>>>>>> .merge_file_BJj2ge
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
<<<<<<< .merge_file_Idf6B6
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> laraxot/dev
=======
>>>>>>> .merge_file_BJj2ge

    /**
     * Dallo stato del form "Registra prodotto" (chiavi snake_case; enum come istanze o valori).
     * Le classi HP e il codice EoW mancano quando i campi sono nascosti: lista vuota / null.
     *
     * @param  array<string, mixed>  $state
     */
    public static function fromFormState(array $state): self
    {
        $tare = $state['tare_weight_kg'] ?? null;
        $location = $state['location_id'] ?? null;
        $eowBatchCode = $state['eow_batch_code'] ?? null;
        $kind = $state['kind'] ?? null;

        return new self(
            ewcCodeId: SafeIntCastAction::cast($state['ewc_code_id'] ?? null),
            physicalState: self::enumOf(PhysicalStateEnum::class, $state['physical_state'] ?? null),
            hazardProperties: app(CastHazardPropertiesAction::class)->execute($state['hazard_properties'] ?? []),
            classification: self::enumOf(LotClassificationEnum::class, $state['classification'] ?? null),
            destination: self::enumOf(LotDestinationEnum::class, $state['destination'] ?? null),
            containerType: self::enumOf(ContainerTypeEnum::class, $state['container_type'] ?? null),
            netWeightKg: SafeFloatCastAction::cast($state['net_weight_kg'] ?? null),
            kind: $kind === null || $kind === '' ? OutputKindEnum::PRODUCT : self::enumOf(OutputKindEnum::class, $kind),
            tareWeightKg: $tare === null || $tare === '' ? null : SafeFloatCastAction::cast($tare),
            locationId: $location === null || $location === '' ? null : SafeIntCastAction::cast($location),
            eowBatchCode: \is_string($eowBatchCode) && trim($eowBatchCode) !== '' ? trim($eowBatchCode) : null,
        );
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum
     */
    private static function enumOf(string $enum, mixed $value): \BackedEnum
    {
        if ($value instanceof $enum) {
            return $value;
        }

        return $enum::from(\is_string($value) ? $value : '');
    }
<<<<<<< .merge_file_Idf6B6
<<<<<<< HEAD
=======
>>>>>>> laraxot/dev
=======
>>>>>>> laraxot/dev
=======
>>>>>>> .merge_file_BJj2ge
}
