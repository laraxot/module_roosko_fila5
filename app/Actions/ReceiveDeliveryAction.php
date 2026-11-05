<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Exceptions\DeliveryException;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Actions\ChangeFirStatusAction;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Models\Warehouse;
use Modules\Weighing\Actions\OpenWeighingAction;
use Modules\Weighing\Enums\WeighingDirectionEnum;
use Modules\Weighing\Models\Scale;
use Spatie\QueueableAction\QueueableAction;

/**
 * Arrivo di un carico con FIR: registra il conferimento (`CNF-{anno}-{progr}`) e, se c'e'
 * il lordo, apre la pesata in ingresso sulla pesa a ponte (primo peso = lordo).
 *
 * Un FIR si conferisce una volta sola (salvo conferimenti respinti); il destinatario del
 * FIR, se indicato, deve essere l'impianto. Il FIR in viaggio passa a `delivered`.
 */
class ReceiveDeliveryAction
{
    use QueueableAction;

    public function execute(Fir $fir, OperatorSite $plant, Warehouse $warehouse, ?float $grossKg = null, ?Scale $scale = null): Delivery
    {
        $this->assertReceivable($fir, $plant);

        return app(RunInPlantTransactionAction::class)->execute(function () use ($fir, $plant, $warehouse, $grossKg, $scale): Delivery {
            $now = CarbonImmutable::now();

            $delivery = Delivery::query()->create([
                'code' => app(GenerateDocumentCodeAction::class)->execute(Delivery::class, Delivery::CODE_PREFIX, $now),
                'fir_id' => $fir->id,
                'plant_site_id' => $plant->id,
                'warehouse_id' => $warehouse->id,
                'status' => DeliveryStatusEnum::ARRIVED,
                'arrived_at' => $now,
            ]);

            if ($grossKg !== null) {
                $weighing = app(OpenWeighingAction::class)->execute(
                    WeighingDirectionEnum::INBOUND,
                    $grossKg,
                    $scale,
                    $fir->vehicle_plate,
                    $delivery,
                );
                $delivery->forceFill(['weighing_id' => $weighing->id])->save();
            }

            // Solo un FIR in viaggio diventa `delivered`; gli altri passi li fa RecordFirAcceptanceAction.
            if ($fir->status->canTransitionTo(FirStatusEnum::DELIVERED)) {
                app(ChangeFirStatusAction::class)->execute($fir, FirStatusEnum::DELIVERED);
            }

            return $delivery;
        });
    }

    private function assertReceivable(Fir $fir, OperatorSite $plant): void
    {
        if ($fir->recipient_site_id !== null && $fir->recipient_site_id !== $plant->id) {
            throw DeliveryException::wrongRecipientSite($fir->number ?? (string) $fir->id);
        }

        $open = Delivery::query()
            ->where('fir_id', $fir->id)
            ->where('status', '!=', DeliveryStatusEnum::REJECTED->value)
            ->first();
        if ($open instanceof Delivery) {
            throw DeliveryException::firAlreadyReceived($fir->number ?? (string) $fir->id, $open->code);
        }
    }
}
