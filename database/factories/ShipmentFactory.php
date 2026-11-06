<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roosko\Actions\GenerateDocumentCodeAction;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Models\Shipment;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Operator;
use Modules\Warehouse\Models\Warehouse;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /** @var class-string<Shipment> */
    protected $model = Shipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => GenerateDocumentCodeAction::format(Shipment::CODE_PREFIX.'-'.date('Y').'-', $this->faker->unique()->numberBetween(900000, 999999)),
            'warehouse_id' => Warehouse::factory(),
            'recipient_operator_id' => Operator::factory(),
            'document_type' => ShipmentDocumentEnum::FIR,
            'operation' => WasteOperationEnum::R13,
            'status' => ShipmentStatusEnum::DRAFT,
        ];
    }

    public function endOfWaste(string $declarationNumber = 'DC-TEST-001'): static
    {
        return $this->state(fn (): array => [
            'document_type' => ShipmentDocumentEnum::EOW_DECLARATION,
            'document_number' => $declarationNumber,
            'operation' => null,
        ]);
    }
}
