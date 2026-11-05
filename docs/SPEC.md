# Roosko — specifica condivisa dei moduli (Laraxot / Filament 5 / Laravel 13)

Roosko gestisce i rifiuti (pericolosi e non) in ingresso a un impianto di trattamento/cernita:
conferimento con FIR e pesata → identificazione dell'unità (QR / RFID) → stoccaggio mappato
nelle aree autorizzate → lavorazione (es. triturazione) con nuove unità e nuova etichetta →
ri-stoccaggio → uscita (vendita End of Waste, recupero/smaltimento presso terzi, bonifica),
con registro cronologico di carico/scarico e interoperabilità RENTRI.

Il progetto è diviso in **7 moduli nwidart, ognuno un repository git indipendente**, con nomi in
inglese. I moduli generici non sanno nulla dei rifiuti; il dominio rifiuti sta in `Waste`;
l'orchestrazione di prodotto sta in `Roosko`.

```
Labeling   ─┐
Weighing   ─┤ (generici, dipendono solo da Xot)
Warehouse  ─┘ ← Labeling
Processing    ← Warehouse
Waste         ← Geo (indirizzi)      (dominio normativo rifiuti, riusabile)
Rentri        ← Waste                (client API RENTRI)
Roosko        ← tutti                (prodotto: conferimenti, lotti, uscite, UI operatore)
```

Output: `/home/claude/roosko/Modules/{Name}/`.

---

## 1. Convenzioni Laraxot (OBBLIGATORIE)

Riferimenti reali da leggere prima di scrivere (repo clonati, NON copiarli nel modulo):
- `$LX/module_xot_fila5` — base: `app/Models/XotBaseModel.php`, `app/Database/Migrations/XotBaseMigration.php`,
  `app/Filament/Resources/XotBaseResource.php`, `app/Filament/Resources/Schemas/XotBaseResourceForm.php`,
  `app/Filament/Resources/Schemas/XotBaseResourceInfolist.php`, `app/Filament/Resources/Tables/XotBaseResourceTable.php`,
  `app/Filament/Resources/Pages/*`, `app/Filament/Resources/RelationManagers/XotBaseRelationManager.php`,
  `app/Filament/Pages/XotBasePage.php`, `app/Filament/Widgets/*`, `app/Traits/EnumTrait.php`,
  `app/Providers/XotBase*ServiceProvider.php`, `app/Providers/Filament/XotBasePanelProvider.php`,
  esempio completo: `app/Filament/Resources/ExtraResource*` + `lang/it/extra*.php`.
- `$LX/module_activity_fila5` — scheletro di un modulo non-core: `module.json`, `composer.json`, `config/config.php`,
  `app/Providers/*`, `app/Models/BaseModel.php`, `app/Models/Policies/*`, migrazioni, factory, seeder, `tests/`.
- `$LX/module_geo_fila5` — `app/Models/Traits/HasAddress.php`, `app/Models/Address.php`.

dove `$LX=/tmp/claude-0/-home-claude/4fcc8777-1858-56a7-b50f-632ae526dc1e/scratchpad/lx`.

Sorgenti delle dipendenze (per verificare namespace e firme reali, non inventarli):
`/home/claude/src/` → `laravel_framework` (13.x), `filamentphp_filament` (5.x, pacchetti in `packages/*/src`),
`livewire_livewire`, `spatie_laravel-queueable-action`, `spatie_laravel-data`, `spatie_laravel-medialibrary`,
`spatie_laravel-schemaless-attributes`, `spatie_laravel-permission`, `chillerlan_php-qrcode`, `firebase_php-jwt`,
`webmozarts_assert`, `nWidart_laravel-modules`, `laraxot_module_ui_fila5`, `laraxot_module_user_fila5`,
`laraxot_module_media_fila5`, `laraxot_module_lang_fila5`. In Filament 5: componenti form in `Filament\Forms\Components\*`,
layout (Section, Grid, Tabs, Wizard\Step, Fieldset) in `Filament\Schemas\Components\*`, `Filament\Schemas\Schema`,
azioni in `Filament\Actions\*` (anche quelle di tabella: `EditAction`, `ViewAction`, `DeleteBulkAction`…),
colonne in `Filament\Tables\Columns\*`, entry in `Filament\Infolists\Components\*`. Prima di usare una classe, verificane l'esistenza con grep.

