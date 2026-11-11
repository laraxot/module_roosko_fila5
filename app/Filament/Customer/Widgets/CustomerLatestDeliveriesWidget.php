<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Widgets;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Filament\Widgets\Concerns\ReadOnlyTableWidget;
use Modules\Xot\Filament\Widgets\XotBaseTableWidget;

/**
 * Ultimi 10 conferimenti del cliente: stessa query (e stesso scope) di CustomerDeliveryResource.
 */
class CustomerLatestDeliveriesWidget extends XotBaseTableWidget
{
    use ReadOnlyTableWidget;

    private const int LIMIT = 10;

    protected static ?int $sort = 2;

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
            'code' => TextColumn::make('code')->url(static fn (Delivery $record): string => CustomerDeliveryResource::getUrl('view', ['record' => $record])),
            'arrived_at' => TextColumn::make('arrived_at')->dateTime(),
            'fir_number' => TextColumn::make('fir.number'),
            'producer_site' => TextColumn::make('fir.producerSite.name'),
            'accepted_kg' => TextColumn::make('accepted_kg')->numeric(3),
            'status' => TextColumn::make('status')->badge(),
        ];
    }

    #[\Override]
    protected function getTablePaginated(): bool
    {
        return false;
    }

    #[\Override]
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'arrived_at';
    }

    /**
     * @return Builder<Delivery>
     */
    protected function getTableQuery(): Builder
    {
        /** @var Builder<Delivery> $query */
        $query = CustomerDeliveryResource::getEloquentQuery();

        return $query->orderByDesc('arrived_at')->limit(self::LIMIT);
    }
}
