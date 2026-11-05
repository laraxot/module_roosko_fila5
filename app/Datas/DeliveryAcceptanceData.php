<?php

declare(strict_types=1);

namespace Modules\Roosko\Datas;

use Modules\Waste\Enums\AcceptanceEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Xot\Actions\Cast\SafeFloatCastAction;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Esito dell'accettazione di un conferimento: kg accettati/respinti, unita' in cui il
 * carico accettato viene suddiviso e operazione di destino all'impianto (es. R13).
 *
 * Invariante: la somma dei pesi netti delle unita' coincide con `acceptedKg`
 * (tolleranza {@see self::TOLERANCE_KG}). I parametri obbligatori precedono quelli con
 * default: usare sempre gli argomenti con nome.
 */
class DeliveryAcceptanceData extends Data
{
    public const float TOLERANCE_KG = 0.001;

    /**
     * @param  list<AcceptedUnitData>  $units
     */
    public function __construct(
        public readonly float $acceptedKg,
        #[DataCollectionOf(AcceptedUnitData::class)]
        public readonly array $units,
        public readonly WasteOperationEnum $operation,
        public readonly float $rejectedKg = 0.0,
        public readonly ?string $rejectionReason = null,
    ) {}

    /**
     * Dallo stato del form di accettazione (chiavi snake_case, repeater `units`).
     *
     * @param  array<string, mixed>  $state
     */
    public static function fromFormState(array $state): self
    {
        $units = [];
        $rows = $state['units'] ?? [];
        foreach (\is_array($rows) ? $rows : [] as $row) {
            if (! \is_array($row)) {
                continue;
            }
            $container = $row['container_type'] ?? null;
            $tare = $row['tare_weight_kg'] ?? null;
            $location = $row['location_id'] ?? null;
            $units[] = new AcceptedUnitData(
                containerType: $container instanceof ContainerTypeEnum ? $container : ContainerTypeEnum::from(\is_string($container) ? $container : ''),
                netWeightKg: SafeFloatCastAction::cast($row['net_weight_kg'] ?? null),
                tareWeightKg: $tare === null || $tare === '' ? null : SafeFloatCastAction::cast($tare),
                locationId: $location === null || $location === '' ? null : SafeIntCastAction::cast($location),
            );
        }

        $operation = $state['operation'] ?? null;
        $reason = $state['rejection_reason'] ?? null;

        return new self(
            acceptedKg: SafeFloatCastAction::cast($state['accepted_kg'] ?? null),
            units: $units,
            operation: $operation instanceof WasteOperationEnum ? $operation : WasteOperationEnum::from(\is_string($operation) ? $operation : ''),
            rejectedKg: SafeFloatCastAction::cast($state['rejected_kg'] ?? null),
            rejectionReason: \is_string($reason) && trim($reason) !== '' ? trim($reason) : null,
        );
    }

    public function unitsTotalKg(): float
    {
        return round(array_sum(array_map(
            static fn (AcceptedUnitData $unit): float => $unit->netWeightKg,
            $this->units,
        )), 3);
    }

    public function unitsMatchAcceptedKg(): bool
    {
        return abs($this->unitsTotalKg() - $this->acceptedKg) <= self::TOLERANCE_KG;
    }

    public function isPartial(): bool
    {
        return $this->rejectedKg > self::TOLERANCE_KG;
    }

    public function acceptance(): AcceptanceEnum
    {
        return AcceptanceEnum::from($this->isPartial() ? 'partial' : 'full');
    }
}
