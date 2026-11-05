<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Rentri\Actions\IssueDigitalFirAction;
use Modules\Rentri\Models\RentriCredential;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Exceptions\RooskoException;
use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Models\Shipment;
use Modules\Roosko\Models\ShipmentItem;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Actions\CreateFirAction;
use Modules\Waste\Actions\PostRegisterEntryAction;
use Modules\Waste\Datas\FirData;
use Modules\Waste\Datas\RegisterEntryData;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Enums\QuantityUnitEnum;
use Modules\Waste\Enums\RegisterCauseEnum;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Actions\ShipStockUnitAction;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Spedisce un'uscita: ogni unita' esce dal magazzino (ShipStockUnitAction, reference = uscita).
 *
 * - Rifiuti (documento `fir`): se l'uscita non ha ancora un FIR lo crea (produttore/detentore =
 *   impianto, destinatario = uscita), scrive uno scarico per unita' (causa `to_third_parties`,
 *   controparte = destinatario, `load_entry_ids` = carichi d'origine del lotto). Se il sito ha
 *   una credenziale RENTRI il FIR nasce digitale e l'emissione su RENTRI si accoda dopo il commit.
 * - End of Waste / sottoprodotti: serve il numero del documento (DDT o dichiarazione di
 *   conformita', che si riporta sui lotti EoW); nessuna scrittura sul registro rifiuti.
 */
class DispatchShipmentAction
{
    use QueueableAction;

    public function execute(Shipment $shipment): Shipment
    {
        Assert::true(
            $shipment->status->canTransitionTo(ShipmentStatusEnum::DISPATCHED),
            RooskoException::transitionMessage($shipment->code, $shipment->status, ShipmentStatusEnum::DISPATCHED),
        );

        /** @var Collection<int, ShipmentItem> $items */
        $items = $shipment->items()->orderBy('id')->get();
        if ($items->isEmpty()) {
            throw ShipmentException::empty($shipment->code);
        }

        $isWaste = $shipment->document_type->carriesWaste();
        if ($isWaste && ! $shipment->operation instanceof WasteOperationEnum) {
            throw ShipmentException::operationRequired($shipment->code);
        }
        if (! $isWaste && ($shipment->document_number === null || trim($shipment->document_number) === '')) {
            throw ShipmentException::documentNumberRequired($shipment->code);
        }

        $plant = app(ResolvePlantSiteAction::class)->execute(Warehouse::query()->findOrFail($shipment->warehouse_id));
        $credential = $isWaste ? app(FindRentriCredentialAction::class)->execute($plant) : null;

        $issueFir = app(RunInPlantTransactionAction::class)->execute(function () use ($shipment, $items, $isWaste, $plant, $credential): ?Fir {
            $now = CarbonImmutable::now();
            $lines = $items->map(static function (ShipmentItem $item): array {
                $unit = StockUnit::query()->findOrFail($item->stock_unit_id);

                return ['item' => $item, 'unit' => $unit, 'lot' => app(ResolveWasteLotAction::class)->execute($unit)];
            });

            $newFir = null;
            if ($isWaste && $shipment->fir_id === null) {
                $newFir = $this->createOutboundFir($shipment, $plant, $lines, $credential instanceof RentriCredential);
                $shipment->forceFill(['fir_id' => $newFir->id]);
            }

            $register = $isWaste ? app(ResolvePlantRegisterAction::class)->execute($plant) : null;

            foreach ($lines as $line) {
                /** @var StockUnit $unit */
                $unit = $line['unit'];
                /** @var WasteLot $lot */
                $lot = $line['lot'];
                /** @var ShipmentItem $item */
                $item = $line['item'];

                app(ShipStockUnitAction::class)->execute($unit, $shipment);

                if ($register !== null) {
                    app(PostRegisterEntryAction::class)->execute(new RegisterEntryData(
                        registerId: $register->id,
                        type: RegisterEntryTypeEnum::UNLOAD,
                        cause: RegisterCauseEnum::TO_THIRD_PARTIES,
                        ewcCodeId: $lot->ewc_code_id,
                        quantityKg: $item->quantityKg(),
                        physicalState: $lot->physical_state,
                        hazardProperties: $lot->hazardPropertyList(),
                        operation: $shipment->operation,
                        firId: $shipment->fir_id,
                        counterpartOperatorId: $shipment->recipient_operator_id,
                        operationAt: $now,
                        reference: $shipment,
                        loadEntryIds: app(GetLotLoadEntryIdsAction::class)->execute($lot),
                    ));
                } elseif ($shipment->document_type === ShipmentDocumentEnum::EOW_DECLARATION && $lot->eow_declaration_number === null) {
                    $lot->forceFill(['eow_declaration_number' => $shipment->document_number])->save();
                }
            }

            $shipment->forceFill(['status' => ShipmentStatusEnum::DISPATCHED, 'dispatched_at' => $now])->save();

            return $newFir;
        });

        if ($issueFir instanceof Fir && $credential instanceof RentriCredential) {
            app(IssueDigitalFirAction::class)
                ->onQueue(app(GetQueueNameAction::class)->execute('rentri'))
                ->execute($issueFir, $credential);
        }

        return $shipment;
    }

    /**
     * @param  Collection<int, array{item: ShipmentItem, unit: StockUnit, lot: WasteLot}>  $lines
     */
    private function createOutboundFir(Shipment $shipment, OperatorSite $plant, Collection $lines, bool $digital): Fir
    {
        /** @var WasteLot $first */
        $first = $lines->first()['lot'] ?? null;
        Assert::isInstanceOf($first, WasteLot::class);

        $hazardProperties = app(CastHazardPropertiesAction::class)->execute(
            $lines->flatMap(static fn (array $line): array => $line['lot']->hazardPropertyList())->all(),
        );
        $totalKg = round($lines->sum(static fn (array $line): float => $line['item']->quantityKg()), 3);

        $operation = $shipment->operation;
        Assert::isInstanceOf($operation, WasteOperationEnum::class);

        return app(CreateFirAction::class)->execute(new FirData(
            producerOperatorId: $plant->operator_id,
            recipientOperatorId: $shipment->recipient_operator_id,
            ewcCodeId: $first->ewc_code_id,
            physicalState: $first->physical_state,
            operation: $operation,
            quantity: $totalKg,
            quantityUnit: QuantityUnitEnum::KG,
            isQuantityEstimated: $shipment->weighing_id === null,
            hazardProperties: $hazardProperties,
            isDigital: $digital,
            status: FirStatusEnum::DRAFT,
            producerSiteId: $plant->id,
            recipientSiteId: $shipment->recipient_site_id,
            notes: $shipment->code,
        ));
    }
}
