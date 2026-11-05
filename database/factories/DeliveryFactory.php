<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roosko\Actions\GenerateDocumentCodeAction;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Models\Warehouse;

/**
 * Conferimento appena arrivato. Per i flussi reali usare ReceiveDeliveryAction: questa
 * factory serve a test di UI e seeder dove lo stato si imposta direttamente.
 *
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    /** @var class-string<Delivery> */
    protected $model = Delivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => GenerateDocumentCodeAction::format(Delivery::CODE_PREFIX.'-'.date('Y').'-', $this->faker->unique()->numberBetween(900000, 999999)),
            'fir_id' => Fir::factory(),
            'plant_site_id' => OperatorSite::factory(),
            'warehouse_id' => Warehouse::factory(),
            'status' => DeliveryStatusEnum::ARRIVED,
            'arrived_at' => CarbonImmutable::now(),
            'analysis_required' => false,
        ];
    }

    public function status(DeliveryStatusEnum $status): static
    {
        return $this->state(static fn (): array => ['status' => $status]);
    }
}
