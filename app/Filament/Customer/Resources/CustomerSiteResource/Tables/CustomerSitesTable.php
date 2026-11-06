<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Tables;

use Modules\Xot\Actions\Cast\SafeIntCastAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TernaryFilter;
use Modules\Roosko\Actions\GetCustomerSiteDeliveredKgAction;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Waste\Models\OperatorSite;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

/**
 * La creazione sta nell'intestazione della pagina (XotBaseListRecords), non della tabella.
 * I kg conferiti sono la somma dei kg accettati dei conferimenti col FIR del cantiere
 * (GetCustomerSiteDeliveredKgAction): calcolati, quindi non ordinabili. Una sola query aggregata
 * per tutti i cantieri del cliente, memorizzata per richiesta: non una per riga (audit R12).
 */
class CustomerSitesTable extends XotBaseResourceTable
{
    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'name' => TextColumn::make('name')->searchable()->sortable(),
            'rentri_site_number' => TextColumn::make('rentri_site_number')->searchable(),
            'address' => TextColumn::make('address.full_address')->wrap()->toggleable(),
            'is_active' => IconColumn::make('is_active')->boolean(),
            'delivered_kg' => TextColumn::make('delivered_kg')
                ->state(static fn (OperatorSite $record): float => self::deliveredKg()[$record->id] ?? 0.0)
                ->numeric(3),
        ];
    }

    /**
     * Kg per cantiere di tutti i cantieri del cliente (insieme limitato), una volta per richiesta.
     *
     * @return array<int, float>
     */
    public static function deliveredKg(): array
    {
        return once(static function (): array {
            $siteIds = array_values(array_map(SafeIntCastAction::cast(...), CustomerSiteResource::getEloquentQuery()->pluck('id')->all()));

            return app(GetCustomerSiteDeliveredKgAction::class)->execute($siteIds);
        });
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getTableFilters(): array
    {
        return [
            'is_active' => TernaryFilter::make('is_active'),
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
     * @return array<string, ViewAction|EditAction>
     */
    public function getTableActions(): array
    {
        return [
            'view' => ViewAction::make(),
            'edit' => EditAction::make(),
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
        return 'name';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }
}
