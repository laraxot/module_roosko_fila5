<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Pages;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Processing\Enums\ProcessingOrderStatusEnum;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Filament\Actions\CompleteTreatmentTableAction;
use Modules\Roosko\Filament\Actions\RegisterTreatmentProductTableAction;
use Modules\Xot\Filament\Pages\XotBasePage;

/**
 * Lavorazioni in corso: gli ordini di Processing `in_progress` (avviati da "Avvia trattamento"
 * nella UI operatore) con i kg in input e quelli gia' prodotti. Per ogni ordine si registrano
 * i prodotti con lotto, EER e carico di registro (RegisterTreatmentOutputAction) e si chiude il
 * trattamento (CompleteTreatmentAction).
 *
 * Testi: `lang/{locale}/treatment.php` (titolo, navigazione, colonne, bottoni).
 */
class TreatmentPage extends XotBasePage implements HasTable
{
    use InteractsWithTable;

    /** @var class-string<ProcessingOrder> */
    public static ?string $model = ProcessingOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'treatments';

    protected string $view = 'roosko::filament.pages.treatment';

    public static function getNavigationLabel(): string
    {
        return static::trans('navigation.label');
    }

    public function getTitle(): string|Htmlable
    {
        return static::trans('title');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::trans('navigation.group');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProcessingOrder::query()
                ->with('machine')
                ->where('status', ProcessingOrderStatusEnum::IN_PROGRESS->value)
                ->withSum('inputs as input_total_kg', 'quantity_kg')
                ->withSum('outputs as output_total_kg', 'quantity_kg'))
            ->columns([
                'code' => TextColumn::make('code')->searchable(),
                'machine_name' => TextColumn::make('machine_name')
                    ->state(static fn (ProcessingOrder $record): string => $record->machine->name),
                'operation_code' => TextColumn::make('operation_code')->badge(),
                'input_total_kg' => TextColumn::make('input_total_kg')->numeric(3)->default(0),
                'output_total_kg' => TextColumn::make('output_total_kg')->numeric(3)->default(0),
                'started_at' => TextColumn::make('started_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('started_at')
            ->recordActions([
                'register_product' => RegisterTreatmentProductTableAction::make(),
                'complete' => CompleteTreatmentTableAction::make(),
            ])
            ->paginated(false);
    }
}
