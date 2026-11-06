<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Warehouse\Models\Area;

/**
 * @extends Factory<AreaWasteRule>
 */
class AreaWasteRuleFactory extends Factory
{
    /** @var class-string<AreaWasteRule> */
    protected $model = AreaWasteRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'authorized_waste_id' => AuthorizedWaste::factory(),
            'operation' => WasteOperationEnum::R13,
            'max_storage_kg' => null,
        ];
    }

    public function limit(float $maxStorageKg): static
    {
        return $this->state(fn (): array => ['max_storage_kg' => $maxStorageKg]);
    }
}
