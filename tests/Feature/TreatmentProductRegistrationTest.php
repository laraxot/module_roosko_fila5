<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Processing\Enums\OutputKindEnum;
use Modules\Processing\Models\ProcessingOutput;
use Modules\Roosko\Actions\RegisterTreatmentOutputAction;
use Modules\Roosko\Actions\StartTreatmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Datas\TreatmentOutputData;
use Modules\Roosko\Enums\LotClassificationEnum;
use Modules\Roosko\Enums\LotDestinationEnum;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\RegisterEntry;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

describe('"Registra prodotto" nella pagina Lavorazioni in corso', function (): void {
    test('dallo stato del form: lotto con EER, unita\' in posizione e carico di nuova produzione', function (): void {
        /** @var PlantFixture $plant */
        $plant = $this->plant;
        $input = $plant->receiveAndAccept($plant->plastics, [800.0])['units'][0];
        app(StoreLotAction::class)->execute($input, $plant->storageLocation);
        $order = app(StartTreatmentAction::class)->execute($plant->shredder, WasteOperationEnum::R12, collect([$input]));
        $loadsBefore = RegisterEntry::query()->where('register_id', $plant->register->id)->where('cause', 'new_production')->count();

        // Stato come lo consegna il form Filament: select e numeri in stringa, campi nascosti assenti.
        $data = TreatmentOutputData::fromFormState([
            'ewc_code_id' => (string) $plant->mechanicalResidue->id,
            'physical_state' => 'solid_non_powdery',
            'classification' => 'waste',
            'destination' => 'disposal',
            'kind' => 'scrap',
            'container_type' => 'skip',
            'net_weight_kg' => '120.5',
            'tare_weight_kg' => '',
            'location_id' => (string) $plant->storageLocation->id,
        ]);
        $unit = app(RegisterTreatmentOutputAction::class)->execute($order, $data);

        Assert::assertSame([], $data->hazardProperties);
        Assert::assertNull($data->eowBatchCode);
        Assert::assertNull($data->tareWeightKg);

        $lot = $unit->subject;
        Assert::assertInstanceOf(WasteLot::class, $lot);
        Assert::assertSame($plant->mechanicalResidue->id, $lot->ewc_code_id);
        Assert::assertSame(LotClassificationEnum::WASTE, $lot->classification);
        Assert::assertSame(LotDestinationEnum::DISPOSAL, $lot->destination);
        Assert::assertSame($order->id, $lot->processing_order_id);
        Assert::assertSame($plant->warehouse->id, $unit->warehouse_id);
        Assert::assertSame($plant->storageLocation->id, $unit->location_id);
        Assert::assertEqualsWithDelta(120.5, (float) $unit->net_weight_kg, 0.0001);

        $output = ProcessingOutput::query()->where('stock_unit_id', $unit->id)->firstOrFail();
        Assert::assertSame(OutputKindEnum::SCRAP, $output->kind);
        Assert::assertSame($loadsBefore + 1, RegisterEntry::query()->where('register_id', $plant->register->id)->where('cause', 'new_production')->count());
    });

    test('tipo output assente: prodotto; codice EoW ripulito dagli spazi', function (): void {
        $data = TreatmentOutputData::fromFormState([
            'ewc_code_id' => '1',
            'physical_state' => 'solid_non_powdery',
            'classification' => 'end_of_waste',
            'destination' => 'sale',
            'container_type' => 'big_bag',
            'net_weight_kg' => '500',
            'eow_batch_code' => '  EOW-2026-007 ',
        ]);

        Assert::assertSame(OutputKindEnum::PRODUCT, $data->kind);
        Assert::assertSame('EOW-2026-007', $data->eowBatchCode);
        Assert::assertNull($data->locationId);
    });
});
