<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Roosko\Actions\RunInPlantTransactionAction;
use Modules\Roosko\Tests\TestCase;
use PHPUnit\Framework\Assert;

/*
 * Le connessioni dei moduli che sono la stessa istanza (alias del modulo Tenant, o sqlite
 * condiviso nei test) aprono UNA sola transazione, non una per nome.
 */

uses(TestCase::class);

test('una sola transazione per istanza di connessione condivisa', function (): void {
    $connection = DB::connection('roosko');
    Assert::assertSame($connection, DB::connection('waste'));
    $before = $connection->transactionLevel();

    $level = app(RunInPlantTransactionAction::class)->execute(static fn (): int => DB::connection('waste')->transactionLevel());

    Assert::assertSame($before + 1, $level);
});
