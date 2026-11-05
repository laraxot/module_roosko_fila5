<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Exceptions\LotException;
use Modules\Roosko\Guards\WasteAuthorizationPlacementGuard;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Warehouse\Enums\StockUnitStatusEnum;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

describe('WasteAuthorizationPlacementGuard', function (): void {
    test('e\' registrato tra i placement guard di Warehouse', function (): void {
        $guards = config('warehouse.placement_guards');

        Assert::assertIsArray($guards);
        Assert::assertContains(WasteAuthorizationPlacementGuard::class, $guards);
    });

    test('flusso 2 — area con regola per il codice: consentito', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $unit = $plant->receiveAndAccept($plant->plastics, [1000.0])['units'][0];

        Assert::assertNull(app(WasteAuthorizationPlacementGuard::class)->deny($unit, $plant->storageLocation));

        $stored = app(StoreLotAction::class)->execute($unit, $plant->storageLocation);
        Assert::assertSame($plant->storageLocation->id, $stored->location_id);
        Assert::assertSame(StockUnitStatusEnum::from('stored'), $stored->status);
    });

    test('flusso 2 — area senza regole: rifiuto con motivo', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $unit = $plant->receiveAndAccept($plant->plastics, [1000.0])['units'][0];

        $reason = app(WasteAuthorizationPlacementGuard::class)->deny($unit, $plant->freeLocation);
        Assert::assertIsString($reason);
        Assert::assertStringContainsString('FREE-01', $reason);

        expect(static fn () => app(StoreLotAction::class)->execute($unit, $plant->freeLocation))
            ->toThrow(LotException::class, $reason);
        Assert::assertNotSame($plant->freeLocation->id, $unit->refresh()->location_id);
    });

    test('codice non previsto nell\'area: negato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $unit = $plant->receiveAndAccept($plant->plastics, [300.0])['units'][0];

        $reason = app(WasteAuthorizationPlacementGuard::class)->deny($unit, $plant->quarantineLocation);

        Assert::assertIsString($reason);
        Assert::assertStringContainsString('19 12 04', $reason);
    });

    test('limite istantaneo dell\'area superato: negato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $plant->plasticsRule->forceFill(['max_storage_kg' => 1500])->save();
        [$first, $second] = $plant->receiveAndAccept($plant->plastics, [1000.0, 1000.0])['units'];

        app(StoreLotAction::class)->execute($first, $plant->storageLocation);
        $reason = app(WasteAuthorizationPlacementGuard::class)->deny($second, $plant->storageLocation);

        Assert::assertIsString($reason);
        Assert::assertStringContainsString('1.500,000', $reason);
        expect(static fn () => app(StoreLotAction::class)->execute($second, $plant->storageLocation))->toThrow(LotException::class);
    });

    test('limite dell\'autorizzazione d\'impianto superato: negato', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $plant->authorized['19 12 04']->forceFill(['max_storage_kg' => 1200])->save();
        [$first, $second] = $plant->receiveAndAccept($plant->plastics, [700.0, 700.0])['units'];

        app(StoreLotAction::class)->execute($first, $plant->storageLocation);

        Assert::assertIsString(app(WasteAuthorizationPlacementGuard::class)->deny($second, $plant->storageLocation));
    });

    test('spostamento nella stessa area non conta due volte l\'unita\'', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $plant->plasticsRule->forceFill(['max_storage_kg' => 1000])->save();
        $unit = $plant->receiveAndAccept($plant->plastics, [1000.0])['units'][0];
        app(StoreLotAction::class)->execute($unit, $plant->storageLocation);

        Assert::assertNull(app(WasteAuthorizationPlacementGuard::class)->deny($unit->refresh(), $plant->storageLocation));
    });

    test('pericoloso in area senza regole per pericolosi: negato; in quarantena: consentito', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $unit = $plant->receiveAndAccept($plant->oilFilters, [400.0])['units'][0];

        $reason = app(WasteAuthorizationPlacementGuard::class)->deny($unit, $plant->storageLocation);
        Assert::assertIsString($reason);
        Assert::assertStringContainsString('16 01 07', $reason);

        Assert::assertNull(app(WasteAuthorizationPlacementGuard::class)->deny($unit, $plant->quarantineLocation));
    });
});
