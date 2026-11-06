<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Unit;

use Modules\Roosko\Datas\AcceptedUnitData;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\AcceptanceEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

/**
 * @param  list<float>  $unitsKg
 */
function acceptanceData(float $acceptedKg, array $unitsKg, float $rejectedKg = 0.0): DeliveryAcceptanceData
{
    return new DeliveryAcceptanceData(
        acceptedKg: $acceptedKg,
        units: array_map(static fn (float $kg): AcceptedUnitData => new AcceptedUnitData(ContainerTypeEnum::BIG_BAG, $kg), $unitsKg),
        operation: WasteOperationEnum::R13,
        rejectedKg: $rejectedKg,
    );
}

/**
 * @param array<array-key, mixed> $unitsKg
 * @return list<float>
 */
function normalizedUnitWeights(array $unitsKg): array
{
    $weights = [];
    foreach ($unitsKg as $weight) {
        if (! is_int($weight) && ! is_float($weight)) {
            throw new \InvalidArgumentException('Dataset unit weight must be numeric.');
        }
        $weights[] = (float) $weight;
    }

    return $weights;
}

describe('DeliveryAcceptanceData', function (): void {
    test('somma le unita\' arrotondando ai grammi', function (): void {
        Assert::assertSame(1000.0, acceptanceData(1000.0, [333.3333, 333.3333, 333.3334])->unitsTotalKg());
    });

    test('tolleranza di 1 g sui kg accettati', function (float $acceptedKg, array $unitsKg, bool $matches): void {
        Assert::assertSame($matches, acceptanceData($acceptedKg, normalizedUnitWeights($unitsKg))->unitsMatchAcceptedKg());
    })->with([
        'esatto' => [2000.0, [1200.0, 800.0], true],
        'entro tolleranza' => [2000.0, [1200.0, 800.001], true],
        'fuori tolleranza' => [2000.0, [1200.0, 800.002], false],
        'mancano unita\'' => [2000.0, [1200.0], false],
    ]);

    test('accettazione totale o parziale dai kg respinti', function (): void {
        Assert::assertSame(AcceptanceEnum::FULL, acceptanceData(100.0, [100.0])->acceptance());
        Assert::assertSame(AcceptanceEnum::PARTIAL, acceptanceData(100.0, [100.0], 5.0)->acceptance());
    });

    test('dallo stato del form con repeater', function (): void {
        $data = DeliveryAcceptanceData::fromFormState([
            'accepted_kg' => '1500.5',
            'rejected_kg' => '',
            'operation' => 'R13',
            'rejection_reason' => '  ',
            'units' => [
                ['container_type' => 'drum', 'net_weight_kg' => '1000', 'tare_weight_kg' => '', 'location_id' => '7'],
                ['container_type' => 'ibc', 'net_weight_kg' => '500.5', 'tare_weight_kg' => '60', 'location_id' => null],
            ],
        ]);

        Assert::assertSame(1500.5, $data->acceptedKg);
        Assert::assertSame(0.0, $data->rejectedKg);
        Assert::assertNull($data->rejectionReason);
        Assert::assertCount(2, $data->units);
        Assert::assertSame(7, $data->units[0]->locationId);
        Assert::assertNull($data->units[0]->tareWeightKg);
        Assert::assertSame(60.0, $data->units[1]->tareWeightKg);
        Assert::assertTrue($data->unitsMatchAcceptedKg());
    });
});
