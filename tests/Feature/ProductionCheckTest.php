<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Roosko\Actions\RunProductionChecksAction;
use Modules\Roosko\Datas\ProductionCheckData;
use Modules\Roosko\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

/**
 * @return array<string, string>
 */
function productionCheckStatuses(): array
{
    $statuses = [];
    foreach (app(RunProductionChecksAction::class)->execute() as $check) {
        Assert::assertInstanceOf(ProductionCheckData::class, $check);
        $statuses[$check->key] = $check->status;
    }

    return $statuses;
}

test('coda sync e scheduler fermo sono bloccanti', function (): void {
    config(['queue.default' => 'sync']);
    Cache::forget(RunProductionChecksAction::SCHEDULER_HEARTBEAT_KEY);

    $statuses = productionCheckStatuses();

    Assert::assertSame('fail', $statuses['queue']);
    Assert::assertSame('fail', $statuses['scheduler']);
});

test('retry_after della coda deve superare il timeout dei job RENTRI', function (): void {
    config(['queue.default' => 'database', 'queue.connections.database.retry_after' => 90]);
    Assert::assertSame('fail', productionCheckStatuses()['queue.retry_after']);

    config(['queue.connections.database.retry_after' => 600]);
    Assert::assertSame('ok', productionCheckStatuses()['queue.retry_after']);
});

test('lo scheduler risulta attivo dopo il battito', function (): void {
    Cache::put(RunProductionChecksAction::SCHEDULER_HEARTBEAT_KEY, now()->toIso8601String(), 3600);

    Assert::assertSame('ok', productionCheckStatuses()['scheduler']);
});

test('APP_DEBUG acceso e\' bloccante solo in produzione', function (): void {
    config(['app.debug' => true]);
    Assert::assertSame('warn', productionCheckStatuses()['app.debug']);

    app()->detectEnvironment(static fn (): string => 'production');
    try {
        Assert::assertSame('fail', productionCheckStatuses()['app.debug']);
    } finally {
        app()->detectEnvironment(static fn (): string => 'testing');
    }
});
