<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Modules\Processing\Models\ProcessingInput;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Exceptions\LotException;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;
use Spatie\QueueableAction\QueueableAction;

/**
 * Magazzino di un trattamento: quello della prima unita' in input dell'ordine. Le unita'
 * prodotte nascono li' (RegisterTreatmentOutputAction) e la UI ne propone le posizioni.
 */
class ResolveOrderWarehouseAction
{
    use QueueableAction;

    public function execute(ProcessingOrder $order): Warehouse
    {
        $warehouse = $this->find($order);
        if (! $warehouse instanceof Warehouse) {
            throw LotException::orderWithoutInputs($order->code);
        }

        return $warehouse;
    }

    /** Come execute(), ma null se l'ordine non ha ancora input (es. per le opzioni di una select). */
    public function find(ProcessingOrder $order): ?Warehouse
    {
        $input = ProcessingInput::query()->where('processing_order_id', $order->id)->orderBy('id')->first();
        if (! $input instanceof ProcessingInput) {
            return null;
        }

        $unit = StockUnit::query()->withoutGlobalScopes()->findOrFail($input->stock_unit_id);

        return Warehouse::query()->findOrFail($unit->warehouse_id);
    }
}
