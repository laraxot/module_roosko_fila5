<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Roosko\Actions\GetCustomerOperatorIdsAction;
use Modules\Roosko\Actions\GetCustomerSiteDeliveredKgAction;
use Modules\Roosko\Actions\ResolveCustomerSiteOperatorAction;
use Modules\Roosko\Filament\Customer\Resources\CustomerDeliveryResource;
use Modules\Roosko\Filament\Customer\Resources\CustomerFirResource;
use Modules\Roosko\Filament\Customer\Resources\CustomerSiteResource;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\User\Models\Tenant;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;

uses(TestCase::class);

/**
 * Cliente dell'area clienti con un soggetto Waste, un cantiere e un conferimento accettato
 * (FIR prodotto da quel cantiere).
 *
 * @return array{tenant: Tenant, operator: Operator, site: OperatorSite, fir: Fir, delivery: Delivery}
 */
function customerWithDelivery(PlantFixture $plant, string $name): array
{
    $tenant = Tenant::factory()->create(['name' => $name.' '.Str::random(6)]);
    $operator = customerOperator($tenant, $name);
    $site = OperatorSite::factory()->create(['operator_id' => $operator->id, 'name' => 'Cantiere '.$name, 'is_plant' => false]);
    $flow = $plant->receiveAndAccept($plant->plastics, [700.0, 500.0], producerSite: $site);

    return ['tenant' => $tenant, 'operator' => $operator, 'site' => $site, 'fir' => $flow['fir'], 'delivery' => $flow['delivery']];
}

function customerOperator(Tenant $tenant, string $name): Operator
{
    return Operator::factory()->create([
        'business_name' => 'Impresa '.$name.' '.Str::random(4),
        'fiscal_code' => (string) random_int(10000000000, 99999999999),
        'roles' => ['producer'],
        'is_active' => true,
        'tenant_id' => $tenant->getKey(),
    ]);
}

/**
 * @param  class-string<CustomerSiteResource|CustomerFirResource|CustomerDeliveryResource>  $resource
 * @return list<int>
 */
function visibleIds(string $resource): array
{
    return array_values(array_map(intval(...), $resource::getEloquentQuery()->pluck('id')->all()));
}

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
    $this->a = customerWithDelivery($this->plant, 'A');
    $this->b = customerWithDelivery($this->plant, 'B');
});

afterEach(function (): void {
    Filament::setTenant(null, isQuiet: true);
});

describe('soggetti del cliente', function (): void {
    test('la action restituisce solo i soggetti del cliente', function (): void {
        $second = customerOperator($this->a['tenant'], 'A2');

        $ids = app(GetCustomerOperatorIdsAction::class)->execute($this->a['tenant']);

        expect($ids)->toEqualCanonicalizing([$this->a['operator']->id, $second->id])
            ->not->toContain($this->b['operator']->id)
            ->not->toContain($this->plant->producer->id);
    });

    test('senza cliente nessun soggetto', function (): void {
        expect(app(GetCustomerOperatorIdsAction::class)->execute(null))->toBe([]);
    });
});

describe('scope delle resource del pannello cliente', function (): void {
    test('il cliente A vede solo cantieri, FIR e conferimenti suoi', function (): void {
        Filament::setTenant($this->a['tenant'], isQuiet: true);

        expect(visibleIds(CustomerSiteResource::class))->toBe([$this->a['site']->id])
            ->and(visibleIds(CustomerFirResource::class))->toBe([$this->a['fir']->id])
            ->and(visibleIds(CustomerDeliveryResource::class))->toBe([$this->a['delivery']->id]);
    });

    test('il cantiere dell\'impianto e i FIR di altri produttori restano fuori', function (): void {
        Filament::setTenant($this->a['tenant'], isQuiet: true);
        $other = $this->plant->fir($this->plant->plastics);

        expect(visibleIds(CustomerSiteResource::class))->not->toContain($this->plant->plant->id)
            ->and(visibleIds(CustomerFirResource::class))->not->toContain($other->id);
    });

    test('un record di B non si apre cambiando l\'id nell\'URL', function (): void {
        Filament::setTenant($this->a['tenant'], isQuiet: true);

        expect(CustomerSiteResource::resolveRecordRouteBinding($this->b['site']->id))->toBeNull()
            ->and(CustomerFirResource::resolveRecordRouteBinding($this->b['fir']->id))->toBeNull()
            ->and(CustomerDeliveryResource::resolveRecordRouteBinding($this->b['delivery']->id))->toBeNull()
            ->and(CustomerDeliveryResource::resolveRecordRouteBinding($this->a['delivery']->id)?->getKey())->toBe($this->a['delivery']->id);
    });

    test('senza cliente corrente le resource non vedono nulla', function (): void {
        expect(visibleIds(CustomerSiteResource::class))->toBe([])
            ->and(visibleIds(CustomerFirResource::class))->toBe([])
            ->and(visibleIds(CustomerDeliveryResource::class))->toBe([]);
    });
});

