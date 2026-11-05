<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Modules\Labeling\Actions\AssignIdentifierAction;
use Modules\Labeling\Enums\IdentifierTypeEnum;
use Modules\Processing\Actions\RegisterProcessingOutputAction;
<<<<<<< .merge_file_qhMcAf
<<<<<<< HEAD
<<<<<<< HEAD
=======
use Modules\Processing\Models\ProcessingInput;
>>>>>>> laraxot/dev
=======
>>>>>>> laraxot/dev
=======
>>>>>>> .merge_file_4IoINe
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Datas\TreatmentOutputData;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Actions\PostRegisterEntryAction;
use Modules\Waste\Datas\RegisterEntryData;
use Modules\Waste\Enums\RegisterCauseEnum;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Modules\Warehouse\Datas\StockUnitData;
use Modules\Warehouse\Models\StockUnit;
<<<<<<< .merge_file_qhMcAf
<<<<<<< HEAD
<<<<<<< HEAD
=======
use Modules\Warehouse\Models\Warehouse;
>>>>>>> laraxot/dev
=======
>>>>>>> laraxot/dev
=======
>>>>>>> .merge_file_4IoINe
use Spatie\QueueableAction\QueueableAction;

/**
 * Registra un'unita' prodotta da un trattamento in corso: lotto (EER, classificazione,
 * destino), output di Processing (che crea la StockUnit con movimento `production`), QR e
 * stampa etichetta. Se il prodotto e' ancora un rifiuto (es. scarto 19 12 12) scrive il carico
 * di nuova produzione sul registro; End of Waste e sottoprodotti non vanno sul registro rifiuti.
 *
<<<<<<< .merge_file_qhMcAf
<<<<<<< HEAD
<<<<<<< HEAD
 * Il magazzino e' quello delle unita' in input dell'ordine (ResolveOrderWarehouseAction).
=======
 * Il magazzino e' quello delle unita' in input dell'ordine.
>>>>>>> laraxot/dev
=======
 * Il magazzino e' quello delle unita' in input dell'ordine (ResolveOrderWarehouseAction).
>>>>>>> laraxot/dev
=======
 * Il magazzino e' quello delle unita' in input dell'ordine (ResolveOrderWarehouseAction).
>>>>>>> .merge_file_4IoINe
 */
class RegisterTreatmentOutputAction
{
    use QueueableAction;

    public function execute(ProcessingOrder $order, TreatmentOutputData $data): StockUnit
    {
        if ($data->classification === LotClassificationEnum::END_OF_WASTE && ($data->eowBatchCode === null || trim($data->eowBatchCode) === '')) {
            throw LotException::eowBatchCodeRequired();
        }

<<<<<<< .merge_file_qhMcAf
<<<<<<< HEAD
<<<<<<< HEAD
        $warehouse = app(ResolveOrderWarehouseAction::class)->execute($order);
=======
        $warehouse = $this->orderWarehouse($order);
>>>>>>> laraxot/dev
=======
        $warehouse = app(ResolveOrderWarehouseAction::class)->execute($order);
>>>>>>> laraxot/dev
=======
        $warehouse = app(ResolveOrderWarehouseAction::class)->execute($order);
>>>>>>> .merge_file_4IoINe

        $unit = app(RunInPlantTransactionAction::class)->execute(function () use ($order, $data, $warehouse): StockUnit {
            $lot = WasteLot::query()->create([
                'ewc_code_id' => $data->ewcCodeId,
                'physical_state' => $data->physicalState,
                'hazard_properties' => $data->hazardProperties,
                'classification' => $data->classification,
                'destination' => $data->destination,
                'processing_order_id' => $order->id,
                'eow_batch_code' => $data->eowBatchCode,
            ]);

            $output = app(RegisterProcessingOutputAction::class)->execute(
                $order,
                new StockUnitData(
                    warehouseId: $warehouse->id,
                    containerType: $data->containerType,
                    netWeightKg: $data->netWeightKg,
                    tareWeightKg: $data->tareWeightKg,
                    locationId: $data->locationId,
                    subject: $lot,
                ),
                $data->kind,
            );
            $unit = StockUnit::query()->findOrFail($output->stock_unit_id);

            app(AssignIdentifierAction::class)->execute($unit, IdentifierTypeEnum::QR);

            if ($data->classification->isWaste()) {
                $plant = app(ResolvePlantSiteAction::class)->execute($warehouse);
                app(PostRegisterEntryAction::class)->execute(new RegisterEntryData(
                    registerId: app(ResolvePlantRegisterAction::class)->execute($plant)->id,
                    type: RegisterEntryTypeEnum::LOAD,
                    cause: RegisterCauseEnum::NEW_PRODUCTION,
                    ewcCodeId: $data->ewcCodeId,
                    quantityKg: $data->netWeightKg,
                    physicalState: $data->physicalState,
                    hazardProperties: $data->hazardProperties,
                    operationAt: CarbonImmutable::now(),
                    reference: $lot,
                ));
            }

            return $unit;
        });

        app(QueueUnitLabelAction::class)->execute($unit);

        return $unit;
    }
<<<<<<< .merge_file_qhMcAf
<<<<<<< HEAD
<<<<<<< HEAD
=======

    private function orderWarehouse(ProcessingOrder $order): Warehouse
    {
        $input = ProcessingInput::query()->where('processing_order_id', $order->id)->orderBy('id')->first();
        if (! $input instanceof ProcessingInput) {
            throw LotException::orderWithoutInputs($order->code);
        }

        $unit = StockUnit::query()->withoutGlobalScopes()->findOrFail($input->stock_unit_id);

        return Warehouse::query()->findOrFail($unit->warehouse_id);
    }
>>>>>>> laraxot/dev
=======
>>>>>>> laraxot/dev
=======
>>>>>>> .merge_file_4IoINe
}
