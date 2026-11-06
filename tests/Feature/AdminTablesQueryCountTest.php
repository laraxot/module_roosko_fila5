<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Roosko\Actions\AddShipmentItemAction;
use Modules\Roosko\Actions\CreateShipmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Filament\Resources\DeliveryResource\Pages\ListDeliveries;
use Modules\Roosko\Filament\Resources\ShipmentResource\Pages\ListShipments;
use Modules\Roosko\Filament\Resources\WasteLotResource\Pages\ListWasteLots;
use Modules\Roosko\Models\Profile;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\User\Models\Role;
use Modules\User\Models\Tenant;
use Modules\User\Models\User;
use Modules\Waste\Enums\WasteOperationEnum;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\actingAs;

/*
 * Audit database R13: le tabelle del pannello Roosko fanno un numero di query che non cresce
 * con le righe (eager loading e aggregati al posto delle query per riga).
 */

uses(TestCase::class);

afterEach(function (): void {
    Filament::setTenant(null, isQuiet: true);
});

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
    // Il Profile del pannello: profiles.id e' autoincrement solo dopo una migrazione MySQL
    // (saltata su sqlite), quindi l'id si assegna qui.
    $user = User::withoutEvents(static fn (): User => User::factory()->create());
    (new Profile)->forceFill(['id' => random_int(100000, 999999999), 'user_id' => $user->id])->saveQuietly();
    $user->assignRole(Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));
    $tenant = Tenant::factory()->create(['name' => 'Impianto '.Str::random(6)]);
    $user->tenants()->attach($tenant->getKey());
    actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('roosko::admin'));
    Filament::setTenant($tenant, isQuiet: true);
});

/**
 * @param  class-string  $page
 */
function renderQueries(string $page): int
{
    $count = 0;
    DB::listen(static function () use (&$count): void {
        $count++;
    });
    Livewire::test($page)->assertOk();

    return $count;
}

test(':dataset: query costanti fra 1 e 4 record', function (string $page, \Closure $seed): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $seed($plant, 1);
    renderQueries($page); // primo render: cache di permessi, traduzioni, impostazioni
    $one = renderQueries($page);
    $seed($plant, 3);
    $four = renderQueries($page);

    Assert::assertSame($one, $four, $page.': '.$one.' query con 1 record, '.$four.' con 4');
})->with([
    'conferimenti' => [ListDeliveries::class, static function (PlantFixture $plant, int $n): void {
        for ($i = 0; $i < $n; $i++) {
            $plant->receiveAndAccept($plant->plastics, [100.0]);
        }
    }],
    'lotti' => [ListWasteLots::class, static function (PlantFixture $plant, int $n): void {
        for ($i = 0; $i < $n; $i++) {
            $plant->receiveAndAccept($plant->plastics, [100.0]);
        }
    }],
    'uscite' => [ListShipments::class, static function (PlantFixture $plant, int $n): void {
        for ($i = 0; $i < $n; $i++) {
            $shipment = app(CreateShipmentAction::class)->execute($plant->warehouse, $plant->recipient, ShipmentDocumentEnum::FIR, WasteOperationEnum::R13);
            $unit = $plant->receiveAndAccept($plant->plastics, [100.0])['units'][0];
            app(StoreLotAction::class)->execute($unit, $plant->storageLocation);
            $unit->subject?->forceFill(['destination' => LotDestinationEnum::EXTERNAL_RECOVERY])->save();
            app(AddShipmentItemAction::class)->execute($shipment, $unit->refresh());
        }
    }],
]);
