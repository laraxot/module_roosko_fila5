<?php

declare(strict_types=1);

namespace Modules\Roosko\Filament\Customer\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Roosko\Actions\GetCustomerSiteDeliveredKgAction;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Waste\Models\OperatorSite;
use Modules\Roosko\Filament\Widgets\Concerns\ReadOnlyTableWidget;
use Modules\Xot\Filament\Widgets\XotBaseTableWidget;

/**
 * Kg accettati nel mese corrente per cantiere del cliente. I cantieri vengono dalla query della
 * resource (scope del cliente), i kg da GetCustomerSiteDeliveredKgAction. Tabella a dati custom
 * (`records()`): le righe non sono modelli.
 */
class CustomerMonthlyKgWidget extends XotBaseTableWidget
{
    use ReadOnlyTableWidget;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows())
            ->columns(array_values($this->getTableColumns()))
            ->paginated(false);
    }

    /**
     * @return array<string, TextColumn>
     */
    #[\Override]
    protected function getTableColumns(): array
    {
        return [
            'site' => TextColumn::make('site'),
            'rentri_site_number' => TextColumn::make('rentri_site_number'),
            'delivered_kg' => TextColumn::make('delivered_kg')->numeric(3)->suffix(' kg'),
        ];
    }

    /**
     * @return array<string, array<string, string|int|float|null>>
     */
    private function rows(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, OperatorSite> $sites */
        $sites = CustomerSiteResource::getEloquentQuery()->orderBy('name')->get();
        $siteIds = array_values(array_map(static fn (OperatorSite $site): int => $site->id, $sites->all()));
        $kg = app(GetCustomerSiteDeliveredKgAction::class)->execute($siteIds, now()->startOfMonth());

        $rows = [];
        foreach ($sites as $site) {
            $rows['site-'.$site->id] = [
                'id' => $site->id,
                'site' => $site->name,
                'rentri_site_number' => $site->rentri_site_number,
                'delivered_kg' => $kg[$site->id] ?? 0.0,
            ];
        }

        return $rows;
    }
}
