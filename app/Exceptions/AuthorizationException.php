<?php

declare(strict_types=1);

namespace Modules\Roosko\Exceptions;

/**
 * L'impianto non e' autorizzato (codice EER/operazione) o non e' determinabile.
 */
class AuthorizationException extends RooskoException
{
    public static function wasteNotAuthorized(string $ewcCode, string $operation, string $siteName): self
    {
        return new self(self::translated('authorization.waste_not_authorized', [
            'ewc' => $ewcCode,
            'operation' => $operation,
            'site' => $siteName,
        ]));
    }

    public static function plantSiteNotResolved(string $warehouseCode): self
    {
        return new self(self::translated('authorization.plant_site_not_resolved', ['warehouse' => $warehouseCode]));
    }

    public static function plantSiteAmbiguous(string $warehouseCode): self
    {
        return new self(self::translated('authorization.plant_site_ambiguous', ['warehouse' => $warehouseCode]));
    }

    public static function registerNotFound(string $siteName): self
    {
        return new self(self::translated('authorization.register_not_found', ['site' => $siteName]));
    }
}
