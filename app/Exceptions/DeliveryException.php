<?php

declare(strict_types=1);

namespace Modules\Roosko\Exceptions;

/**
 * Conferimento non lavorabile: FIR gia' in carico, impianto sbagliato, pesata mancante,
 * unita' che non tornano con i kg accettati.
 */
class DeliveryException extends RooskoException
{
    public static function firAlreadyReceived(string $firNumber, string $deliveryCode): self
    {
        return new self(self::translated('delivery.fir_already_received', ['fir' => $firNumber, 'delivery' => $deliveryCode]));
    }

    public static function firNotReceivable(string $firNumber, string $status): self
    {
        return new self(self::translated('delivery.fir_not_receivable', ['fir' => $firNumber, 'status' => $status]));
    }

    public static function wrongRecipientSite(string $firNumber): self
    {
        return new self(self::translated('delivery.wrong_recipient_site', ['fir' => $firNumber]));
    }

    public static function notWeighed(string $deliveryCode): self
    {
        return new self(self::translated('delivery.not_weighed', ['delivery' => $deliveryCode]));
    }

    public static function unitsMismatch(float $unitsKg, float $acceptedKg): self
    {
        return new self(self::translated('delivery.units_mismatch', [
            'units' => number_format($unitsKg, 3, ',', '.'),
            'accepted' => number_format($acceptedKg, 3, ',', '.'),
        ]));
    }

    public static function noUnits(): self
    {
        return new self(self::translated('delivery.no_units'));
    }
}
