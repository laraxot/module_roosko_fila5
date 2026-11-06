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
 * - Righe, stato e pesi si rileggono dentro la transazione con lock sull'uscita; i kg di ogni
 *   riga sono quelli movimentati (peso attuale dell'unita'), gli stessi di FIR e registro.
 * - End of Waste / sottoprodotti: serve il numero del documento (DDT o dichiarazione di
 *   conformita', che si riporta sui lotti EoW); nessuna scrittura sul registro rifiuti.
 */
class DispatchShipmentAction
{
    use QueueableAction;

    public function execute(Shipment $shipment): Shipment
    {
        $this->assertDispatchable($shipment);

        $isWaste = $shipment->document_type->carriesWaste();
        $plant = app(ResolvePlantSiteAction::class)->execute(Warehouse::query()->findOrFail($shipment->warehouse_id));
        $credential = $isWaste ? app(FindRentriCredentialAction::class)->execute($plant) : null;

        $issueFir = app(RunInPlantTransactionAction::class)->execute(function () use ($shipment, $isWaste, $plant, $credential): ?Fir {
            // Lock dell'uscita e rilettura delle righe sotto lock: nessuna riga aggiunta o tolta in parallelo (R7).
            $locked = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $this->assertDispatchable($locked);
            $now = CarbonImmutable::now();

            /** @var Collection<int, ShipmentItem> $items */
            $items = $locked->items()->orderBy('id')->get();

            // Prima escono le unita': i kg di ogni riga diventano quelli del movimento (peso attuale
            // dell'unita' riletto sotto lock), cosi' movimento, FIR e registro coincidono (R6).
            $lines = $items->map(static function (ShipmentItem $item) use ($locked): array {
                $unit = StockUnit::query()->findOrFail($item->stock_unit_id);
                $lot = app(ResolveWasteLotAction::class)->execute($unit);
                $movement = app(ShipStockUnitAction::class)->execute($unit, $locked);
                $item->forceFill(['quantity_kg' => round($movement->quantityKg(), 3)])->save();

                return ['item' => $item, 'unit' => $unit, 'lot' => $lot];
            });

            $newFir = null;
            if ($isWaste && $locked->fir_id === null) {
                $newFir = $this->createOutboundFir($locked, $plant, $lines, $credential instanceof RentriCredential);
                $locked->forceFill(['fir_id' => $newFir->id]);
            }

            $register = $isWaste ? app(ResolvePlantRegisterAction::class)->execute($plant) : null;

            foreach ($lines as $line) {
                /** @var WasteLot $lot */
                $lot = $line['lot'];
                /** @var ShipmentItem $item */
                $item = $line['item'];

                if ($register !== null) {
                    app(PostRegisterEntryAction::class)->execute(new RegisterEntryData(
                        registerId: $register->id,
                        type: RegisterEntryTypeEnum::UNLOAD,
                        cause: RegisterCauseEnum::TO_THIRD_PARTIES,
                        ewcCodeId: $lot->ewc_code_id,
                        quantityKg: $item->quantityKg(),
                        physicalState: $lot->physical_state,
                        hazardProperties: $lot->hazardPropertyList(),
                        operation: $locked->operation,
                        firId: $locked->fir_id,
                        counterpartOperatorId: $locked->recipient_operator_id,
                        operationAt: $now,
                        reference: $locked,
                        loadEntryIds: app(GetLotLoadEntryIdsAction::class)->execute($lot),
                    ));
                } elseif ($locked->document_type === ShipmentDocumentEnum::EOW_DECLARATION && $lot->eow_declaration_number === null) {
                    $lot->forceFill(['eow_declaration_number' => $locked->document_number])->save();
                }
            }

            $locked->forceFill(['status' => ShipmentStatusEnum::DISPATCHED, 'dispatched_at' => $now])->save();
            $shipment->setRawAttributes($locked->getAttributes(), true);

            return $newFir;
        });

        if ($issueFir instanceof Fir && $credential instanceof RentriCredential) {
            app(IssueDigitalFirAction::class)
                ->onQueue(app(GetQueueNameAction::class)->execute('rentri'))
                ->execute($issueFir, $credential);
        }

        return $shipment;
    }

    private function assertDispatchable(Shipment $shipment): void
    {
        Assert::true(
            $shipment->status->canTransitionTo(ShipmentStatusEnum::DISPATCHED),
            RooskoException::transitionMessage($shipment->code, $shipment->status, ShipmentStatusEnum::DISPATCHED),
        );
        if (! $shipment->items()->exists()) {
            throw ShipmentException::empty($shipment->code);
        }

        $isWaste = $shipment->document_type->carriesWaste();
        if ($isWaste && ! $shipment->operation instanceof WasteOperationEnum) {
            throw ShipmentException::operationRequired($shipment->code);
        }
        if (! $isWaste && ($shipment->document_number === null || trim($shipment->document_number) === '')) {
            throw ShipmentException::documentNumberRequired($shipment->code);
        }
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
