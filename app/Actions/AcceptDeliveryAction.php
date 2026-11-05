<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Modules\Labeling\Actions\AssignIdentifierAction;
use Modules\Labeling\Enums\IdentifierTypeEnum;
use Modules\Roosko\Datas\AcceptedUnitData;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Exceptions\AuthorizationException;
use Modules\Roosko\Exceptions\DeliveryException;
use Modules\Roosko\Exceptions\RooskoException;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Actions\FindAuthorizedWasteAction;
use Modules\Waste\Actions\PostRegisterEntryAction;
use Modules\Waste\Actions\RecordFirAcceptanceAction;
use Modules\Waste\Datas\RegisterEntryData;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Enums\RegisterCauseEnum;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Actions\CreateStockUnitAction;
use Modules\Warehouse\Datas\StockUnitData;
use Modules\Warehouse\Enums\MovementTypeEnum;
use Modules\Warehouse\Models\StockUnit;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Accettazione (totale o parziale) di un conferimento.
 *
 * In un'unica transazione:
 * 1. verifica che l'impianto sia autorizzato per codice EER del FIR e operazione;
 * 2. verifica che la somma delle unita' coincida con i kg accettati;
 * 3. per ogni unita': WasteLot (contenuto) + StockUnit (contenitore, movimento `receipt`,
 *    con i guard di posizionamento se c'e' la posizione) + QR;
 * 4. una scrittura di carico sul registro del sito (causa `from_third_parties`, FIR,
 *    controparte = produttore, reference = conferimento);
 * 5. accettazione sul FIR.
 *
 * Dopo il commit: stampa etichette accodata e, per FIR digitale con credenziale del sito,
 * invio dell'accettazione a RENTRI accodato.
 */
class AcceptDeliveryAction
{
    use QueueableAction;

    public function execute(Delivery $delivery, DeliveryAcceptanceData $data): Delivery
    {
        $target = $data->isPartial() ? DeliveryStatusEnum::PARTIALLY_ACCEPTED : DeliveryStatusEnum::ACCEPTED;
        Assert::true($delivery->status->canTransitionTo($target), RooskoException::transitionMessage($delivery->code, $delivery->status, $target));

        if ($data->units === []) {
            throw DeliveryException::noUnits();
        }
        if (! $data->unitsMatchAcceptedKg()) {
            throw DeliveryException::unitsMismatch($data->unitsTotalKg(), $data->acceptedKg);
        }

        $fir = Fir::query()->findOrFail($delivery->fir_id);
        $plant = OperatorSite::query()->findOrFail($delivery->plant_site_id);
        $ewc = EwcCode::query()->findOrFail($fir->ewc_code_id);
        $this->assertAuthorized($plant, $ewc, $data);

        /** @var list<StockUnit> $units */
        $units = app(RunInPlantTransactionAction::class)->execute(function () use ($delivery, $data, $fir, $plant, $target): array {
            $physicalState = $fir->physical_state;
            $hazardProperties = array_values($fir->hazard_properties->all());
            $producerId = $fir->producer_operator_id;

            $units = array_map(
                fn (AcceptedUnitData $unit): StockUnit => $this->createUnit($delivery, $fir, $unit, $physicalState, $hazardProperties, $producerId),
                $data->units,
            );

            $arrivedAt = $delivery->arrived_at;
            app(PostRegisterEntryAction::class)->execute(new RegisterEntryData(
                registerId: app(ResolvePlantRegisterAction::class)->execute($plant)->id,
                type: RegisterEntryTypeEnum::LOAD,
                cause: RegisterCauseEnum::FROM_THIRD_PARTIES,
                ewcCodeId: $fir->ewc_code_id,
                quantityKg: $data->acceptedKg,
                physicalState: $physicalState,
                hazardProperties: $hazardProperties,
                operation: $data->operation,
                firId: $fir->id,
                counterpartOperatorId: $producerId,
                operationAt: $arrivedAt,
                reference: $delivery,
            ));

            app(RecordFirAcceptanceAction::class)->execute($fir, $data->acceptance(), $data->acceptedKg, $arrivedAt, $data->rejectionReason);

            $delivery->forceFill([
                'status' => $target,
                'accepted_kg' => $data->acceptedKg,
                'rejected_kg' => $data->rejectedKg,
                'rejection_reason' => $data->rejectionReason,
            ])->save();

            return $units;
        });

        foreach ($units as $unit) {
            app(QueueUnitLabelAction::class)->execute($unit);
        }
        app(SubmitDigitalFirAcceptanceAction::class)->execute($fir, $plant);

        return $delivery;
    }

    /**
     * @param  list<HazardPropertyEnum>  $hazardProperties
     */
    private function createUnit(
        Delivery $delivery,
        Fir $fir,
        AcceptedUnitData $unit,
        PhysicalStateEnum $physicalState,
        array $hazardProperties,
        int $producerId,
    ): StockUnit {
        $lot = WasteLot::query()->create([
            'ewc_code_id' => $fir->ewc_code_id,
            'physical_state' => $physicalState,
            'hazard_properties' => $hazardProperties,
            'classification' => LotClassificationEnum::WASTE,
            'destination' => LotDestinationEnum::INTERNAL_TREATMENT,
            'producer_operator_id' => $producerId,
            'delivery_id' => $delivery->id,
        ]);

        $stockUnit = app(CreateStockUnitAction::class)->execute(
            new StockUnitData(
                warehouseId: $delivery->warehouse_id,
                containerType: $unit->containerType,
                netWeightKg: $unit->netWeightKg,
                tareWeightKg: $unit->tareWeightKg,
                locationId: $unit->locationId,
                subject: $lot,
            ),
            MovementTypeEnum::RECEIPT,
            $delivery,
        );

        app(AssignIdentifierAction::class)->execute($stockUnit, IdentifierTypeEnum::QR);

        return $stockUnit;
    }

    private function assertAuthorized(OperatorSite $plant, EwcCode $ewc, DeliveryAcceptanceData $data): void
    {
        $authorized = app(FindAuthorizedWasteAction::class)->execute($plant, $ewc, $data->operation);
        if (! $authorized instanceof AuthorizedWaste) {
            throw AuthorizationException::wasteNotAuthorized($ewc->code, $data->operation->value, $plant->name);
        }
    }
}
