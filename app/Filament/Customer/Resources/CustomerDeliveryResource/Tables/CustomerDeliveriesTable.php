<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Modules\Roosko\Models\Delivery;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

/**
 * FIR, cantiere ed EER arrivano dalle relazioni (`fir`, `fir.producerSite`, `fir.ewcCode`):
 * Filament le carica in blocco, ciascuna sulla connessione del suo modello. Non sono ordinabili
 * ne' ricercabili perche' richiederebbero join tra moduli.
 */
class CustomerDeliveriesTable extends XotBaseResourceTable
{
    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'code' => TextColumn::make('code')->searchable()->sortable(),
            'arrived_at' => TextColumn::make('arrived_at')->dateTime()->sortable(),
            'fir_number' => TextColumn::make('fir_number')->state(static fn (Delivery $record): string => $record->fir->displayNumber()),
            'producer_site' => TextColumn::make('fir.producerSite.name'),
            'ewc_code' => TextColumn::make('fir.ewcCode.display_code'),
            'accepted_kg' => TextColumn::make('accepted_kg')->numeric(3)->sortable(),
            'status' => TextColumn::make('status')->badge()->sortable(),
        ];
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getTableFilters(): array
    {
        return [
            'status' => SelectFilter::make('status')->options(DeliveryStatusEnum::class)->multiple(),
        ];
    }

    /**
     * @return array<int|string, Action|ActionGroup>
     */
    public function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, ViewAction>
     */
    public function getTableActions(): array
    {
        return [
            'view' => ViewAction::make(),
        ];
    }

    /**
     * @return array<string, never>
     */
    public function getTableBulkActions(): array
    {
        return [];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'arrived_at';
    }
}
