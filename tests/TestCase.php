<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\ServiceProvider;
use Modules\Labeling\Providers\LabelingServiceProvider;
use Modules\Processing\Providers\ProcessingServiceProvider;
use Modules\Rentri\Providers\RentriServiceProvider;
use Modules\Roosko\Providers\RooskoServiceProvider;
use Modules\User\Providers\UserServiceProvider;
use Modules\Waste\Providers\WasteServiceProvider;
use Modules\Warehouse\Providers\WarehouseServiceProvider;
use Modules\Weighing\Providers\WeighingServiceProvider;
use Modules\Xot\Tests\XotBaseTestCase;

/**
 * Base dei test Roosko: sqlite condiviso (nessun migrate:fresh), transazione su tutte le
 * connessioni dei sette moduli, rollback a fine test.
 */
abstract class TestCase extends XotBaseTestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    protected $connectionsToTransact = ['sqlite', 'user', 'roosko', 'warehouse', 'labeling', 'weighing', 'processing', 'waste', 'rentri'];

    protected function setUp(): void
    {
        $this->prepareSharedSqliteForTesting();

        parent::setUp();
    }

    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders(Application $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            UserServiceProvider::class,
            LabelingServiceProvider::class,
            WeighingServiceProvider::class,
            WarehouseServiceProvider::class,
            ProcessingServiceProvider::class,
            WasteServiceProvider::class,
            RentriServiceProvider::class,
            RooskoServiceProvider::class,
        ];
    }
}
