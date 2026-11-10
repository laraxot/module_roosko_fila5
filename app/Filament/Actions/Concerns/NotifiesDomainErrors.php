<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions\Concerns;

use Closure;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;
use Livewire\Livewire;
use Modules\Weighing\Exceptions\WeighingException;

/**
 * Esegue la action di dominio e mostra l'errore all'operatore senza chiudere il modale.
 * Le eccezioni attese sono quelle di dominio (RooskoException e le DomainException degli altri
 * moduli) e le Assert (InvalidArgumentException); le altre (database, rete) risalgono.
 */
trait NotifiesDomainErrors
{
    /**
     * `$successTitle` puo' dipendere dal risultato della action (es. il codice dell'unita' creata).
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @param  string|Closure(TResult): string  $successTitle
     */
    protected function runDomainAction(Closure $callback, string|Closure $successTitle): void
    {
        try {
            $result = $callback();
        } catch (DomainException|InvalidArgumentException|WeighingException $exception) {
            Notification::make()
                ->danger()
                ->title(static::transAction('error'))
                ->body($exception->getMessage())
                ->send();

            $this->halt();

            return;
        }

        Notification::make()
            ->success()
            ->title($successTitle instanceof Closure ? $successTitle($result) : $successTitle)
            ->send();

        $this->reloadRecordPage();
    }

    /**
     * Sulla pagina di dettaglio di un record le tabelle collegate (es. le unita' di un conferimento
     * appena accettato) non si aggiornano da sole dopo una action di testata: restavano vuote fino
     * al ricaricamento manuale. Si ricarica la pagina; la notifica arriva comunque (sessione).
     */
    private function reloadRecordPage(): void
    {
        if (! $this->getLivewire() instanceof ViewRecord || ! Livewire::isLivewireRequest()) {
            return;
        }

        $this->redirect(Livewire::originalUrl());
    }
}
