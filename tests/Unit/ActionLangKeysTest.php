<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Unit;

use Modules\Roosko\Tests\TestCase;
use Modules\Xot\Actions\GetTransKeyAction;
use PHPUnit\Framework\Assert;

/*
 * Ogni Filament action di Roosko trova il suo file lang con la chiave ricavata dalla classe
 * (GetTransKeyAction): "Nuova uscita" mostrava le chiavi grezze perche' la classe
 * CreateShipmentHeaderAction risolve a `shipment_header`, non a `create_shipment_header`.
 */

uses(TestCase::class);

test('la chiave lang di :dataset esiste in it ed en', function (string $class): void {
    $key = app(GetTransKeyAction::class)->execute('Modules\\Roosko\\Filament\\Actions\\'.$class);
    $file = substr($key, \strlen('roosko::'));

    foreach (['it', 'en'] as $locale) {
        Assert::assertFileExists(__DIR__.'/../../lang/'.$locale.'/'.$file.'.php', $class.' → '.$key);
        Assert::assertNotSame($key.'.error', __($key.'.error', [], $locale), $class.' senza testo di errore in '.$locale);
    }
})->with(array_map(
    static fn (string $path): string => basename($path, '.php'),
    glob(__DIR__.'/../../app/Filament/Actions/*Action.php') ?: [],
));

test('Nuova uscita risolve a roosko::shipment_header', function (): void {
    Assert::assertSame('roosko::shipment_header', app(GetTransKeyAction::class)->execute('Modules\\Roosko\\Filament\\Actions\\CreateShipmentHeaderAction'));
});
