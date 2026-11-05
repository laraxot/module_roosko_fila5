<?php

declare(strict_types=1);

namespace Modules\Roosko\Exceptions;

/**
 * Unita' non caricabile su un'uscita o uscita non spedibile.
 */
class ShipmentException extends RooskoException
{
    public static function closed(string $shipmentCode): self
    {
        return new self(self::translated('shipment.closed', ['shipment' => $shipmentCode]));
    }

    public static function unitNotInStock(string $unitCode): self
    {
        return new self(self::translated('shipment.unit_not_in_stock', ['unit' => $unitCode]));
    }

    public static function otherWarehouse(string $unitCode): self
    {
        return new self(self::translated('shipment.other_warehouse', ['unit' => $unitCode]));
    }

    public static function alreadyLoaded(string $unitCode, string $shipmentCode): self
    {
        return new self(self::translated('shipment.already_loaded', ['unit' => $unitCode, 'shipment' => $shipmentCode]));
    }

    public static function documentMismatch(string $unitCode, string $classification, string $document): self
    {
        return new self(self::translated('shipment.document_mismatch', [
            'unit' => $unitCode,
            'classification' => $classification,
            'document' => $document,
        ]));
    }

    public static function destinationMismatch(string $unitCode, string $destination, string $expected): self
    {
        return new self(self::translated('shipment.destination_mismatch', [
            'unit' => $unitCode,
            'destination' => $destination,
            'expected' => $expected,
        ]));
    }

    public static function ewcMismatch(string $unitCode, string $ewc, string $expected): self
    {
        return new self(self::translated('shipment.ewc_mismatch', ['unit' => $unitCode, 'ewc' => $ewc, 'expected' => $expected]));
    }

    public static function empty(string $shipmentCode): self
    {
        return new self(self::translated('shipment.empty', ['shipment' => $shipmentCode]));
    }

    public static function operationRequired(string $shipmentCode): self
    {
        return new self(self::translated('shipment.operation_required', ['shipment' => $shipmentCode]));
    }

    public static function documentNumberRequired(string $shipmentCode): self
    {
        return new self(self::translated('shipment.document_number_required', ['shipment' => $shipmentCode]));
    }
}
