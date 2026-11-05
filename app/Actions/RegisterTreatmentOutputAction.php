<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Modules\Labeling\Actions\AssignIdentifierAction;
use Modules\Labeling\Enums\IdentifierTypeEnum;
use Modules\Processing\Actions\RegisterProcessingOutputAction;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Datas\TreatmentOutputData;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Models\WasteLot;
use Modules\Warehouse\Datas\StockUnitData;
use Modules\Warehouse\Models\StockUnit;
use Modules\Waste\Actions\PostRegisterEntryAction;
use Modules\Waste\Datas\RegisterEntryData;
use Modules\Waste\Enums\RegisterCauseEnum;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Spatie\QueueableAction\QueueableAction;

/**
 * Registra un'unita' prodotta da un trattamento in corso: lotto (EER, classificazione,
 * destino), output di Processing (che crea la StockUnit con movimento `production`), QR e
 * stampa etichetta. Se il prodotto e' ancora un rifiuto (es. scarto 19 12 12) scrive il carico
 * di nuova produzione sul registro; End of Waste e sottoprodotti non vanno sul registro rifiuti.
 *
 * Il magazzino e' quello delle unita' in input dell'ordine (ResolveOrderWarehouseAction).
 */
class RegisterTreatmentOutputAction
{
    use QueueableAction;

    public function execute(ProcessingOrder $order, TreatmentOutputData $data): StockUnit
    {
        if ($data->classification === LotClassificationEnum::END_OF_WASTE && ($data->eowBatchCode === null || trim($data->eowBatchCode) === '')) {
            throw LotException::eowBatchCodeRequired();
        }

        $warehouse = app(ResolveOrderWarehouseAction::class)->execute($order);

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
}
