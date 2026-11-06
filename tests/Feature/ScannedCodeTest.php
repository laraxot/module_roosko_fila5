<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Labeling\Models\Identifier;
use Modules\Roosko\Actions\ResolveScannedCodeAction;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use PHPUnit\Framework\Assert;

/*
 * Pagina Operatore: scansione dell'etichetta di una posizione (`/l/LOC-{magazzino}-{codice}`,
 * nessun identificatore assegnato) e delle unita' senza identificatore (audit funzionale).
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

test('etichetta della posizione: :dataset', function (string $template): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $location = $plant->storageLocation;
    Assert::assertSame(0, Identifier::query()->where('identifiable_type', $location->getMorphClass())->where('identifiable_id', $location->id)->count());

    $scanned = str_replace('{label}', $location->getLabelCode(), $template);
    $resolved = app(ResolveScannedCodeAction::class)->execute($scanned);

    Assert::assertInstanceOf(Location::class, $resolved);
    Assert::assertSame($location->id, $resolved->id);
})->with([
    'URL assoluto del QR' => ['https://roosko.example/l/{label}'],
    'URL relativo' => ['/l/{label}'],
    'codice dell\'etichetta' => ['{label}'],
    'lettore wedge con CR/LF' => ["{label}\r\n"],
]);

test('LOC con trattini nel codice del magazzino e della posizione', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    // MAG-XXXXXX-STO-01: il magazzino ha un trattino, la posizione anche.
    Assert::assertStringContainsString('-', $plant->warehouse->code);
    Assert::assertStringContainsString('-', $plant->storageLocation->code);

    $resolved = app(ResolveScannedCodeAction::class)->execute(mb_strtolower($plant->storageLocation->getLabelCode()));

    Assert::assertInstanceOf(Location::class, $resolved);
    Assert::assertSame($plant->storageLocation->id, $resolved->id);
});

test('LOC di un magazzino inesistente: non risolto', function (): void {
    Assert::assertNull(app(ResolveScannedCodeAction::class)->execute('/l/LOC-NOPE-STO-01'));
});

test('codice unita\' senza identificatore, anche da URL', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $unit = $plant->receiveAndAccept($plant->plastics, [500.0])['units'][0];
    Identifier::query()->where('identifiable_type', $unit->getMorphClass())->where('identifiable_id', $unit->id)->delete();

    foreach ([$unit->code, '/l/'.$unit->code, mb_strtolower($unit->code)."\t"] as $scanned) {
        $resolved = app(ResolveScannedCodeAction::class)->execute($scanned);
        Assert::assertInstanceOf(StockUnit::class, $resolved);
        Assert::assertSame($unit->id, $resolved->id);
    }
});

test('lettura vuota o solo caratteri di controllo: null', function (): void {
    Assert::assertNull(app(ResolveScannedCodeAction::class)->execute("\r\n"));
});
