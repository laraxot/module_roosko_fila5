<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Warehouse\Models\StockUnit;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    /** @var class-string<ShipmentItem> */
    protected $model = ShipmentItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'stock_unit_id' => StockUnit::factory(),
            'quantity_kg' => $this->faker->randomFloat(3, 100, 1500),
        ];
    }
}
