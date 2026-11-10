<?php

declare(strict_types=1);

namespace Modules\Roosko\Console\Commands;

use Illuminate\Console\Command;
use Modules\Roosko\Actions\RunProductionChecksAction;
use Modules\Roosko\Datas\ProductionCheckData;

/**
 * php artisan roosko:production-check
 *
 * Da lanciare dopo ogni rilascio (lo fa deploy.sh): esce con 1 se un controllo e' bloccante.
 */
class ProductionCheckCommand extends Command
{
    /** @var string */
    protected $signature = 'roosko:production-check';

    /** @var string */
    protected $description = 'Controlla configurazione, code, scheduler, asset e dati minimi per la produzione';

    public function handle(RunProductionChecksAction $action): int
    {
        $checks = $action->execute();

        $this->table(['Controllo', 'Esito', 'Dettaglio'], array_map(
            static fn (ProductionCheckData $check): array => [$check->key, strtoupper($check->status), $check->message],
            $checks,
        ));

        $failed = array_filter($checks, static fn (ProductionCheckData $check): bool => $check->failed());
        if ($failed !== []) {
            $this->error(\count($failed).' controlli bloccanti');

            return self::FAILURE;
        }

        $this->info('Nessun controllo bloccante');

        return self::SUCCESS;
    }
}
