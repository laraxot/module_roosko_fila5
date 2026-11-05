<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerFirResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

/**
 * Niente filtri su relazioni (cantiere, soggetto): le loro opzioni leggerebbero tabelle non
 * filtrate per cliente.
 */
class CustomerFirsTable extends XotBaseResourceTable
{
    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'number' => TextColumn::make('number')->searchable()->sortable(),
            'transport_started_at' => TextColumn::make('transport_started_at')->date()->sortable(),
            'producer_site' => TextColumn::make('producerSite.name'),
            'ewc_code' => TextColumn::make('ewcCode.display_code'),
            'quantity' => TextColumn::make('quantity')->numeric(decimalPlaces: 3),
            'quantity_unit' => TextColumn::make('quantity_unit'),
            'status' => TextColumn::make('status')->badge()->sortable(),
        ];
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getTableFilters(): array
    {
        return [
            'status' => SelectFilter::make('status')->options(FirStatusEnum::class)->multiple(),
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
        return 'transport_started_at';
    }
}
