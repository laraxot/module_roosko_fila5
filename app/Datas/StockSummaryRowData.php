<?php

declare(strict_types=1);

namespace Modules\Roosko\Datas;

use Spatie\LaravelData\Data;

/**
 * Giacenza di un codice EER in un magazzino, confrontata con il limite istantaneo
 * autorizzato (somma dei `max_storage_kg` dei codici autorizzati mappati sulle aree).
 */
class StockSummaryRowData extends Data
{
    public function __construct(
        public readonly int $warehouseId,
        public readonly int $ewcCodeId,
        public readonly string $ewcCode,
        public readonly string $description,
        public readonly bool $isHazardous,
        public readonly int $unitsCount,
        public readonly float $stockKg,
        public readonly ?float $authorizedMaxKg,
        public readonly ?float $usagePct,
    ) {}

    public static function make(
        int $warehouseId,
        int $ewcCodeId,
        string $ewcCode,
        string $description,
        bool $isHazardous,
        int $unitsCount,
        float $stockKg,
        ?float $authorizedMaxKg,
    ): self {
        return new self(
            warehouseId: $warehouseId,
            ewcCodeId: $ewcCodeId,
            ewcCode: $ewcCode,
            description: $description,
            isHazardous: $isHazardous,
            unitsCount: $unitsCount,
            stockKg: round($stockKg, 3),
            authorizedMaxKg: $authorizedMaxKg,
            usagePct: self::percentage($stockKg, $authorizedMaxKg),
        );
    }

    /** Uso percentuale del limite; null senza limite (o limite non positivo). */
    public static function percentage(float $stockKg, ?float $limitKg): ?float
    {
        if ($limitKg === null || $limitKg <= 0.0) {
            return null;
        }

        return round($stockKg / $limitKg * 100, 2);
    }

    /**
     * Colore Filament per una % d'uso: sotto `warning` success, poi warning, da `danger` danger.
     */
    public static function usageColor(?float $usagePct, float $warning, float $danger): string
    {
        return match (true) {
            $usagePct === null => 'gray',
            $usagePct >= $danger => 'danger',
            $usagePct >= $warning => 'warning',
            default => 'success',
        };
    }

    /** Colore con le soglie `roosko.authorization_usage_thresholds`. */
    public static function configuredUsageColor(?float $usagePct): string
    {
        $thresholds = config('roosko.authorization_usage_thresholds', []);
        $warning = \is_array($thresholds) && is_numeric($thresholds['warning'] ?? null) ? (float) $thresholds['warning'] : 80.0;
        $danger = \is_array($thresholds) && is_numeric($thresholds['danger'] ?? null) ? (float) $thresholds['danger'] : 95.0;

        return self::usageColor($usagePct, $warning, $danger);
    }

    /** Chiave stabile della riga per le tabelle Filament a dati custom. */
    public function key(): string
    {
        return $this->warehouseId.'-'.$this->ewcCodeId;
    }
}
