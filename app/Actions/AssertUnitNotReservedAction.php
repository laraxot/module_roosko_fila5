<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Roosko\Exceptions\LotException;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;

/**
 * Un'unita' impegnata su un'uscita (`reserved`) non si tratta e non si sposta: prima va tolta
 * dall'uscita (o l'uscita va annullata).
 */
class AssertUnitNotReservedAction
{
    use QueueableAction;

    public function execute(StockUnit $unit): void
    {
        if ($unit->status === StockUnitStatusEnum::RESERVED) {
            throw LotException::unitReserved($unit->code);
        }
    }
}
