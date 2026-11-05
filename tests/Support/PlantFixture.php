<?php

declare(strict_types=1);

namespace Modules\Roosko\Tests\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Modules\Processing\Models\Machine;
use Modules\Roosko\Actions\AcceptDeliveryAction;
use Modules\Roosko\Actions\ReceiveDeliveryAction;
use Modules\Roosko\Actions\WeighDeliveryAction;
use Modules\Roosko\Datas\AcceptedUnitData;
use Modules\Roosko\Datas\DeliveryAcceptanceData;
use Modules\Roosko\Models\AreaWasteRule;
use Modules\Roosko\Models\Delivery;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\AuthorizedWaste;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\PlantAuthorization;
use Modules\Waste\Models\Register;
use Modules\Warehouse\Enums\ContainerTypeEnum;
use Modules\Warehouse\Models\Area;
use Modules\Warehouse\Models\Location;
use Modules\Warehouse\Models\StockUnit;
use Modules\Warehouse\Models\Warehouse;

/**
 * Impianto di test completo, costruito con le colonne della SPEC sez. 3:
 * produttore, impianto (sito + autorizzazione + registro), destinatario terzo, magazzino con
 * area di stoccaggio (19 12 04, 19 12 12), area di quarantena (16 01 07*), area senza regole,
 * un trituratore.
 */
final class PlantFixture
{
    public Operator $producer;

    public Operator $plantOperator;

    public Operator $recipient;

    public OperatorSite $plant;

    public PlantAuthorization $authorization;

    public Register $register;

    public EwcCode $plastics;

    public EwcCode $oilFilters;

    public EwcCode $mechanicalResidue;

    /** @var array<string, AuthorizedWaste> codice EER => autorizzato */
    public array $authorized = [];

    public Warehouse $warehouse;

    public Area $storageArea;

    public Area $quarantineArea;

    public Area $freeArea;

    public Location $storageLocation;

    public Location $quarantineLocation;

    public Location $freeLocation;

    public AreaWasteRule $plasticsRule;

    public Machine $shredder;

    public static function build(): self
    {
        $fixture = new self;
        $suffix = Str::upper(Str::random(6));

        $fixture->producer = self::operator('Produttore '.$suffix, ['producer']);
        $fixture->plantOperator = self::operator('Impianto '.$suffix, ['holder', 'recipient', 'producer']);
        $fixture->recipient = self::operator('Recuperi '.$suffix, ['recipient']);

        $fixture->plant = OperatorSite::factory()->create([
            'operator_id' => $fixture->plantOperator->id,
            'name' => 'Impianto di cernita '.$suffix,
            'is_plant' => true,
            'is_active' => true,
        ]);
        $fixture->authorization = PlantAuthorization::factory()->create([
            'operator_site_id' => $fixture->plant->id,
            'type' => 'ordinary_208',
            'number' => 'AUT-'.$suffix,
            'issuing_authority' => 'Regione',
            'issued_at' => CarbonImmutable::now()->subYear(),
            'expires_at' => CarbonImmutable::now()->addYears(5),
            'is_active' => true,
        ]);
        $fixture->register = Register::factory()->create([
            'operator_site_id' => $fixture->plant->id,
            'name' => 'Registro C/S '.$suffix,
            'is_active' => true,
        ]);

        $fixture->plastics = self::ewc('19 12 04', 'plastica e gomma', false);
        $fixture->oilFilters = self::ewc('16 01 07', 'filtri dell\'olio', true);
        $fixture->mechanicalResidue = self::ewc('19 12 12', 'altri rifiuti (compresi materiali misti) prodotti dal trattamento meccanico dei rifiuti', false);

        foreach ([$fixture->plastics, $fixture->oilFilters, $fixture->mechanicalResidue] as $ewc) {
            $fixture->authorized[$ewc->code] = AuthorizedWaste::factory()->create([
                'plant_authorization_id' => $fixture->authorization->id,
                'ewc_code_id' => $ewc->id,
                'operations' => ['R13', 'R12', 'R3'],
                'max_storage_kg' => 10000,
                'max_annual_kg' => 500000,
            ]);
        }

        $fixture->warehouse = Warehouse::factory()->create(['code' => 'MAG-'.$suffix, 'name' => 'Magazzino '.$suffix, 'is_active' => true]);
        $fixture->storageArea = self::area($fixture->warehouse, 'STO', 'storage');
        $fixture->quarantineArea = self::area($fixture->warehouse, 'QUA', 'quarantine');
        $fixture->freeArea = self::area($fixture->warehouse, 'FREE', 'storage');
        $fixture->storageLocation = self::location($fixture->storageArea, 'STO-01');
        $fixture->quarantineLocation = self::location($fixture->quarantineArea, 'QUA-01');
        $fixture->freeLocation = self::location($fixture->freeArea, 'FREE-01');

        $fixture->plasticsRule = self::rule($fixture->storageArea, $fixture->authorized['19 12 04']);
        self::rule($fixture->storageArea, $fixture->authorized['19 12 12']);
        self::rule($fixture->quarantineArea, $fixture->authorized['16 01 07']);

        $fixture->shredder = Machine::factory()->create([
            'code' => 'TRI-'.$suffix,
            'name' => 'Trituratore',
            'type' => 'shredder',
            'is_active' => true,
        ]);

        return $fixture;
    }

