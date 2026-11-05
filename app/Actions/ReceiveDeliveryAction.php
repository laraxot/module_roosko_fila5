<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
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
 * FIR, se indicato, deve essere l'impianto. Dentro la transazione il FIR si rilegge sotto
 * lock (`SELECT ... FOR UPDATE`) e il controllo "gia' conferito" si ripete: due arrivi
 * concorrenti dello stesso FIR si serializzano. L'indice unique su `deliveries.open_fir_id`
 * (colonna generata: `fir_id` dei conferimenti non respinti e non eliminati) e' l'ultima
 * difesa se le connessioni sono database separati.
 *
 * Il FIR avanza lungo le transizioni consentite fino a `delivered` (ChangeFirStatusAction,
 * un passo alla volta): un FIR inserito in bozza (`draft`, default della UI di Waste)
 * passa per `issued` e `in_transit`, cosi' l'accettazione non fallisce. Un FIR in stato
 * finale (accettato, respinto, annullato) non si conferisce.
 */
class ReceiveDeliveryAction
{
    use QueueableAction;

    /** Passi verso la consegna, nell'ordine di FirStatusEnum. */
    private const array STEPS_TO_DELIVERED = [FirStatusEnum::ISSUED, FirStatusEnum::IN_TRANSIT, FirStatusEnum::DELIVERED];

    public function execute(Fir $fir, OperatorSite $plant, Warehouse $warehouse, ?float $grossKg = null, ?Scale $scale = null): Delivery
    {
        $this->assertReceivable($fir, $plant);

        try {
            return app(RunInPlantTransactionAction::class)->execute(function () use ($fir, $plant, $warehouse, $grossKg, $scale): Delivery {
                $locked = Fir::query()->lockForUpdate()->findOrFail($fir->id);
                $this->assertReceivable($locked, $plant);

                $now = CarbonImmutable::now();

                $delivery = Delivery::query()->create([
                    'code' => app(GenerateDocumentCodeAction::class)->execute(Delivery::class, Delivery::CODE_PREFIX, $now),
                    'fir_id' => $locked->id,
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
                        $locked->vehicle_plate,
                        $delivery,
                    );
                    $delivery->forceFill(['weighing_id' => $weighing->id])->save();
                }

                $this->advanceToDelivered($locked);
                $fir->setRawAttributes($locked->getAttributes(), true);

                return $delivery;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertReceivable($fir, $plant);

            throw $exception;
        }
    }

    /**
     * draft → issued → in_transit → delivered, saltando i passi gia' fatti.
     */
    private function advanceToDelivered(Fir $fir): void
    {
        foreach (self::STEPS_TO_DELIVERED as $step) {
            if ($fir->status->canTransitionTo($step)) {
                app(ChangeFirStatusAction::class)->execute($fir, $step);
            }
        }
    }

    private function assertReceivable(Fir $fir, OperatorSite $plant): void
    {
        $number = $fir->number ?? (string) $fir->id;

        if ($fir->recipient_site_id !== null && $fir->recipient_site_id !== $plant->id) {
            throw DeliveryException::wrongRecipientSite($number);
        }
        if ($fir->status->isFinal()) {
            throw DeliveryException::firNotReceivable($number, $fir->status->value);
        }

        $open = Delivery::query()
            ->where('fir_id', $fir->id)
            ->where('status', '!=', DeliveryStatusEnum::REJECTED->value)
            ->first();
        if ($open instanceof Delivery) {
            throw DeliveryException::firAlreadyReceived($number, $open->code);
        }
    }
}
