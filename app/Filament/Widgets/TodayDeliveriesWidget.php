<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Filament\Resources\DeliveryResource;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Models\Fir;
use Modules\Xot\Filament\Widgets\XotBaseTableWidget;

/**
 * Conferimenti arrivati oggi, con stato: il colpo d'occhio del responsabile di piazzale.
 */
class TodayDeliveriesWidget extends XotBaseTableWidget
{
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
            'fir_number' => TextColumn::make('fir_number')->state(static fn (Delivery $record): ?string => Fir::query()->find($record->fir_id)?->number),
            'status' => TextColumn::make('status')->badge(),
            'accepted_kg' => TextColumn::make('accepted_kg')->numeric(3),
        ];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Action>
     */
    #[\Override]
    public function getTableActions(): array
    {
        return [];
    }

    /**
     * @return array<string, never>
     */
    #[\Override]
    public function getTableBulkActions(): array
    {
        return [];
    }

    /**
     * @return Builder<Delivery>
     */
    protected function getTableQuery(): Builder
    {
        return Delivery::query()->whereDate('arrived_at', today())->orderByDesc('arrived_at');
    }
}
