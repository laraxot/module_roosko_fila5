<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Exceptions\RooskoException;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Prima istruzione di ogni decisione su un conferimento (accettazione, respingimento), da
 * chiamare DENTRO la transazione dell'impianto: rilegge e blocca (`SELECT ... FOR UPDATE`)
 * conferimento e FIR e ricontrolla il passaggio di stato sui dati appena letti.
 *
 * Due richieste concorrenti sullo stesso conferimento (due operatori, doppio invio, retry di
 * rete) si serializzano sul lock: la seconda legge lo stato gia' aggiornato dalla prima e
 * fallisce sulla transizione invece di duplicare lotti, unita' e carico di registro.
 */
class LockDeliveryForDecisionAction
{
    use QueueableAction;

    /**
     * @return array{delivery: Delivery, fir: Fir}
     */
    public function execute(Delivery $delivery, DeliveryStatusEnum $target): array
    {
        $locked = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);
        Assert::true(
            $locked->status->canTransitionTo($target),
            RooskoException::transitionMessage($locked->code, $locked->status, $target),
        );

        $fir = Fir::query()->lockForUpdate()->findOrFail($locked->fir_id);

        return ['delivery' => $locked, 'fir' => $fir];
    }
}
