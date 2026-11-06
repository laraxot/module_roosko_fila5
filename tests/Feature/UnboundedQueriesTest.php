<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Roosko\Actions\GetCustomerOperatorIdsAction;
use Modules\Roosko\Actions\GetCustomerSiteDeliveredKgAction;
use Modules\Roosko\Actions\GetLotStockKgAction;
use Modules\Roosko\Actions\IsSiteRegistrationLockedAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Filament\Actions\ReceiveDeliveryHeaderAction;
use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Pages\EditCustomerSite;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource\Schemas\CustomerSiteForm;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\User\Models\Tenant;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use PHPUnit\Framework\Assert;

/*
 * Audit database R5/R12 e sicurezza M6/B4: nessun whereIn con liste che crescono con la storia,
 * scope cliente memoizzato, kg per cantiere in una query, numero RENTRI del cantiere bloccato.
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

afterEach(function (): void {
    Filament::setTenant(null, isQuiet: true);
});

/**
 * Esegue il callback e restituisce il numero massimo di binding di una singola query.
 */
function maxBindings(\Closure $callback): int
{
    $max = 0;
    DB::listen(static function (QueryExecuted $query) use (&$max): void {
        $max = max($max, \count($query->bindings));
    });
    $callback();

    return $max;
}

test('giacenza per EER: i lotti storici non diventano parametri della query', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $unit = $plant->receiveAndAccept($plant->plastics, [300.0])['units'][0];
    app(StoreLotAction::class)->execute($unit, $plant->storageLocation);
    // 300 lotti storici dello stesso EER, senza unita' in giacenza.
    WasteLot::factory()->count(300)->create(['ewc_code_id' => $plant->plastics->id]);

    $kg = 0.0;
    $bindings = maxBindings(static function () use ($plant, &$kg): void {
        $kg = app(GetLotStockKgAction::class)->execute($plant->plastics->id, [$plant->storageArea->id]);
    });

    Assert::assertEqualsWithDelta(300.0, $kg, 0.001);
    Assert::assertLessThan(20, $bindings);
    Assert::assertEqualsWithDelta(0.0, app(GetLotStockKgAction::class)->execute($plant->plastics->id, [$plant->storageArea->id], $unit->id), 0.001);
});

test('select FIR della ricezione: niente FIR in stato finale, in uscita o con conferimento aperto', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $free = $plant->fir($plant->plastics, status: 'draft');
    $accepted = $plant->receiveAndAccept($plant->plastics, [100.0])['fir'];
    $open = $plant->fir($plant->plastics);
    app(\Modules\Roosko\Actions\ReceiveDeliveryAction::class)->execute($open, $plant->plant, $plant->warehouse);
    $outbound = Fir::factory()->create([
        'number' => 'OUT-'.Str::upper(Str::random(6)),
        'status' => 'draft',
        'producer_operator_id' => $plant->plantOperator->id,
        'recipient_operator_id' => $plant->recipient->id,
        'recipient_site_id' => null,
        'ewc_code_id' => $plant->mechanicalResidue->id,
    ]);

    $options = (new \ReflectionMethod(ReceiveDeliveryHeaderAction::class, 'firOptions'))->invoke(null);

    Assert::assertArrayHasKey($free->id, $options);
    Assert::assertArrayNotHasKey($accepted->id, $options);
    Assert::assertArrayNotHasKey($open->id, $options);
    Assert::assertArrayNotHasKey($outbound->id, $options);
});

describe('pannello cliente', function (): void {
    beforeEach(function (): void {
        $this->tenant = Tenant::factory()->create(['name' => 'Cliente '.Str::random(6)]);
        $this->operator = Operator::factory()->create([
            'business_name' => 'Impresa '.Str::random(4),
            'fiscal_code' => (string) random_int(10000000000, 99999999999),
            'roles' => ['producer'],
            'is_active' => true,
            'tenant_id' => $this->tenant->getKey(),
        ]);
        $this->site = OperatorSite::factory()->create(['operator_id' => $this->operator->id, 'name' => 'Cantiere', 'is_plant' => false]);
    });

    test('gli id dei soggetti del cliente si leggono una volta per richiesta', function (): void {
        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        $first = app(GetCustomerOperatorIdsAction::class)->execute($this->tenant);
        $afterFirst = $queries;
        $second = app(GetCustomerOperatorIdsAction::class)->execute($this->tenant);

        Assert::assertSame([$this->operator->id], $first);
        Assert::assertSame($first, $second);
        Assert::assertSame($afterFirst, $queries);
    });

    test('conferimenti del cliente: subquery, nessuna lista di id FIR', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $flow = $plant->receiveAndAccept($plant->plastics, [400.0], producerSite: $this->site);
        Filament::setTenant($this->tenant, isQuiet: true);

        $sql = CustomerDeliveryResource::getEloquentQuery()->toSql();

        Assert::assertStringContainsString('select', mb_strtolower(substr($sql, 20)));
        Assert::assertSame([$flow['delivery']->id], array_map(intval(...), CustomerDeliveryResource::getEloquentQuery()->pluck('id')->all()));
    });

    test('kg per cantiere: una sola query per tutti i cantieri, solo FIR del produttore titolare', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $plant->receiveAndAccept($plant->plastics, [400.0, 200.0], producerSite: $this->site);
        // FIR incoerente: cantiere del cliente, produttore di un'altra impresa.
        $foreign = $plant->fir($plant->plastics, 999.0);
        $foreign->forceFill(['producer_site_id' => $this->site->id])->saveQuietly();
        $other = OperatorSite::factory()->create(['operator_id' => $this->operator->id, 'name' => 'Cantiere 2', 'is_plant' => false]);

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });
        $kg = app(GetCustomerSiteDeliveredKgAction::class)->execute([$this->site->id, $other->id]);

        Assert::assertSame(1, $queries);
        Assert::assertEqualsWithDelta(600.0, $kg[$this->site->id], 0.001);
        Assert::assertEqualsWithDelta(0.0, $kg[$other->id], 0.001);
    });

    test('numero RENTRI del cantiere: libero senza FIR, bloccato dopo il primo FIR (anche lato server)', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $this->site->forceFill(['rentri_site_number' => 'UL-1'])->save();
        Assert::assertFalse(app(IsSiteRegistrationLockedAction::class)->execute($this->site));
        Assert::assertFalse(CustomerSiteForm::registrationLocked(null));

        $plant->fir($plant->plastics, producerSite: $this->site);
        Assert::assertTrue(CustomerSiteForm::registrationLocked($this->site));

        $page = new EditCustomerSite;
        $page->record = $this->site;
        $data = (new \ReflectionMethod($page, 'mutateFormDataBeforeSave'))->invoke($page, ['name' => 'Nuovo nome', 'rentri_site_number' => 'UL-FALSO']);

        Assert::assertSame(['name' => 'Nuovo nome'], $data);
    });
});
