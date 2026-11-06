<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Modules\Roosko\Tests\TestCase;
use PHPUnit\Framework\Assert;

/*
 * Audit funzionale 1.4: con Roosko attivo i bottoni nativi "Aggiungi input" e "Registra output"
 * del pannello Processing (che non scrivono registro, lotto e QR) sono spenti.
 */

uses(TestCase::class);

test('Roosko spegne le azioni native sul materiale di Processing', function (): void {
    Assert::assertFalse(config('processing.material_actions_enabled'));
});
