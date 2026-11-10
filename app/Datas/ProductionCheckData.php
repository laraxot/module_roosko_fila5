<?php

declare(strict_types=1);

namespace Modules\Roosko\Datas;

use Spatie\LaravelData\Data;

/**
 * Esito di un controllo di `roosko:production-check`: `ok`, `warn` (da guardare) o `fail` (bloccante).
 */
class ProductionCheckData extends Data
{
    public function __construct(
        public string $key,
        public string $status,
        public string $message,
    ) {}

    public function failed(): bool
    {
        return $this->status === 'fail';
    }
}
