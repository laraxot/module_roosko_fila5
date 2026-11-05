<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Unit;

use Modules\Roosko\Actions\AddShipmentItemAction;
use Modules\Roosko\Actions\GenerateDocumentCodeAction;
use Modules\Roosko\Datas\StockSummaryRowData;
use Modules\Roosko\Enums\DeliveryStatusEnum;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Enums\ShipmentDocumentEnum;
use Modules\Roosko\Enums\ShipmentStatusEnum;
use Modules\Roosko\Tests\TestCase;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

describe('transizioni di stato', function (): void {
    test('conferimento', function (): void {
        Assert::assertTrue(DeliveryStatusEnum::ARRIVED->canTransitionTo(DeliveryStatusEnum::WEIGHED));
        Assert::assertTrue(DeliveryStatusEnum::WEIGHED->canTransitionTo(DeliveryStatusEnum::ACCEPTED));
        Assert::assertTrue(DeliveryStatusEnum::ARRIVED->canTransitionTo(DeliveryStatusEnum::ACCEPTED));
        Assert::assertFalse(DeliveryStatusEnum::ACCEPTED->canTransitionTo(DeliveryStatusEnum::REJECTED));
        Assert::assertFalse(DeliveryStatusEnum::REJECTED->canTransitionTo(DeliveryStatusEnum::ACCEPTED));
        Assert::assertSame([], DeliveryStatusEnum::CLOSED->allowedTransitions());
    });

    test('uscita', function (): void {
        Assert::assertTrue(ShipmentStatusEnum::LOADING->canTransitionTo(ShipmentStatusEnum::DISPATCHED));
        Assert::assertFalse(ShipmentStatusEnum::DRAFT->canTransitionTo(ShipmentStatusEnum::DISPATCHED));
        Assert::assertFalse(ShipmentStatusEnum::DISPATCHED->canTransitionTo(ShipmentStatusEnum::CANCELLED));
        Assert::assertTrue(ShipmentStatusEnum::READY->acceptsItems());
        Assert::assertFalse(ShipmentStatusEnum::DISPATCHED->acceptsItems());
    });
});

describe('documento e lotto', function (): void {
    test('compatibilita\' documento / classificazione', function (): void {
        Assert::assertTrue(ShipmentDocumentEnum::FIR->accepts(LotClassificationEnum::WASTE));
        Assert::assertFalse(ShipmentDocumentEnum::FIR->accepts(LotClassificationEnum::END_OF_WASTE));
        Assert::assertTrue(ShipmentDocumentEnum::EOW_DECLARATION->accepts(LotClassificationEnum::END_OF_WASTE));
        Assert::assertFalse(ShipmentDocumentEnum::EOW_DECLARATION->accepts(LotClassificationEnum::BY_PRODUCT));
        Assert::assertTrue(ShipmentDocumentEnum::TRANSPORT_DOCUMENT->accepts(LotClassificationEnum::BY_PRODUCT));
    });

    test('destini ammessi per documento', function (): void {
        Assert::assertNotContains(LotDestinationEnum::SALE, AddShipmentItemAction::destinationsFor(ShipmentDocumentEnum::FIR));
        Assert::assertSame([LotDestinationEnum::SALE], AddShipmentItemAction::destinationsFor(ShipmentDocumentEnum::EOW_DECLARATION));
    });
});

describe('calcoli', function (): void {
    test('formato dei progressivi', function (): void {
        Assert::assertSame('CNF-2026-000042', GenerateDocumentCodeAction::format('CNF-2026-', 42));
        Assert::assertSame('USC-2026-1234567', GenerateDocumentCodeAction::format('USC-2026-', 1234567));
    });

    test('percentuale d\'uso del limite autorizzato', function (): void {
        Assert::assertSame(50.0, StockSummaryRowData::percentage(5000.0, 10000.0));
        Assert::assertSame(33.33, StockSummaryRowData::percentage(1.0, 3.0));
        Assert::assertNull(StockSummaryRowData::percentage(100.0, null));
        Assert::assertNull(StockSummaryRowData::percentage(100.0, 0.0));
    });

    test('colore per soglia', function (?float $usage, string $color): void {
        Assert::assertSame($color, StockSummaryRowData::usageColor($usage, 80.0, 95.0));
    })->with([
        [null, 'gray'],
        [10.0, 'success'],
        [80.0, 'warning'],
        [95.0, 'danger'],
        [120.0, 'danger'],
    ]);
});
