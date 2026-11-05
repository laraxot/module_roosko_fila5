<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Modules\Waste\Models\RegisterEntry;
use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Spatie\QueueableAction\QueueableAction;

/**
 * Scritture di carico che hanno originato un lotto (per `load_entry_ids` degli scarichi):
 * - lotto conferito: il carico del conferimento (reference = Delivery);
 * - lotto prodotto da trattamento: il carico di nuova produzione (reference = WasteLot).
 */
class GetLotLoadEntryIdsAction
{
    use QueueableAction;

    /**
     * @return list<int>
     */
    public function execute(WasteLot $lot): array
    {
        $reference = $lot->delivery_id !== null
            ? [(new Delivery)->getMorphClass(), $lot->delivery_id]
            : [$lot->getMorphClass(), $lot->id];

        /** @var list<int> $ids */
        $ids = RegisterEntry::query()
            ->where('reference_type', $reference[0])
            ->where('reference_id', $reference[1])
            ->where('type', RegisterEntryTypeEnum::LOAD->value)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => SafeIntCastAction::cast($id))
            ->values()
            ->all();

        return $ids;
    }
}
