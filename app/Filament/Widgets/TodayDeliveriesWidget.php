<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Filament\Widgets\Concerns\ReadOnlyTableWidget;
use Modules\Xot\Filament\Widgets\XotBaseTableWidget;

/**
 * Conferimenti arrivati oggi, con stato: il colpo d'occhio del responsabile di piazzale.
 * Intervallo su `arrived_at` (usa l'indice, a differenza di whereDate) e FIR in eager loading.
 */
class TodayDeliveriesWidget extends XotBaseTableWidget
{
    use ReadOnlyTableWidget;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Delivery::class) ?? false;
    }

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return class-string<Delivery>
     */
    public function getModel(): string
    {
        return Delivery::class;
    }

    /**
     * @return array<string, TextColumn>
     */
    #[\Override]
    protected function getTableColumns(): array
    {
        return [
            'code' => TextColumn::make('code')->url(static fn (Delivery $record): string => DeliveryResource::getUrl('view', ['record' => $record])),
            'arrived_at' => TextColumn::make('arrived_at')->time('H:i'),
            'fir_number' => TextColumn::make('fir_number')->state(static fn (Delivery $record): ?string => $record->fir?->number),
            'status' => TextColumn::make('status')->badge(),
            'accepted_kg' => TextColumn::make('accepted_kg')->numeric(3),
        ];
    }

    /**
     * @return Builder<Delivery>
     */
    protected function getTableQuery(): Builder
    {
        /** @var Builder<Delivery> $query */
        $query = Delivery::query()
            ->with('fir')
            ->whereBetween('arrived_at', [today()->startOfDay(), today()->endOfDay()])
            ->orderByDesc('arrived_at');

        return $query;
    }
}
