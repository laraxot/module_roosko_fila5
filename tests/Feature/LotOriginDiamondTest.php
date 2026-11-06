<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Processing\Models\LineageLink;
use Modules\Processing\Models\ProcessingOrder;
use Modules\Roosko\Actions\TraceLotOriginAction;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Warehouse\Models\StockUnit;
use PHPUnit\Framework\Assert;

/*
 * Audit database R17: in un grafo a diamante (prodotto ← A, B; A ← X, B ← X) l'origine X
 * pesa la somma delle quote dei due cammini, non solo l'ultimo.
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

test('grafo a diamante: quota dell\'origine = somma dei cammini', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $origin = $plant->receiveAndAccept($plant->plastics, [1000.0]);
    $x = $origin['units'][0];
    $order = ProcessingOrder::factory()->create();
    $a = StockUnit::factory()->create(['warehouse_id' => $plant->warehouse->id]);
    $b = StockUnit::factory()->create(['warehouse_id' => $plant->warehouse->id]);
    $product = StockUnit::factory()->create(['warehouse_id' => $plant->warehouse->id]);

    foreach ([[$x, $a, 1.0, 500.0], [$x, $b, 1.0, 500.0], [$a, $product, 0.5, 400.0], [$b, $product, 0.5, 400.0]] as [$parent, $child, $share, $kg]) {
        LineageLink::query()->create([
            'processing_order_id' => $order->id,
            'parent_stock_unit_id' => $parent->id,
            'child_stock_unit_id' => $child->id,
            'share' => $share,
            'attributed_kg' => $kg,
        ]);
    }

    $rows = app(TraceLotOriginAction::class)->execute($product);

    Assert::assertCount(1, $rows);
    $row = $rows->first();
    Assert::assertSame($x->id, $row['unit']->id);
    Assert::assertEqualsWithDelta(1.0, $row['share'], 0.000001);
    Assert::assertSame($origin['delivery']->id, $row['delivery']?->id);
    Assert::assertSame($origin['fir']->id, $row['fir']?->id);
});
