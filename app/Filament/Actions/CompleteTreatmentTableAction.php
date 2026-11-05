<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Actions;

use Filament\Forms\Components\Toggle;
use Modules\Processing\Enums\ProcessingOrderStatusEnum;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Actions\CompleteTreatmentAction;
use Modules\Roosko\Filament\Actions\Concerns\NotifiesDomainErrors;
use Modules\Roosko\Filament\Actions\Concerns\TranslatesAction;
use Modules\Xot\Actions\Cast\SafeBooleanCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;

/**
 * "Completa trattamento" (CompleteTreatmentAction): bilancio di massa e lineage di Processing.
 * Se la perdita supera la soglia il modale resta aperto con l'errore di dominio e l'operatore
 * puo' riconfermare con "Forza la chiusura".
 *
 * Etichetta/icona: `treatment.actions.complete`; testi: `complete_treatment_table.php`.
 */
class CompleteTreatmentTableAction extends XotBaseAction
{
    use NotifiesDomainErrors;
    use TranslatesAction;

    public static function getDefaultName(): ?string
    {
        return 'complete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-check-circle')
            ->color('primary')
            ->button()
            ->requiresConfirmation()
            ->modalHeading(static fn (ProcessingOrder $record): string => static::transAction('modal_heading', ['code' => $record->code]))
            ->modalDescription(static fn (): string => static::transAction('modal_description'))
            ->schema([
                'force' => Toggle::make('force')->default(false),
            ])
            ->action(function (array $data, ProcessingOrder $record): void {
                $force = SafeBooleanCastAction::cast($data['force'] ?? false);
                $this->runDomainAction(
                    static fn (): ProcessingOrder => app(CompleteTreatmentAction::class)->execute($record, $force),
                    static::transAction('success', ['code' => $record->code]),
                );
            })
            ->visible(static fn (ProcessingOrder $record): bool => $record->status->canTransitionTo(ProcessingOrderStatusEnum::COMPLETED));
    }
}
