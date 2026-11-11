<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Geo\Models\Region;
use Modules\Rentri\Actions\Concerns\ConfiguresRentriJob;
use Modules\Rentri\Models\RentriCredential;
use Modules\Roosko\Datas\ProductionCheckData;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\PlantAuthorization;
use Modules\Xot\Datas\XotData;
use Spatie\QueueableAction\QueueableAction;
use Throwable;

/**
 * Controlli prima e dopo un rilascio in produzione: configurazione, code, scheduler, asset, dati minimi.
 * `fail` blocca (il comando esce con 1), `warn` va guardato. Non modifica nulla.
 */
class RunProductionChecksAction
{
    use QueueableAction;

    public const string SCHEDULER_HEARTBEAT_KEY = 'roosko:scheduler:heartbeat';

    /**
     * @return list<ProductionCheckData>
     */
    public function execute(): array
    {
        $production = app()->environment('production');
        $checks = [];

        $checks[] = $this->check('app.env', $production ? 'ok' : 'warn', 'APP_ENV='.app()->environment());
        $checks[] = $this->check('app.debug', config()->boolean('app.debug') ? ($production ? 'fail' : 'warn') : 'ok', 'APP_DEBUG deve essere false in produzione (mostra codice e dati negli errori)');
        $checks[] = $this->check('app.key', \is_string(config('app.key')) && config('app.key') !== '' ? 'ok' : 'fail', 'APP_KEY impostata');
        $url = config('app.url');
        $checks[] = $this->check('app.url', \is_string($url) && str_starts_with($url, 'https://') ? 'ok' : ($production ? 'fail' : 'warn'), 'APP_URL in https (QR delle etichette e link nelle mail): '.(\is_string($url) ? $url : '-'));

        $checks = [...$checks, ...$this->queueChecks()];

        $heartbeat = Cache::get(self::SCHEDULER_HEARTBEAT_KEY);
        $lastRun = \is_string($heartbeat) ? CarbonImmutable::parse($heartbeat) : null;
        $checks[] = $this->check(
            'scheduler',
            $lastRun !== null && $lastRun->greaterThan(now()->subMinutes(5)) ? 'ok' : 'fail',
            $lastRun === null ? 'scheduler mai eseguito: manca il cron "* * * * * php artisan schedule:run" (esiti RENTRI, pulizia log)' : 'ultimo giro dello scheduler: '.$lastRun->toDateTimeString(),
        );

        foreach (['storage' => storage_path(), 'bootstrap/cache' => base_path('bootstrap/cache')] as $label => $path) {
            $checks[] = $this->check('writable.'.$label, is_writable($path) ? 'ok' : 'fail', $path.' scrivibile dall\'utente del web server');
        }

        // Asset serviti dai symlink public_html/assets/{modulo} e public_html/themes/{Tema} verso le cartelle public/ versionate.
        $checks[] = $this->check('assets.operator', is_file(public_path('assets/roosko/manifest.json')) ? 'ok' : 'warn', 'pagina operatore (fotocamera): php artisan xot:module-link');
        $checks[] = $this->check('assets.geo', is_file(public_path('assets/geo/manifest.json')) ? 'ok' : 'warn', 'Geo (selettore coordinate): php artisan xot:module-link');
        $theme = XotData::make()->pub_theme;
        $checks[] = $this->check('assets.theme', is_file(public_path('themes/'.$theme.'/manifest.json')) ? 'ok' : 'warn', 'tema '.$theme.' (pagine pubbliche): php artisan xot:theme-link');

        $mailer = config('mail.default');
        $checks[] = $this->check('mail', \in_array($mailer, ['log', 'array'], true) ? 'warn' : 'ok', 'MAIL_MAILER='.(\is_string($mailer) ? $mailer : '-').' (con log/array le mail non partono: reset password, notifiche)');

        return [...$checks, ...$this->dataChecks()];
    }

    /**
     * @return list<ProductionCheckData>
     */
    private function queueChecks(): array
    {
        $connection = config('queue.default');
        if (! \is_string($connection) || $connection === 'sync') {
            return [$this->check('queue', 'fail', 'QUEUE_CONNECTION=sync: stampe e invii RENTRI girerebbero dentro la richiesta web; usare database o redis con i worker')];
        }

        $retryAfter = config()->integer('queue.connections.'.$connection.'.retry_after', 90);
        $rentriTimeout = (new class
        {
            use ConfiguresRentriJob;
        })::rentriJobTimeout();

        return [
            $this->check('queue', 'ok', 'QUEUE_CONNECTION='.$connection),
            $this->check(
                'queue.retry_after',
                $retryAfter > $rentriTimeout ? 'ok' : 'fail',
                "retry_after {$retryAfter}s deve superare il timeout dei job RENTRI ({$rentriTimeout}s): altrimenti un invio lento viene ripreso da un secondo worker e trasmesso due volte",
            ),
        ];
    }

    /**
     * @return list<ProductionCheckData>
     */
    private function dataChecks(): array
    {
        try {
            $regions = Region::query()->count();
            $plants = OperatorSite::query()->where('is_plant', true)->where('is_active', true)->count();
            $validAuthorizations = PlantAuthorization::query()->validOn(CarbonImmutable::today())->count();
            $credentials = RentriCredential::query()->where('is_active', true)->count();
        } catch (Throwable $e) {
            return [$this->check('data', 'fail', 'database non raggiungibile: '.$e->getMessage())];
        }

        return [
            $this->check('data.municipalities', $regions >= 20 ? 'ok' : 'fail', "{$regions} regioni nell'elenco dei comuni (Geo comuni-istat.json): senza, gli indirizzi non si compilano"),
            $this->check('data.plant', $plants > 0 ? 'ok' : 'warn', "{$plants} impianti attivi (unita' locali con is_plant)"),
            $this->check('data.authorization', $validAuthorizations > 0 ? 'ok' : 'warn', "{$validAuthorizations} autorizzazioni dell'impianto valide oggi: senza, nessun conferimento e' accettabile"),
            $this->check('data.rentri', $credentials > 0 ? 'ok' : 'warn', "{$credentials} credenziali RENTRI attive: senza, FIR digitali e invii al registro non partono"),
        ];
    }

    private function check(string $key, string $status, string $message): ProductionCheckData
    {
        return new ProductionCheckData($key, $status, $message);
    }
}
