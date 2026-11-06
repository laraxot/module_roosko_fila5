<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Database\Seeders\RooskoPermissionSeeder;
use Modules\Roosko\Filament\Pages\OperatorScanPage;
use Modules\Roosko\Filament\Resources\DeliveryResource\Pages\ViewDelivery;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Roosko\Models\Delivery;
use Modules\Roosko\Models\Profile;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\User\Models\Permission;
use Modules\User\Models\Role;
use Modules\User\Models\Tenant;
use Modules\User\Models\User;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\actingAs;

/*
 * Audit sicurezza A2, A3, M4: policy con DeniesUndeclaredFilamentAbilities, abilita' sulle azioni
 * custom, permessi e ruoli operativi seminati.
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    (new RooskoPermissionSeeder)->run();
    $this->plant = PlantFixture::build();
});

afterEach(function (): void {
    Filament::setTenant(null, isQuiet: true);
});

function userWithRole(string $role): User
{
    // profiles.id e' autoincrement solo dopo una migrazione MySQL: id assegnato qui.
    $user = User::withoutEvents(static fn (): User => User::factory()->create());
    (new Profile)->forceFill(['id' => random_int(100000, 999999999), 'user_id' => $user->id])->saveQuietly();
    $user->assignRole($role);

    return $user->refresh();
}

test('il seeder e\' idempotente e crea tutti i permessi controllati dalle policy di Roosko', function (): void {
    (new RooskoPermissionSeeder)->run();

    $source = '';
    foreach (glob(__DIR__.'/../../app/Models/Policies/*.php') ?: [] as $file) {
        $source .= (string) file_get_contents($file);
    }
    preg_match_all("/hasPermissionTo\\('([a-z_]+\\.[A-Za-z]+)'\\)/", $source, $matches);

    Assert::assertNotEmpty($matches[1]);
    foreach (array_unique($matches[1]) as $name) {
        Assert::assertSame(1, Permission::query()->where('name', $name)->where('guard_name', 'web')->count(), $name);
    }
    foreach ([RooskoPermissionSeeder::OPERATOR, RooskoPermissionSeeder::ACCEPTANCE, RooskoPermissionSeeder::TECHNICAL_MANAGER] as $role) {
        Assert::assertSame(1, Role::query()->where('name', $role)->count());
    }
});

test('matrice: il responsabile tecnico ha tutti i permessi di operatore e accettazione', function (): void {
    $matrix = RooskoPermissionSeeder::matrix();

    Assert::assertSame([], array_values(array_diff($matrix[RooskoPermissionSeeder::OPERATOR], $matrix[RooskoPermissionSeeder::TECHNICAL_MANAGER])));
    Assert::assertSame([], array_values(array_diff($matrix[RooskoPermissionSeeder::ACCEPTANCE], $matrix[RooskoPermissionSeeder::TECHNICAL_MANAGER])));
    Assert::assertNotContains('delivery.accept', $matrix[RooskoPermissionSeeder::OPERATOR]);
    Assert::assertNotContains('processing_order.forceComplete', $matrix[RooskoPermissionSeeder::OPERATOR]);
});

test('abilita\' per ruolo sul conferimento e sul lotto', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $delivery = app(ReceiveDeliveryAction::class)->execute($plant->fir($plant->plastics), $plant->plant, $plant->warehouse);
    $lot = WasteLot::factory()->create(['ewc_code_id' => $plant->plastics->id]);

    $operator = userWithRole(RooskoPermissionSeeder::OPERATOR);
    $acceptance = userWithRole(RooskoPermissionSeeder::ACCEPTANCE);

    Assert::assertFalse(Gate::forUser($operator)->allows('accept', $delivery));
    Assert::assertFalse(Gate::forUser($operator)->allows('reject', $delivery));
    Assert::assertTrue(Gate::forUser($operator)->allows('store', $lot));
    Assert::assertTrue(Gate::forUser($acceptance)->allows('accept', $delivery));
    Assert::assertFalse(Gate::forUser($acceptance)->allows('store', $lot));
});

test('abilita\' non dichiarate negate (eliminazione massiva, riordino, replica)', function (string $ability): void {
    $manager = userWithRole(RooskoPermissionSeeder::TECHNICAL_MANAGER);

    foreach ([Delivery::class, WasteLot::class, AreaWasteRule::class] as $model) {
        Assert::assertFalse(Gate::forUser($manager)->allows($ability, $model), $model.'::'.$ability);
    }
})->with(['deleteAny', 'forceDeleteAny', 'restoreAny', 'reorder']);

describe('pannello', function (): void {
    beforeEach(function (): void {
        $this->tenant = Tenant::factory()->create(['name' => 'Impianto '.Str::random(6)]);
        Filament::setCurrentPanel(Filament::getPanel('roosko::admin'));
        Filament::setTenant($this->tenant, isQuiet: true);
    });

    test('Accetta e Respingi nascosti senza permesso, visibili all\'accettazione', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $delivery = app(ReceiveDeliveryAction::class)->execute($plant->fir($plant->plastics), $plant->plant, $plant->warehouse);

        $operator = userWithRole(RooskoPermissionSeeder::OPERATOR);
        $operator->tenants()->attach($this->tenant->getKey());
        actingAs($operator);
        Livewire::test(ViewDelivery::class, ['record' => $delivery->getRouteKey()])
            ->assertActionHidden('accept')
            ->assertActionHidden('reject');

        $acceptance = userWithRole(RooskoPermissionSeeder::ACCEPTANCE);
        $acceptance->tenants()->attach($this->tenant->getKey());
        actingAs($acceptance);
        Livewire::test(ViewDelivery::class, ['record' => $delivery->getRouteKey()])
            ->assertActionVisible('accept')
            ->assertActionVisible('reject');
    });

    test('pagina Operatore: accesso solo con waste_lot.viewAny', function (): void {
        $nobody = User::withoutEvents(static fn (): User => User::factory()->create());
        actingAs($nobody);
        Assert::assertFalse(OperatorScanPage::canAccess());

        actingAs(userWithRole(RooskoPermissionSeeder::OPERATOR));
        Assert::assertTrue(OperatorScanPage::canAccess());
    });
});
