<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Exceptions\ShipmentException;
use Modules\Roosko\Models\Shipment;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use Modules\Warehouse\Models\Warehouse;
use Spatie\QueueableAction\QueueableAction;

/**
 * Nuova uscita in bozza (`USC-{anno}-{progr}`) verso un destinatario.
 *
 * Con documento `fir` l'operazione a destino (R/D) e' obbligatoria: finisce sul FIR in uscita
 * e sullo scarico di registro.
 */
class CreateShipmentAction
{
    use QueueableAction;

    public function execute(
        Warehouse $warehouse,
        Operator $recipient,
        ShipmentDocumentEnum $documentType,
        ?WasteOperationEnum $operation = null,
        ?OperatorSite $recipientSite = null,
        ?string $documentNumber = null,
        ?string $notes = null,
    ): Shipment {
        $connection = (new Shipment)->getConnectionName() ?? DB::getDefaultConnection();

        return DB::connection($connection)->transaction(function () use ($warehouse, $recipient, $documentType, $operation, $recipientSite, $documentNumber, $notes): Shipment {
            $code = app(GenerateDocumentCodeAction::class)->execute(Shipment::class, Shipment::CODE_PREFIX, CarbonImmutable::now());
            if ($documentType->carriesWaste() && $operation === null) {
                throw ShipmentException::operationRequired($code);
            }

            return Shipment::query()->create([
                'code' => $code,
                'warehouse_id' => $warehouse->id,
                'recipient_operator_id' => $recipient->id,
                'recipient_site_id' => $recipientSite?->id,
                'document_type' => $documentType,
                'document_number' => $documentNumber !== null && trim($documentNumber) !== '' ? trim($documentNumber) : null,
                'operation' => $operation,
                'status' => ShipmentStatusEnum::DRAFT,
                'notes' => $notes,
            ]);
        });
    }
}
