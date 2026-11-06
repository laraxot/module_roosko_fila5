<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Models\EwcCode;

/**
 * @extends Factory<WasteLot>
 */
class WasteLotFactory extends Factory
{
    /** @var class-string<WasteLot> */
    protected $model = WasteLot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ewc_code_id' => EwcCode::factory(),
            'physical_state' => PhysicalStateEnum::SOLID_NON_POWDERY,
            'hazard_properties' => [],
            'classification' => LotClassificationEnum::WASTE,
            'destination' => LotDestinationEnum::INTERNAL_TREATMENT,
        ];
    }

    public function ewc(EwcCode $ewc): static
    {
        return $this->state(fn (): array => ['ewc_code_id' => $ewc->id]);
    }

    /**
     * @param  list<string>  $properties  es. ['HP14']
     */
    public function hazardous(array $properties = ['HP14']): static
    {
        return $this->state(fn (): array => [
            'hazard_properties' => array_map(HazardPropertyEnum::from(...), $properties),
        ]);
    }

    public function endOfWaste(string $batchCode = 'EOW-TEST-001'): static
    {
        return $this->state(fn (): array => [
            'classification' => LotClassificationEnum::END_OF_WASTE,
            'destination' => LotDestinationEnum::SALE,
            'eow_batch_code' => $batchCode,
        ]);
    }
}