describe('operazioni consentite', function (): void {
    test('conferimenti e FIR in sola lettura, cantieri anche in creazione e modifica', function (): void {
        Filament::setTenant($this->a['tenant'], isQuiet: true);

        expect(CustomerDeliveryResource::can('view', $this->a['delivery']))->toBeTrue()
            ->and(CustomerDeliveryResource::can('update', $this->a['delivery']))->toBeFalse()
            ->and(CustomerDeliveryResource::can('create'))->toBeFalse()
            ->and(CustomerFirResource::can('view', $this->a['fir']))->toBeTrue()
            ->and(CustomerFirResource::can('delete', $this->a['fir']))->toBeFalse()
            ->and(CustomerSiteResource::can('create'))->toBeTrue()
            ->and(CustomerSiteResource::can('update', $this->a['site']))->toBeTrue()
            ->and(CustomerSiteResource::can('delete', $this->a['site']))->toBeFalse();
    });

    test('nessuna operazione sui record di un altro cliente', function (): void {
        Filament::setTenant($this->a['tenant'], isQuiet: true);

        expect(CustomerSiteResource::can('view', $this->b['site']))->toBeFalse()
            ->and(CustomerSiteResource::can('update', $this->b['site']))->toBeFalse()
            ->and(CustomerDeliveryResource::can('view', $this->b['delivery']))->toBeFalse();
    });
});

describe('creazione del cantiere', function (): void {
    test('con un solo soggetto il cantiere va a quel soggetto, qualunque id arrivi dal form', function (): void {
        $operatorId = app(ResolveCustomerSiteOperatorAction::class)->execute($this->a['tenant'], $this->b['operator']->id);
        $site = OperatorSite::query()->create(['operator_id' => $operatorId, 'name' => 'Nuovo cantiere A', 'is_plant' => false]);

        Filament::setTenant($this->a['tenant'], isQuiet: true);
        expect($operatorId)->toBe($this->a['operator']->id)
            ->and(visibleIds(CustomerSiteResource::class))->toContain($site->id);

        Filament::setTenant($this->b['tenant'], isQuiet: true);
        expect(visibleIds(CustomerSiteResource::class))->not->toContain($site->id);
    });

    test('con piu\' soggetti si sceglie solo fra i propri', function (): void {
        $second = customerOperator($this->a['tenant'], 'A2');
        $resolve = app(ResolveCustomerSiteOperatorAction::class);

        expect($resolve->execute($this->a['tenant'], (string) $second->id))->toBe($second->id)
            ->and(fn () => $resolve->execute($this->a['tenant'], $this->b['operator']->id))->toThrow(ValidationException::class)
            ->and(fn () => $resolve->execute($this->a['tenant'], null))->toThrow(ValidationException::class);
    });

    test('un cliente senza soggetti non crea cantieri', function (): void {
        $orphan = Tenant::factory()->create();

        expect(fn () => app(ResolveCustomerSiteOperatorAction::class)->execute($orphan, null))->toThrow(ValidationException::class);
    });
});

describe('kg conferiti per cantiere', function (): void {
    test('somma dei kg accettati dei conferimenti col FIR del cantiere', function (): void {
        $siteA = $this->a['site']->id;
        $siteB = $this->b['site']->id;
        $kg = app(GetCustomerSiteDeliveredKgAction::class);

        expect($kg->execute([$siteA]))->toBe([$siteA => 1200.0])
            ->and($kg->execute([$siteA], now()->startOfMonth()))->toBe([$siteA => 1200.0])
            ->and($kg->execute([$siteA], now()->addDay()))->toBe([$siteA => 0.0])
            ->and($kg->execute([$siteA, $siteB])[$siteB])->toBe(1200.0)
            ->and($kg->execute([]))->toBe([]);
    });

    test('piu\' conferimenti dello stesso cantiere si sommano', function (): void {
        $siteA = $this->a['site']->id;
        $this->plant->receiveAndAccept($this->plant->plastics, [300.0], producerSite: $this->a['site']);

        expect(app(GetCustomerSiteDeliveredKgAction::class)->execute([$siteA]))->toBe([$siteA => 1500.0]);
    });

    test('un FIR col cantiere di A ma prodotto da B non entra nei kg di A', function (): void {
        $siteA = $this->a['site']->id;
        $flow = $this->plant->receiveAndAccept($this->plant->plastics, [400.0], producerSite: $this->a['site']);
        Fir::query()->whereKey($flow['fir']->id)->update(['producer_operator_id' => $this->b['operator']->id]);

        Filament::setTenant($this->a['tenant'], isQuiet: true);

        // stesso perimetro dell'elenco dei conferimenti del cliente A
        expect(app(GetCustomerSiteDeliveredKgAction::class)->execute([$siteA]))->toBe([$siteA => 1200.0])
            ->and(visibleIds(CustomerDeliveryResource::class))->not->toContain($flow['delivery']->id);
    });
});
