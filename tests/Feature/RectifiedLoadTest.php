<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Roosko\Actions\GetLotLoadEntryIdsAction;
use Modules\Roosko\Actions\StartTreatmentAction;
use Modules\Roosko\Actions\StoreLotAction;
use Modules\Roosko\Models\WasteLot;
use Modules\Roosko\Tests\Support\PlantFixture;
use Modules\Roosko\Tests\TestCase;
use Modules\Waste\Actions\RectifyRegisterEntryAction;
use Modules\Waste\Datas\RegisterEntryData;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\RegisterEntry;
use PHPUnit\Framework\Assert;

/*
 * Audit database R3: dopo la rettifica del carico di un conferimento gli scarichi dei suoi lotti
 * devono riferirsi alla rettifica, non all'originale sostituito.
 */

uses(TestCase::class);

beforeEach(function (): void {
    Queue::fake();
    $this->plant = PlantFixture::build();
});

test('dopo la rettifica del carico il trattamento scarica con load_entry_ids = [rettifica]', function (): void {
    /** @var PlantFixture $plant */
    $plant = $this->plant;
    $result = $plant->receiveAndAccept($plant->plastics, [1000.0]);
    $unit = $result['units'][0];
    app(StoreLotAction::class)->execute($unit, $plant->storageLocation);

    $load = RegisterEntry::query()
        ->where('reference_type', $result['delivery']->getMorphClass())
        ->where('reference_id', $result['delivery']->id)
        ->where('type', 'load')
        ->firstOrFail();
    $corrected = RegisterEntryData::fromEntry($load);
    $corrected->quantityKg = 1000.0;
    $corrected->notes = 'Correzione della controparte';
    $rectification = app(RectifyRegisterEntryAction::class)->execute($load, $corrected, 'Errore di trascrizione');

    $lot = WasteLot::query()->where('delivery_id', $result['delivery']->id)->firstOrFail();
    Assert::assertSame([$rectification->id], app(GetLotLoadEntryIdsAction::class)->execute($lot));

    app(StartTreatmentAction::class)->execute($plant->shredder, WasteOperationEnum::R12, collect([$unit]));

    $unload = RegisterEntry::query()->where('register_id', $plant->register->id)->where('type', 'unload')->orderByDesc('id')->firstOrFail();
    Assert::assertSame([$rectification->id], array_values(array_map(intval(...), $unload->load_entry_ids)));
});