Regole:
1. `declare(strict_types=1);` ovunque. PHP 8.3+, tipi stretti, PHPDoc generics per PHPStan livello 10 (`@return BelongsTo<Area, $this>`, `@property` sui modelli). `mixed` solo come ultima spiaggia.
2. **Mai estendere Filament direttamente**: `XotBaseResource`, `XotBaseListRecords`, `XotBaseCreateRecord`, `XotBaseEditRecord`, `XotBaseViewRecord`, `XotBaseRelationManager`, `XotBasePage` (`Modules\Xot\Filament\Pages\XotBasePage` per pagine standalone), widget `XotBase*Widget`.
3. Resource: solo `$model`, `getRelations()`, `getPages()`. Form in `{Resource}/Schemas/{Model}Form.php` (`extends XotBaseResourceForm`, `getFormSchema(): array` con **chiavi stringa = nome campo**), infolist in `{Resource}/Schemas/{Model}Infolist.php`, tabella in `{Resource}/Tables/{ModelPlurale}Table.php` (`extends XotBaseResourceTable`, `getTableColumns()`, `getTableFilters()`, `getTableActions()`, `getTableBulkActions()`, tutti array con chiavi stringa). Le pagine List/Create/Edit/View contengono solo `$resource` (più header actions se servono).
4. **Niente `->label()`, `->placeholder()`, `->helperText()`, `->tooltip()` con testo**: le etichette arrivano dai file `lang/{it,en}/{snake_model}.php` (struttura `navigation`, `label`, `plural_label`, `fields.{campo}.{label,placeholder,helper_text,tooltip}`, `actions.{azione}.{label,tooltip,modal_heading,modal_description,success,error}`). Ogni Resource/Enum/Page/Widget ha il suo file lang in it **ed** en.
5. **Niente Services, niente Jobs**: logica in Spatie Queueable Actions (`use QueueableAction; public function execute(...)`), chiamate con `app(XAction::class)->execute(...)` o `->onQueue()->execute(...)` per l'asincrono. Una action = un verbo di dominio. Le Filament actions (bottoni) chiamano le Actions di dominio, non contengono logica.
6. DTO con `spatie/laravel-data` in `app/Datas/` (`XxxData extends Data`).
7. Stati: **backed enum** con `use EnumTrait;` che implementa `HasLabel, HasColor, HasIcon` (label/colore/icona da `lang/{it,en}/{snake_enum}.php` sotto `values.{value}.{label,color,icon,description}`). Le transizioni consentite sono un metodo dell'enum `canTransitionTo(self $to): bool` e le action fanno `Assert::true($from->canTransitionTo($to))`. NON usare spatie/laravel-model-states.
8. Modelli: `abstract class BaseModel extends XotBaseModel` per modulo con `protected $connection = '{modulo_lowercase}';`. Modelli concreti `extends BaseModel`, attributi `$fillable`, `casts()` che fa `array_merge(parent::casts(), [...])`, relazioni tipizzate. Chiavi primarie `bigIncrements` (id int). Audit `created_by/updated_by` gestito da `Updater` di Xot: nelle migrazioni usare `$this->updateTimestamps($table, true)` dentro `tableUpdate` come fa Activity.
9. Migrazioni: `return new class extends XotBaseMigration { public function up(): void { $this->tableCreate(fn (Blueprint $table) => ...); $this->tableUpdate(function (Blueprint $table): void { ... hasColumn guards ...; $this->updateTimestamps($table, true); }); } };` Nome file `YYYY_MM_DD_HHMMSS_create_{tabella}_table.php` (XotBaseMigration ricava il modello dal nome file). Le FK cross-modulo sono **colonne indicizzate senza vincolo** (`unsignedBigInteger(...)->index()`), perché i moduli possono stare su connessioni/DB diversi. FK interne al modulo: `foreignId` normale va bene ma senza `constrained()` se il modello è su connessione modulo (per sicurezza: solo `->index()`).
10. Modelli immutabili (log): override `boot`/`booted` che lancia eccezione su `updating`/`deleting`.
11. Policy in `app/Models/Policies/{Model}Policy.php` che estendono `Modules\User\Models\Policies\UserBasePolicy` con permessi `{model_snake}.{viewAny,view,create,update,delete}` come in Activity.
12. Provider: `{Name}ServiceProvider extends XotBaseServiceProvider` (`$name`, `$moduleDir = __DIR__`, `$moduleNs = __NAMESPACE__`), `RouteServiceProvider extends XotBaseRouteServiceProvider`, `Filament/AdminPanelProvider extends XotBasePanelProvider` (`protected string $module = '{Name}';`). `module.json` con `requires` corretti. `composer.json` come Activity (psr-4 `Modules\\{Name}\\` → `app/`, factories, seeders, tests), autore Marco Sottana, versioni `*` (cavalchiamo l'onda), solo dipendenze NUOVE rispetto a Xot.
13. Config modulo in `config/config.php` (merge come Activity) per i punti di estensione reali (non per comportamenti invarianti).
14. Test Pest v5 in `tests/` come Activity: `tests/TestCase.php extends XotBaseTestCase`, `tests/Pest.php`, file con `uses(TestCase::class);`, `describe/test`, `Assert` di PHPUnit. Test unitari per la logica pura (calcoli) e feature per le action principali.
15. `docs/README.md` per modulo: scopo, modelli, actions, punti di estensione, dipendenze. Niente changelog.
16. Factory per ogni modello (`database/factories/{Model}Factory.php`), seeder orchestratore `{Name}DatabaseSeeder` + seeder per modello dove ha senso.
17. Naming di dominio, non di implementazione (DRY, KISS). Niente proprietà configurabili per comportamenti invarianti. Niente stato duplicato tra layer.
18. Asset JS con npm/vite del modulo (niente CDN).

Quantità: pesi sempre in **kg** `decimal(14,3)` (cast `decimal:3`, esposti come `float` nelle Data). Date `timestamp`/`datetime` immutabili `immutable_datetime`.

---

## 2. Contratti cross-modulo (NOMI ESATTI — non cambiarli)

### Labeling (`Modules\Labeling`)
```php
namespace Modules\Labeling\Contracts;
interface LabelableContract {
    /** Codice leggibile stampato e codificato nel QR (univoco per tipo di modello). */
    public function getLabelCode(): string;
    /** @return array<string, string|int|float|null> segnaposti per i template */
    public function getLabelData(): array;
}
```
- Trait `Modules\Labeling\Models\Traits\HasIdentifiers` (per modelli Labelable): `identifiers(): MorphMany<Identifier>`, `labelPrints(): MorphMany<LabelPrint>`, `activeIdentifier(IdentifierTypeEnum $type): ?Identifier`.
- Enum `Modules\Labeling\Enums\IdentifierTypeEnum`: `qr`, `barcode128`, `rfid_uhf`, `nfc`.
- Actions:
  - `AssignIdentifierAction::execute(Model&LabelableContract $model, IdentifierTypeEnum $type, ?string $value = null): Identifier` — per `qr`/`barcode128` se `$value` null usa `getLabelCode()`; disattiva l'eventuale identificatore attivo dello stesso tipo; valore univoco tra gli attivi dello stesso tipo.
  - `ResolveIdentifiableAction::execute(string $scanned): ?Model` — normalizza (trim, uppercase per RFID esadecimale, estrae il codice da URL `.../l/{code}`), cerca tra identificatori attivi.
  - `PrintLabelAction::execute(Model&LabelableContract $model, ?LabelTemplate $template = null, ?Printer $printer = null, int $copies = 1): LabelPrint` (queueable).
  - `RenderLabelAction::execute(Model&LabelableContract $model, LabelTemplate $template): string`.
  - `GenerateQrSvgAction::execute(string $payload, int $sizePx = 256): string` (chillerlan/php-qrcode).
- Filament: `Modules\Labeling\Filament\Actions\PrintLabelTableAction` e `PrintLabelHeaderAction` (estendono le basi Xot `XotBaseAction` se adatte, altrimenti `Filament\Actions\Action` configurata in `setUp()`), riusabili da qualunque Resource di modelli Labelable.

### Weighing (`Modules\Weighing`)
- Modello `Weighing` (morph `weighable`), enum `WeighingDirectionEnum` (`inbound`, `outbound`, `internal`), `WeighingStatusEnum` (`open`, `closed`, `voided`).
- Actions:
  - `OpenWeighingAction::execute(WeighingDirectionEnum $direction, float $firstKg, ?Scale $scale = null, ?string $vehiclePlate = null, ?Model $weighable = null): Weighing`
    (inbound: primo peso = lordo; outbound: primo peso = tara).
  - `CloseWeighingAction::execute(Weighing $weighing, float $secondKg): Weighing` (calcola `net_kg = gross_kg - tare_kg`, Assert >= 0).
  - `RecordSingleWeighingAction::execute(float $netKg, ?Scale $scale = null, ?Model $weighable = null): Weighing` (bilancia a pianale: pesata netta diretta, già chiusa).
  - `ReadScaleAction::execute(Scale $scale): ScaleReadingData` (driver da `ScaleDriverEnum`).
  - `AttachWeighingAction::execute(Weighing $weighing, Model $weighable): Weighing`.

### Warehouse (`Modules\Warehouse`)
- Modelli `Warehouse`, `Area`, `Location`, `StockUnit`, `StockMovement`.
- `StockUnit` implementa `LabelableContract` + `HasIdentifiers`; ha morph nullable **`subject`** (`subject_type`, `subject_id`) per il contenuto di dominio (in Roosko: `WasteLot`).
- `Location` implementa `LabelableContract` (si etichettano anche le posizioni, l'operatore scansiona la posizione per stoccare).
- Enum: `ContainerTypeEnum` (`big_bag`, `drum`, `ibc`, `skip`, `roll_off`, `pallet`, `bulk_pile`, `tank`, `box`, `other`), `StockUnitStatusEnum` (`pending`, `stored`, `reserved`, `in_process`, `consumed`, `shipped`, `voided`), `MovementTypeEnum` (`receipt`, `putaway`, `transfer`, `consumption`, `production`, `shipment`, `adjustment`, `void`), `AreaTypeEnum` (`inbound`, `quarantine`, `storage`, `processing`, `outbound`).
- Data `Modules\Warehouse\Datas\StockUnitData`: `warehouseId:int, containerType:ContainerTypeEnum, netWeightKg:float, tareWeightKg:?float=null, locationId:?int=null, subject:?Model=null (Optional), notes:?string=null`.
- Contratto punto di estensione:
```php
namespace Modules\Warehouse\Contracts;
interface PlacementGuardContract {
    /** Ritorna null se consentito, altrimenti il motivo (testo già tradotto) del rifiuto. */
    public function deny(StockUnit $unit, Location $location): ?string;
}
```
  Guard registrati in `config('warehouse.placement_guards')` (array di class-string). Il modulo fornisce `CapacityPlacementGuard` (portata massima di location e area). Roosko aggiunge il suo guard facendo `config()->push('warehouse.placement_guards', ...)` nel proprio ServiceProvider.
- Actions (unico scrittore dei movimenti = `RecordStockMovementAction`):
  - `CreateStockUnitAction::execute(StockUnitData $data, MovementTypeEnum $origin = MovementTypeEnum::RECEIPT, ?Model $reference = null): StockUnit` — genera `code` (`SU-{anno}-{progressivo 6 cifre}` per magazzino, con lock), registra il movimento d'origine (`receipt` o `production`), se `locationId` presente fa anche la verifica dei guard.
  - `CheckPlacementAction::execute(StockUnit $unit, Location $location): ?string` (primo motivo di rifiuto dai guard).
  - `PutAwayStockUnitAction::execute(StockUnit $unit, Location $location, ?Model $reference = null): StockUnit` (guard → movimento `putaway`/`transfer`, status `stored`).
  - `ConsumeStockUnitAction::execute(StockUnit $unit, float $kg, ?Model $reference = null): StockMovement` (consumo parziale o totale; a peso 0 status `consumed`, location null).
  - `ShipStockUnitAction::execute(StockUnit $unit, ?Model $reference = null): StockMovement` (status `shipped`).
  - `AdjustStockUnitWeightAction::execute(StockUnit $unit, float $newNetKg, string $reason): StockMovement`.
  - `VoidStockUnitAction::execute(StockUnit $unit, string $reason): StockMovement`.
  - `RecordStockMovementAction::execute(StockUnit $unit, MovementTypeEnum $type, float $quantityKg, ?Location $from, ?Location $to, ?Model $reference = null, ?string $notes = null): StockMovement`.
  - `GetAreaLoadKgAction::execute(Area $area): float` e `GetLocationLoadKgAction::execute(Location $location): float` (somma `net_weight_kg` delle unità `stored|reserved|pending` presenti).

### Processing (`Modules\Processing`)
- Modelli `Machine`, `ProcessingOrder`, `ProcessingInput`, `ProcessingOutput`, `LineageLink`.
- Enum `ProcessingOrderStatusEnum` (`draft`, `in_progress`, `completed`, `cancelled`), `OutputKindEnum` (`product`, `by_product`, `scrap`, `residue`), `MachineTypeEnum` (`shredder`, `crusher`, `sorter`, `screen`, `magnetic_separator`, `baler`, `press`, `other`).
- Actions:
  - `CreateProcessingOrderAction::execute(Machine $machine, ?string $operationCode = null, ?\DateTimeInterface $plannedAt = null, ?string $notes = null): ProcessingOrder` (code `PO-{anno}-{progr}`).
  - `StartProcessingOrderAction::execute(ProcessingOrder $order): ProcessingOrder`.
  - `AddProcessingInputAction::execute(ProcessingOrder $order, StockUnit $unit, ?float $kg = null): ProcessingInput` (kg null = intera unità; chiama `ConsumeStockUnitAction` con reference all'ordine).
  - `RegisterProcessingOutputAction::execute(ProcessingOrder $order, StockUnitData $unitData, OutputKindEnum $kind = OutputKindEnum::PRODUCT): ProcessingOutput` (crea la StockUnit con `CreateStockUnitAction(..., MovementTypeEnum::PRODUCTION, $order)`).
  - `CompleteProcessingOrderAction::execute(ProcessingOrder $order): ProcessingOrder` — richiede ≥1 input e ≥1 output; calcola il bilancio di massa con `CalculateMassBalanceAction`; scrive i `LineageLink` (per ogni output × input: `share = input_kg / totale_input`, `attributed_kg = output_kg * share`); salva snapshot `input_kg`, `output_kg`, `loss_kg`, `loss_pct`; se `loss_pct` oltre `config('processing.max_loss_pct')` lancia eccezione salvo `$force`.
  - `CancelProcessingOrderAction::execute(ProcessingOrder $order, string $reason): ProcessingOrder` (solo se nessun input consumato).
  - `CalculateMassBalanceAction::execute(array $inputsKg, array $outputsKg): MassBalanceData` — puro (`inputKg`, `outputKg`, `lossKg`, `lossPct`), testato in unit.
  - `TraceUpstreamAction::execute(StockUnit $unit, int $maxDepth = 20): Collection<int, LineageNodeData>` e `TraceDownstreamAction` (BFS su `lineage_links`, con quota di massa cumulata moltiplicando le share lungo il cammino).

### Waste (`Modules\Waste`)
- Enum (valori fissati per legge → enum):
  - `HazardPropertyEnum`: `HP1`…`HP15` (valori `'HP1'`…`'HP15'`).
  - `WasteOperationEnum`: `R1`…`R13`, `D1`…`D15` (valori `'R1'`…), metodi `isRecovery()`, `isDisposal()`, `isPreliminaryStorage()` (R13, D15).
  - `PhysicalStateEnum`: `solid_powdery`, `solid_non_powdery`, `sludge`, `liquid`, `viscous`.
  - `QuantityUnitEnum`: `kg`, `l`, `m3`.
  - `OperatorRoleEnum`: `producer`, `holder`, `transporter`, `intermediary`, `dealer`, `recipient`.
  - `AuthorizationTypeEnum`: `aia`, `aua`, `ordinary_208`, `simplified_214`, `mobile_plant_208`, `other`.
  - `FirStatusEnum`: `draft`, `issued`, `in_transit`, `delivered`, `accepted`, `partially_accepted`, `rejected`, `cancelled`.
  - `AcceptanceEnum`: `full`, `partial`, `rejected`.
  - `RegisterEntryTypeEnum`: `load`, `unload`.
  - `RegisterCauseEnum`: `new_production` (prodotto dall'attività), `from_third_parties` (ricevuto da terzi), `to_third_parties` (consegnato a terzi), `internal_treatment` (avviato a trattamento interno), `rejected_return` (respinto), `adjustment` (rettifica). Il codice RENTRI corrispondente NON va nell'enum: lo mappa il modulo Rentri.
- Modelli: `EwcCode` (EER), `Operator` (soggetto), `OperatorSite` (unità locale, `HasAddress` di Geo), `PlantAuthorization`, `AuthorizedWaste`, `Fir`, `Register`, `RegisterEntry`.
- Data `RegisterEntryData`: `registerId:int, type:RegisterEntryTypeEnum, cause:RegisterCauseEnum, ewcCodeId:int, quantityKg:float, physicalState:PhysicalStateEnum, hazardProperties:list<HazardPropertyEnum>=[], operation:?WasteOperationEnum=null, firId:?int=null, counterpartOperatorId:?int=null, operationAt:CarbonImmutable, reference:?Model=null, loadEntryIds:list<int>=[], notes:?string=null`.
- Actions:
  - `PostRegisterEntryAction::execute(RegisterEntryData $data): RegisterEntry` — numero progressivo per registro e anno con lock (`lockForUpdate`), `recorded_at = now()`, immutabile dopo la trasmissione.
  - `RectifyRegisterEntryAction::execute(RegisterEntry $entry, RegisterEntryData $corrected, string $reason): RegisterEntry` (nuova scrittura con `rectifies_entry_id`).
  - `FindAuthorizedWasteAction::execute(OperatorSite $plant, EwcCode $ewc, WasteOperationEnum $operation): ?AuthorizedWaste` (autorizzazione attiva, non scaduta, codice e operazione presenti).
  - `GetAuthorizedQuantityUsageAction::execute(AuthorizedWaste $authorized, float $currentStockKg): AuthorizationUsageData` (limite istantaneo e annuo: il carico annuo si somma dai `RegisterEntry` di tipo load dell'anno per quel codice sul registro del sito).
  - `CreateFirAction::execute(FirData $data): Fir`, `RecordFirAcceptanceAction::execute(Fir $fir, AcceptanceEnum $acceptance, float $acceptedKg, \DateTimeInterface $arrivedAt, ?string $reason = null): Fir`, `ChangeFirStatusAction::execute(Fir $fir, FirStatusEnum $status): Fir`.
  - `ImportEwcCodesFromCsvAction::execute(string $path): int` (colonne `code;description;is_hazardous;is_mirror`).

### Rentri (`Modules\Rentri`)
- Dipende da Waste. Legge i modelli Waste, non li estende.
- Azioni pubbliche usate da Roosko:
  - `IssueDigitalFirAction::execute(Fir $fir, RentriCredential $credential): RentriTransmission` (crea il FIR digitale su RENTRI).
  - `SignFirAction::execute(Fir $fir, RentriCredential $credential, string $pin): RentriTransmission`.
  - `SubmitFirAcceptanceAction::execute(Fir $fir, RentriCredential $credential): RentriTransmission` (dati accettazione dal Fir).
  - `TransmitRegisterEntriesAction::execute(Register $register, RentriCredential $credential, ?Collection $entries = null): RentriTransmission` (default: tutte le non trasmesse).
  - `PollRentriTransactionAction::execute(RentriTransmission $transmission): RentriTransmission`.
  - `ResolveCredentialAction::execute(OperatorSite $site): RentriCredential` (credenziale attiva per il sito).

### Roosko (`Modules\Roosko`)
- Modelli: `Delivery` (conferimento), `WasteLot` (contenuto di una StockUnit), `Shipment`, `ShipmentItem`, `AreaWasteRule`.
- Implementa `PlacementGuardContract` con `WasteAuthorizationPlacementGuard`.

---

## 3. Modello dati per modulo

Colonne standard omesse: `id`, `created_at`, `updated_at`, `deleted_at` (dove soft delete), `created_by`, `updated_by`, `deleted_by` (da `updateTimestamps`).
Connessione = nome modulo lowercase. FK cross-modulo = indice senza vincolo.

### Labeling
- `label_templates`: `code` string unique, `name`, `format` (`LabelFormatEnum`: `zpl`, `pdf`), `width_mm` int, `height_mm` int, `dpi` int default 203, `content` longText (Blade: per ZPL testo con `{{ $code }}` ecc.; per PDF HTML; a disposizione `$code`, `$data` array, `$qrSvg` per PDF), `labelable_type` string nullable (template di default per classe), `is_default` bool, `is_active` bool.
- `printers`: `code` unique, `name`, `driver` (`PrinterDriverEnum`: `zpl_tcp`, `pdf_download`), `host` nullable, `port` int default 9100, `dpi` int default 203, `is_active`.
- `identifiers`: morph `identifiable` (index), `type` (`IdentifierTypeEnum`), `value` string, `is_active` bool, `assigned_at`, `revoked_at` nullable. Indice `(type, value, is_active)`.
- `label_prints`: morph `labelable`, `label_template_id`, `printer_id` nullable, `copies` int, `rendered` longText (snapshot), `status` (`LabelPrintStatusEnum`: `queued`, `sent`, `rendered`, `failed`), `error` text nullable, `printed_at` nullable.
- Driver: `zpl_tcp` invia il ZPL via socket TCP (host:port, timeout da config); `pdf_download` genera il PDF con `Modules\Xot\Actions\GeneratePdfAction` di Xot (leggerne la firma) o direttamente spipu/html2pdf e lo salva su disco `local` restituendo il path.
- Route web `GET /l/{code}` (nome `labeling.resolve`) → redirect alla pagina di dettaglio Filament del modello risolto (se `Model` ha resource). Il QR codifica `url('/l/'.$code)` così funziona anche con la fotocamera del telefono fuori dall'app.

### Weighing
- `scales`: `code` unique, `name`, `type` (`ScaleTypeEnum`: `weighbridge`, `platform`, `crane`, `forklift`), `driver` (`ScaleDriverEnum`: `manual`, `tcp_ascii`, `fake`), `host` nullable, `port` nullable, `max_capacity_kg`, `resolution_kg`, `verified_until` date nullable (verifica periodica metrologica), `is_active`.
- `weighings`: `ticket_number` string unique (`W-{anno}-{progr}`), morph nullable `weighable`, `scale_id` nullable, `direction`, `status`, `vehicle_plate` nullable, `trailer_plate` nullable, `gross_kg` nullable, `tare_kg` nullable, `net_kg` nullable, `gross_weighed_at`, `tare_weighed_at`, `closed_at` nullable, `raw_readings` json nullable, `notes`.
- Driver `tcp_ascii`: apre socket, legge una riga, regex numero (con segno e decimali, virgola o punto), flag stabile se la riga contiene `ST` (formato comune) — parametri in config; `fake` restituisce un valore da config (per test/demo).
- Data `ScaleReadingData`: `weightKg:float, stable:bool, raw:string, readAt:CarbonImmutable`.

### Warehouse
- `warehouses`: `code` unique, `name`, `description` nullable, `map_width_m` decimal nullable, `map_height_m` decimal nullable, `is_active`. Media collection `map` (planimetria) tramite spatie medialibrary (`HasMedia`, `InteractsWithMedia`).
- `areas`: `warehouse_id`, `code`, `name`, `type`, `max_weight_kg` nullable, `map_geometry` json nullable (poligono `[[x,y],...]` in metri sulla planimetria), `color` nullable, `is_active`. Unique `(warehouse_id, code)`.
- `locations`: `area_id`, `code` (univoco nel magazzino: `{area}-{codice}`), `name` nullable, `x` decimal nullable, `y` decimal nullable, `max_weight_kg` nullable, `is_active`.
- `stock_units`: `warehouse_id`, `code` unique, `container_type`, `status`, `location_id` nullable, `net_weight_kg`, `tare_weight_kg` nullable, morph nullable `subject`, `received_at`, `notes` nullable, `extra_attributes` json nullable (schemaless di Xot se presente nel BaseModel; altrimenti json cast array).
- `stock_movements` (immutabile): `stock_unit_id`, `type`, `quantity_kg`, `weight_before_kg`, `weight_after_kg`, `from_location_id` nullable, `to_location_id` nullable, morph nullable `reference`, `occurred_at`, `notes`.
- Filament: `WarehouseResource` (RM aree), `AreaResource` (RM posizioni), `LocationResource` (azione stampa etichetta), `StockUnitResource` (View con infolist + RM movimenti; header actions: stampa etichetta, sposta, rettifica peso, annulla), `StockMovementResource` (solo lista + filtri, niente create/edit). Pagina `WarehouseMapPage` (XotBasePage): selezione magazzino, SVG con poligoni aree colorati per occupazione (% `GetAreaLoadKgAction`/`max_weight_kg`) e punti posizione; click su area → elenco unità. Widget `AreaOccupancyWidget` (stats).

### Processing
- `machines`: `code` unique, `name`, `type`, `location_ref_id` nullable (id Location di Warehouse, cross-modulo), `capacity_kg_h` nullable, `is_active`.
- `processing_orders`: `code` unique, `machine_id`, `operation_code` string nullable (es. `R12`, valorizzato dal dominio), `status`, `planned_at` nullable, `started_at` nullable, `completed_at` nullable, `cancelled_at` nullable, `cancel_reason` nullable, `input_kg` nullable, `output_kg` nullable, `loss_kg` nullable, `loss_pct` nullable (snapshot alla chiusura), `notes`.
- `processing_inputs`: `processing_order_id`, `stock_unit_id` (cross), `quantity_kg`, `consumed_at`.
- `processing_outputs`: `processing_order_id`, `stock_unit_id` (cross), `kind`, `quantity_kg`, `produced_at`.
- `lineage_links`: `processing_order_id`, `parent_stock_unit_id`, `child_stock_unit_id`, `share` decimal(9,6), `attributed_kg`. Indici su parent e child.
- Config: `max_loss_pct` (default 15.0).
- Filament: `MachineResource`, `ProcessingOrderResource` (RM input e output; header actions avvia/completa/annulla; azione "aggiungi input" con ricerca per codice unità o scansione), pagina `TraceabilityPage` (cerca un codice unità → albero a monte e a valle).

### Waste
- `ewc_codes`: `code` string(8) unique formato `'19 12 04'`, `chapter` string(2), `sub_chapter` string(5), `description` text, `is_hazardous` bool (asterisco), `is_mirror` bool, `is_active`. Seeder con un sottoinsieme sicuro e verificato di codici comuni per impianti di cernita/triturazione (capitoli 15 01, 16 01, 17, 19 12, 20 01, 20 03 01) — **solo codici di cui si è certi**; il catalogo completo si importa con `ImportEwcCodesFromCsvAction`.
- `operators`: `business_name`, `fiscal_code` (index), `vat_number` nullable, `rentri_registration_number` nullable, `albo_registration_number` nullable, `roles` json (lista `OperatorRoleEnum`), `email`, `pec`, `phone` nullable, `is_active`.
- `operator_sites`: `operator_id`, `name`, `rentri_site_number` nullable (num_iscr_sito), `is_plant` bool (impianto gestito da noi), `is_active`. Indirizzo via `HasAddress` (Geo).
- `plant_authorizations`: `operator_site_id`, `type`, `number`, `issuing_authority`, `issued_at` date, `expires_at` date nullable, `is_active`, `notes`. Media collection `document`.
- `authorized_wastes`: `plant_authorization_id`, `ewc_code_id`, `operations` json (lista `WasteOperationEnum`), `max_storage_kg` nullable (istantaneo), `max_annual_kg` nullable, `notes`.
- `firs`: `number` string unique nullable (numero formulario), `is_digital` bool, `status`, `producer_operator_id`, `producer_site_id` nullable, `transporter_operator_id` nullable, `recipient_operator_id`, `recipient_site_id` nullable, `intermediary_operator_id` nullable, `ewc_code_id`, `waste_description` nullable, `physical_state`, `hazard_properties` json, `operation` (WasteOperationEnum), `quantity` decimal, `quantity_unit`, `is_quantity_estimated` bool (peso da verificarsi a destino), `accepted_quantity_kg` nullable, `acceptance` nullable, `rejection_reason` nullable, `vehicle_plate` nullable, `trailer_plate` nullable, `driver_name` nullable, `adr_un_number` nullable, `transport_started_at` nullable, `arrived_at` nullable, `notes`.
- `registers`: `operator_site_id`, `name`, `rentri_identifier` nullable (identificativo registro vidimato), `is_active`.
- `register_entries`: `register_id`, `year` smallint, `number` int (progressivo per registro/anno; unique `(register_id, year, number)`), `type`, `cause`, `recorded_at`, `operation_at`, `ewc_code_id`, `physical_state`, `hazard_properties` json, `quantity_kg`, `operation` nullable, `fir_id` nullable, `counterpart_operator_id` nullable, morph nullable `reference`, `load_entry_ids` json (riferimenti ai carichi per gli scarichi), `rectifies_entry_id` nullable, `notes`, `transmitted_at` nullable, `rentri_transmission_ref` nullable.
- Filament: `EwcCodeResource` (import CSV header action), `OperatorResource` (RM sedi), `OperatorSiteResource`, `PlantAuthorizationResource` (RM codici autorizzati), `FirResource`, `RegisterResource` (RM scritture), `RegisterEntryResource` (lista + view, nessun edit; azione "rettifica"; export).

### Rentri
Fatti verificati dalla documentazione pubblica RENTRI (usarli così):
- Ambienti: DEMO `https://demoapi.rentri.gov.it`, PRODUZIONE `https://api.rentri.gov.it`.
- Servizio formulari: base path `/formulari/v1.0`.
  - `POST /` crea FIR (query opzionale `codice_blocco`), `PUT /{numero_fir}` modifica dati non firmati,
    `POST /{numero_fir}/quantita`, `POST /{numero_fir}/trasporto`, `GET /` lista (query `num_iscr_sito`, header `Paging-Page`, `Paging-PageSize` max 100),
    `POST /{numero_fir}/hash` → `token`, `digest_to_sign` (base64), `hash_algorithm` (`SHA256`),
    `POST /{numero_fir}/acquisizione-firma` (campi `certificato`, `token`, firma, `identificativo_utente`) → `transazione_id`,
    `POST /{numero_fir}/rollback-firma`, `GET /{numero_fir}/azioni` (query `identificativo_soggetto`, `num_iscr_sito`),
    `GET /{numero_fir}`, `GET /{numero_fir}/xfir`, `POST /{numero_fir}/xfir`, `POST /xfir/valida`,
    `POST /{numero_fir}/annulla-fir`, `POST /{numero_fir}/note-annullamento` (campo `note`),
    `POST /{numero_fir}/accettazione` (campi `tipoAccettazione` es. `"A"`, `quantitaAccettata.valore`, `dataOraArrivo`),
    `POST /trasmissioni/operatore/{num_iscr_sito}/{numero_fir}/estrai`, `POST /trasmissioni/operatore/{num_iscr_sito}` (trasmissione dati FIR pericolosi), `GET` lista/dettaglio, `DELETE .../{identificativo}/annulla`,
    `GET /{transazione_id}/status`, `GET /{transazione_id}/result` (pattern asincrono: molte POST restituiscono `transazione_id`).
  - Stati del formulario (`StatoFormulario`): InserimentoQuantita, InserimentoQuantitaTrasportoIniziale, InserimentoTrasportoIniziale, FirmaProduttoreTrasportatoreIniziale, FirmaTrasportatoreIniziale, FirmaProduttore, InserimentoTrasportoSuccessivo, FirmaTrasportatoreSuccessivo, FirmaAnnotazione, FirmaTrasbordoParziale, FirmaTrasbordoTotale, FirmaSostaTecnica, InserimentoAccettazione, FirmaAccettazione, Accettato, RespintoAccettatoParzialmente, FirmaDestinatarioSuccessivo, FirmaAccettazioneSuccessiva, FirmaAnnullamento, Annullato.
- Firma: certificato di firma qualificato/sigillo o firma remota RENTRI (servizio `Sign`: `POST /signatures/signHash`, credenziali `/credentials/list`, `/credentials/authorize` con PIN). Il certificato di interoperabilità `.p12` serve SOLO ad autenticare le chiamate API, NON per firmare i FIR.
- Autenticazione chiamate: certificato di interoperabilità `.p12` rilasciato da RENTRI; pattern AgID ModI (ID_AUTH_REST_02 `Authorization: Bearer <JWT firmato RS256 con header x5c>` + INTEGRITY_REST_02 `Agid-JWT-Signature` con header `Digest: SHA-256=<base64>`). **Claim esatti, `aud` e path dei servizi diversi dai formulari (anagrafiche, codifiche, dati-registri, vidimazione) NON verificati**: metterli in `config/config.php` con commento `// DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)` e valori plausibili: `anagrafiche/v1.0`, `codifiche/v1.0`, `dati-registri/v1.0`, `vidimazione-formulari/v1.0`.
- Mapping codici RENTRI dei nostri enum (stato fisico, causale registro, unità di misura, tipo accettazione `A`=full, `P`=partial, `R`=rejected) in config `rentri.codes.*`, marcati DA VERIFICARE tranne dove sopra indicato.

Modelli:
- `rentri_credentials`: `operator_site_id` (cross Waste), `name`, `environment` (`RentriEnvironmentEnum`: `demo`, `production`), `subject_identifier` (codice fiscale/identificativo soggetto), `certificate_path` (file .p12 su disco privato), `certificate_password` (cast `encrypted`), `remote_signature_credential_id` nullable, `expires_at` nullable, `is_active`. Mai esporre la password nei form in chiaro (campo password, `dehydrated` solo se compilato).
- `rentri_transmissions`: morph nullable `subject` (Fir, Register, RegisterEntry…), `rentri_credential_id`, `service` string, `method`, `path`, `request_payload` json nullable, `response_status` int nullable, `response_body` json nullable, `transaction_id` nullable index, `status` (`TransmissionStatusEnum`: `pending`, `submitted`, `succeeded`, `failed`), `attempts` int, `error` text nullable, `completed_at` nullable.
- `rentri_codes` (cache codifiche): `list` string, `code` string, `description` text, `payload` json; unique `(list, code)`.
Actions interne: `BuildAuthorizationJwtAction`, `BuildIntegrityJwtAction` (firebase/php-jwt o openssl, chiave da `openssl_pkcs12_read`), `SendRentriRequestAction::execute(RentriCredential $c, string $service, string $method, string $path, array $payload = [], array $query = [], ?Model $subject = null): RentriTransmission` (Http facade, timeout/retry da config, log su `rentri_transmissions`), `MapFirToRentriPayloadAction`, `MapRegisterEntryToRentriPayloadAction`, `CheckRentriServiceStatusAction`, `SyncRentriCodesAction`, più quelle pubbliche della sez. 2. Poll pianificato: nel ServiceProvider registrare nello scheduler (`$this->callAfterResolving(Schedule::class, ...)`) una chiamata ogni minuto a `PollPendingRentriTransactionsAction`.
Filament: `RentriCredentialResource`, `RentriTransmissionResource` (sola lettura + azione "riprova" e "verifica esito"), pagina `RentriStatusPage` (stato servizi).
Test: con `Http::fake()` su firma JWT (header presenti, digest corretto), polling, mapping.

### Roosko
- `deliveries`: `code` unique (`CNF-{anno}-{progr}`), `fir_id` (cross Waste), `plant_site_id` (cross Waste OperatorSite), `warehouse_id` (cross), `weighing_id` nullable (cross), `status` (`DeliveryStatusEnum`: `arrived`, `weighed`, `inspecting`, `accepted`, `partially_accepted`, `rejected`, `closed`), `arrived_at`, `accepted_kg` nullable, `rejected_kg` nullable, `rejection_reason` nullable, `inspection_notes` nullable, `analysis_required` bool, `closed_at` nullable.
- `waste_lots` (contenuto di una StockUnit, `morphOne` inverso `stockUnit()` via `subject`): `ewc_code_id` (cross), `physical_state`, `hazard_properties` json, `classification` (`LotClassificationEnum`: `waste`, `end_of_waste`, `by_product`), `destination` (`LotDestinationEnum`: `internal_treatment`, `sale`, `external_recovery`, `disposal`, `remediation`), `producer_operator_id` nullable (origine, per i conferiti), `delivery_id` nullable, `processing_order_id` nullable (cross, per i prodotti), `eow_batch_code` nullable, `eow_declaration_number` nullable, `notes`.
- `shipments`: `code` unique (`USC-{anno}-{progr}`), `warehouse_id`, `recipient_operator_id` (cross), `recipient_site_id` nullable, `fir_id` nullable (cross; assente per vendita EoW), `document_type` (`ShipmentDocumentEnum`: `fir`, `transport_document`, `eow_declaration`), `document_number` nullable, `weighing_id` nullable, `status` (`ShipmentStatusEnum`: `draft`, `loading`, `ready`, `dispatched`, `delivered`, `cancelled`), `dispatched_at` nullable, `notes`.
- `shipment_items`: `shipment_id`, `stock_unit_id` (cross), `quantity_kg`.
- `area_waste_rules`: `area_id` (cross Warehouse), `authorized_waste_id` (cross Waste), `operation` (WasteOperationEnum, R13 o D15), `max_storage_kg` nullable, `notes`.
- `WasteAuthorizationPlacementGuard`: se la StockUnit ha subject `WasteLot`, la Location deve stare in un'Area con una `AreaWasteRule` per il codice EER del lotto; carico area (stesso codice) + unità ≤ `max_storage_kg` della regola e ≤ `authorized_wastes.max_storage_kg` (somma su tutto l'impianto); lotto pericoloso non ammesso in area senza regole per codici pericolosi (segregazione implicita). Restituisce il motivo tradotto.
- Actions di orchestrazione (ognuna in transazione DB dove tocca più tabelle, chiamate alle action degli altri moduli, nessuna logica duplicata):
  - `ReceiveDeliveryAction::execute(Fir $fir, OperatorSite $plant, Warehouse $warehouse, ?float $grossKg = null, ?Scale $scale = null): Delivery` (apre la pesata inbound se c'è il lordo).
  - `WeighDeliveryAction::execute(Delivery $delivery, float $tareKg): Delivery` (chiude la pesata, stato `weighed`).
  - `AcceptDeliveryAction::execute(Delivery $delivery, DeliveryAcceptanceData $data): Delivery` — `DeliveryAcceptanceData`: `acceptedKg`, `rejectedKg=0`, `rejectionReason`, `units: list<AcceptedUnitData{containerType, netWeightKg, tareWeightKg?, locationId?}>`, `operation: WasteOperationEnum` (es. R13). Passi: verifica autorizzazione (`FindAuthorizedWasteAction`), somma unità == acceptedKg (tolleranza 0.001), crea `WasteLot`+`StockUnit` per ogni unità (subject), assegna QR (`AssignIdentifierAction`), accoda stampa etichette (`PrintLabelAction->onQueue()`), scrive carico registro (`PostRegisterEntryAction`, causa `from_third_parties`, fir, controparte = produttore), registra accettazione FIR (`RecordFirAcceptanceAction`), se FIR digitale accoda `SubmitFirAcceptanceAction` di Rentri (risolvendo la credenziale), stato `accepted`/`partially_accepted`.
  - `RejectDeliveryAction::execute(Delivery $delivery, string $reason): Delivery`.
  - `StoreLotAction::execute(StockUnit $unit, Location $location): StockUnit` (wrapper che chiama `PutAwayStockUnitAction`, per la UI operatore).
  - `StartTreatmentAction::execute(Machine $machine, WasteOperationEnum $operation, Collection<int, StockUnit> $units): ProcessingOrder` (crea e avvia ordine, aggiunge input; scarico registro per ogni input con causa `internal_treatment`).
  - `RegisterTreatmentOutputAction::execute(ProcessingOrder $order, TreatmentOutputData $data): StockUnit` — `TreatmentOutputData`: `ewcCodeId`, `physicalState`, `hazardProperties`, `classification`, `destination`, `containerType`, `netWeightKg`, `tareWeightKg?`, `locationId?`, `kind: OutputKindEnum`, `eowBatchCode?`. Crea `WasteLot` + output Processing + QR + stampa etichetta; se classification `waste` scrive carico registro causa `new_production`.
  - `CompleteTreatmentAction::execute(ProcessingOrder $order, bool $force = false): ProcessingOrder` (chiama `CompleteProcessingOrderAction`).
  - `CreateShipmentAction`, `AddShipmentItemAction::execute(Shipment $shipment, StockUnit $unit): ShipmentItem` (verifica destinazione coerente), `DispatchShipmentAction::execute(Shipment $shipment): Shipment` (per ogni unità `ShipStockUnitAction`; per lotti `waste`: crea il FIR in uscita con `CreateFirAction` se manca e accoda `IssueDigitalFirAction`, scrive scarico registro causa `to_third_parties` con `load_entry_ids` dei carichi d'origine; per EoW: richiede `document_number`/dichiarazione di conformità, nessuna scrittura sul registro rifiuti).
  - `GetPlantStockSummaryAction::execute(Warehouse $warehouse): Collection<int, StockSummaryRowData>` (per codice EER: kg in giacenza, pericoloso, limite autorizzato, % uso).
- Pannello e UI:
  - `AdminPanelProvider` del modulo (pannello `roosko::admin`) con dashboard e widget: `PlantStockByEwcWidget` (tabella), `AuthorizationUsageWidget` (stats con soglie), `TodayDeliveriesWidget`.
  - Resource: `DeliveryResource` (header actions: pesa tara, accetta con wizard unità, rifiuta), `WasteLotResource` (view con StockUnit, tracciabilità a monte via `TraceUpstreamAction`), `ShipmentResource` (RM items, azioni carica/spedisci), `AreaWasteRuleResource`.
  - **UI operatore mobile**: pagina `OperatorScanPage` (XotBasePage, layout mobile-first) con: campo scansione sempre a fuoco (funziona con lettori barcode/RFID in modalità *keyboard wedge* dei palmari Zebra/Chainway — nessuna app nativa necessaria) + pulsante fotocamera che usa il componente Alpine `roosko-scanner` (npm `qr-scanner`, bundle vite del modulo, niente CDN). Dopo la scansione mostra la scheda dell'unità (codice, EER, peso, posizione, stato) e i pulsanti contestuali: *Stocca/Sposta* (seconda scansione = posizione), *Avvia trattamento*, *Aggiungi a uscita*, *Ristampa etichetta*, *Rettifica peso*. Se il codice scansionato è una Location mostra le unità presenti. Tutte le azioni chiamano le Actions di dominio.
  - PWA minima: `public/manifest.webmanifest` e service worker che mette in cache gli asset statici (l'outbox offline è un passo successivo, documentato in docs).

---

## 4. Flussi end-to-end (per test feature di Roosko)
1. FIR in ingresso (non pericoloso 19 12 04 / pericoloso 16 01 07*) → `ReceiveDeliveryAction` con lordo → `WeighDeliveryAction` tara → `AcceptDeliveryAction` 2 unità → 2 StockUnit + 2 WasteLot + 1 carico registro + QR assegnati.
2. `StoreLotAction` su posizione di area con regola → ok; su area senza regola → rifiuto con motivo.
3. `StartTreatmentAction` con le 2 unità (R12/R3) → scarichi registro, unità consumate → `RegisterTreatmentOutputAction` 1 prodotto EoW + 1 scarto `19 12 12` → `CompleteTreatmentAction` → lineage con share 0.5/0.5 se input uguali; `TraceUpstreamAction` dall'output risale ai 2 lotti e al FIR.
4. `CreateShipmentAction` per lo scarto verso destinatario terzo → `DispatchShipmentAction` → FIR in uscita creato, scarico registro, unità shipped.
