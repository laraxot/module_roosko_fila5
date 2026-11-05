<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Exceptions\DeliveryException;
use Modules\Roosko\Exceptions\RooskoException;
use Modules\Roosko\Models\Delivery;
use Modules\Weighing\Actions\CloseWeighingAction;
use Modules\Weighing\Models\Weighing;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Seconda pesata (tara) a scarico avvenuto: chiude la pesata in ingresso, il netto lo calcola
 * Weighing. Il conferimento passa a `weighed`.
 */
class WeighDeliveryAction
{
    use QueueableAction;

    public function execute(Delivery $delivery, float $tareKg): Delivery
    {
        Assert::true(
            $delivery->status->canTransitionTo(DeliveryStatusEnum::WEIGHED),
            RooskoException::transitionMessage($delivery->code, $delivery->status, DeliveryStatusEnum::WEIGHED),
        );

        $weighing = $delivery->weighing;
        if (! $weighing instanceof Weighing) {
            throw DeliveryException::notWeighed($delivery->code);
        }

        return app(RunInPlantTransactionAction::class)->execute(static function () use ($delivery, $weighing, $tareKg): Delivery {
            app(CloseWeighingAction::class)->execute($weighing, $tareKg);
            $delivery->forceFill(['status' => DeliveryStatusEnum::WEIGHED])->save();

            return $delivery;
        });
    }
}