    public function fir(EwcCode $ewc, float $quantityKg = 2000.0, bool $digital = false): Fir
    {
        return Fir::factory()->create([
            'number' => 'FIR-'.Str::upper(Str::random(8)),
            'is_digital' => $digital,
            'status' => 'in_transit',
            'producer_operator_id' => $this->producer->id,
            'recipient_operator_id' => $this->plantOperator->id,
            'recipient_site_id' => $this->plant->id,
            'ewc_code_id' => $ewc->id,
            'physical_state' => 'solid_non_powdery',
            'hazard_properties' => $ewc->is_hazardous ? ['HP14'] : [],
            'operation' => 'R13',
            'quantity' => $quantityKg,
            'quantity_unit' => 'kg',
            'is_quantity_estimated' => true,
        ]);
    }

    /**
     * Flusso 1 della SPEC: arrivo con lordo, tara, accettazione con un'unita' per peso.
     *
     * @param  list<float>  $unitsKg
     * @return array{delivery: Delivery, units: list<StockUnit>, fir: Fir}
     */
    public function receiveAndAccept(EwcCode $ewc, array $unitsKg = [1000.0, 1000.0], bool $digital = false): array
    {
        $totalKg = array_sum($unitsKg);
        $fir = $this->fir($ewc, $totalKg, $digital);

        $delivery = app(ReceiveDeliveryAction::class)->execute($fir, $this->plant, $this->warehouse, $totalKg + 14000.0);
        $delivery = app(WeighDeliveryAction::class)->execute($delivery, 14000.0);
        $delivery = app(AcceptDeliveryAction::class)->execute($delivery, new DeliveryAcceptanceData(
            acceptedKg: $totalKg,
            units: array_map(static fn (float $kg): AcceptedUnitData => new AcceptedUnitData(ContainerTypeEnum::from('big_bag'), $kg, 2.5), $unitsKg),
            operation: WasteOperationEnum::from('R13'),
        ));

        /** @var list<StockUnit> $units */
        $units = $delivery->wasteLots()->orderBy('id')->get()
            ->map(static fn ($lot): StockUnit => StockUnit::query()->where('subject_type', $lot->getMorphClass())->where('subject_id', $lot->id)->firstOrFail())
            ->values()
            ->all();

        return ['delivery' => $delivery, 'units' => $units, 'fir' => $fir];
    }

    /**
     * @param  list<string>  $roles
     */
    private static function operator(string $name, array $roles): Operator
    {
        return Operator::factory()->create([
            'business_name' => $name,
            'fiscal_code' => (string) random_int(10000000000, 99999999999),
            'roles' => $roles,
            'is_active' => true,
        ]);
    }

    private static function ewc(string $code, string $description, bool $hazardous): EwcCode
    {
        return EwcCode::query()->firstOrCreate(['code' => $code], [
            'chapter' => substr($code, 0, 2),
            'sub_chapter' => substr($code, 0, 5),
            'description' => $description,
            'is_hazardous' => $hazardous,
            'is_mirror' => false,
            'is_active' => true,
        ]);
    }

    private static function area(Warehouse $warehouse, string $code, string $type): Area
    {
        return Area::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => $code,
            'name' => $code,
            'type' => $type,
            'max_weight_kg' => null,
            'is_active' => true,
        ]);
    }

    private static function location(Area $area, string $code): Location
    {
        return Location::factory()->create([
            'area_id' => $area->id,
            'code' => $code,
            'max_weight_kg' => null,
            'is_active' => true,
        ]);
    }

    private static function rule(Area $area, AuthorizedWaste $authorized): AreaWasteRule
    {
        return AreaWasteRule::query()->create([
            'area_id' => $area->id,
            'authorized_waste_id' => $authorized->id,
            'operation' => WasteOperationEnum::from('R13'),
        ]);
    }
}
