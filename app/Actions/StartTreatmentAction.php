<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Processing\Actions\AddProcessingInputAction;
use Modules\Processing\Actions\CreateProcessingOrderAction;
use Modules\Processing\Actions\StartProcessingOrderAction;
use Modules\Processing\Models\Machine;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Exceptions\AuthorizationException;
use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Models\WasteLot;
use Modules\Waste\Actions\FindAuthorizedWasteAction;
use Modules\Waste\Actions\PostRegisterEntryAction;
use Modules\Waste\Datas\RegisterEntryData;
use Modules\Waste\Enums\RegisterCauseEnum;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\Register;
use Modules\Warehouse\Actions\LockStockUnitAction;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;
use Spatie\QueueableAction\QueueableAction;

/**
 * Avvia un trattamento (es. triturazione R12/R3) sulle unita' indicate.
 *
 * Crea e avvia l'ordine di lavorazione (codice operazione = `$operation`), consuma per intero
 * ogni unita' come input e, per ogni lotto `waste`, scrive lo scarico sul registro (causa
 * `internal_treatment`, collegato ai carichi d'origine del lotto). Prima verifica che
 * l'impianto sia autorizzato all'operazione per ciascun codice EER.
 *
 * Un'unita' impegnata su un'uscita (`reserved`) non si tratta. Lo scarico usa la quantita'
 * dell'input registrato da Processing (peso riletto sotto lock), non quella dell'istanza
 * passata, che puo' essere stantia (audit database R6).
 */
class StartTreatmentAction
{
    use QueueableAction;

    /**
     * @param  Collection<int, StockUnit>  $units
     */
    public function execute(Machine $machine, WasteOperationEnum $operation, Collection $units): ProcessingOrder
    {
        if ($units->isEmpty()) {
            throw LotException::noUnitsToTreat();
        }

        $resolver = app(ResolveWasteLotAction::class);
        /** @var array<int, WasteLot> $lots */
        $lots = [];
        foreach ($units as $unit) {
            app(AssertUnitNotReservedAction::class)->execute($unit);
            $lots[$unit->id] = $resolver->execute($unit);
        }

        $warehouse = Warehouse::query()->findOrFail($units->first()->warehouse_id);
        $plant = app(ResolvePlantSiteAction::class)->execute($warehouse);
        $this->assertAuthorized($plant, $operation, $lots);

        return app(RunInPlantTransactionAction::class)->execute(function () use ($machine, $operation, $units, $lots, $plant): ProcessingOrder {
            $order = app(CreateProcessingOrderAction::class)->execute($machine, $operation->value);
            $order = app(StartProcessingOrderAction::class)->execute($order);
            $register = null;

            foreach ($units as $unit) {
                $lot = $lots[$unit->id];
                app(AssertUnitNotReservedAction::class)->execute(app(LockStockUnitAction::class)->execute($unit));

                // Peso riletto sotto lock da Processing: scarico di registro = quantita' consumata.
                $consumedKg = (float) app(AddProcessingInputAction::class)->execute($order, $unit)->quantity_kg;

                if (! $lot->classification->isWaste()) {
                    continue;
                }

                $register ??= app(ResolvePlantRegisterAction::class)->execute($plant);
                $this->postUnload($register, $lot, $consumedKg, $operation, $order);
            }

            return $order;
        });
    }

    private function postUnload(Register $register, WasteLot $lot, float $kg, WasteOperationEnum $operation, ProcessingOrder $order): void
    {
        app(PostRegisterEntryAction::class)->execute(new RegisterEntryData(
            registerId: $register->id,
            type: RegisterEntryTypeEnum::UNLOAD,
            cause: RegisterCauseEnum::INTERNAL_TREATMENT,
            ewcCodeId: $lot->ewc_code_id,
            quantityKg: $kg,
            physicalState: $lot->physical_state,
            hazardProperties: $lot->hazardPropertyList(),
            operation: $operation,
            operationAt: CarbonImmutable::now(),
            reference: $order,
            loadEntryIds: app(GetLotLoadEntryIdsAction::class)->execute($lot),
        ));
    }

    /**
     * @param  array<int, WasteLot>  $lots
     */
    private function assertAuthorized(OperatorSite $plant, WasteOperationEnum $operation, array $lots): void
    {
        $ewcIds = array_values(array_unique(array_map(static fn (WasteLot $lot): int => $lot->ewc_code_id, array_filter(
            $lots,
            static fn (WasteLot $lot): bool => $lot->classification->isWaste(),
        ))));

        /** @var \Illuminate\Database\Eloquent\Collection<int, EwcCode> $ewcCodes */
        $ewcCodes = EwcCode::query()->whereIn('id', $ewcIds)->get();
        foreach ($ewcCodes as $ewc) {
            $authorized = app(FindAuthorizedWasteAction::class)->execute($plant, $ewc, $operation, CarbonImmutable::today());
            if (! $authorized instanceof AuthorizedWaste) {
                throw AuthorizationException::wasteNotAuthorized($ewc->code, $operation->value, $plant->name);
            }
        }
    }
}
