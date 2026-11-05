<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Exceptions\RooskoException;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Actions\RecordFirAcceptanceAction;
use Modules\Waste\Enums\AcceptanceEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Weighing\Models\Weighing;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Respingimento integrale del carico: nessun lotto e nessuna scrittura di carico (il rifiuto
 * non entra in impianto). Il FIR registra il respingimento con il motivo; per FIR digitale
 * l'esito va a RENTRI (accodato dopo il commit).
 *
 * Kg respinti = netto della pesata se chiusa, altrimenti la quantita' dichiarata sul FIR.
 */
class RejectDeliveryAction
{
    use QueueableAction;

    public function execute(Delivery $delivery, string $reason): Delivery
    {
        Assert::true(
            $delivery->status->canTransitionTo(DeliveryStatusEnum::REJECTED),
            RooskoException::transitionMessage($delivery->code, $delivery->status, DeliveryStatusEnum::REJECTED),
        );
        Assert::stringNotEmpty(trim($reason));

        $fir = Fir::query()->findOrFail($delivery->fir_id);
        $plant = OperatorSite::query()->findOrFail($delivery->plant_site_id);

        app(RunInPlantTransactionAction::class)->execute(function () use ($delivery, $fir, $reason): void {
            app(RecordFirAcceptanceAction::class)->execute($fir, AcceptanceEnum::REJECTED, 0.0, $delivery->arrived_at, $reason);

            $delivery->forceFill([
                'status' => DeliveryStatusEnum::REJECTED,
                'accepted_kg' => 0,
                'rejected_kg' => $this->rejectedKg($delivery, $fir),
                'rejection_reason' => $reason,
            ])->save();
        });

        app(SubmitDigitalFirAcceptanceAction::class)->execute($fir, $plant);

        return $delivery;
    }

    private function rejectedKg(Delivery $delivery, Fir $fir): float
    {
        $weighing = $delivery->weighing;
        if ($weighing instanceof Weighing && $weighing->net_kg !== null) {
            return (float) $weighing->net_kg;
        }

        return (float) $fir->quantity;
    }
}
