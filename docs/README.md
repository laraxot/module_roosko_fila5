# Roosko

Modulo di prodotto per un impianto di trattamento/cernita rifiuti. Orchestra sei moduli generici o di
dominio e non duplica la loro logica: conferimenti con FIR e pesata, accettazione in unità etichettate
con QR, stoccaggio nelle aree autorizzate, trattamento con nuove unità, uscite (vendita End of Waste,
recupero, smaltimento, bonifica), registro di carico/scarico, invii RENTRI e UI mobile dell'operatore.

## Architettura dei 7 moduli

```
                         ┌───────────────┐
                         │      Xot      │  (basi Laraxot: modelli, Filament, migrazioni)
                         └───────┬───────┘
         ┌───────────────┬───────┴────────┬────────────────┐
   ┌─────▼─────┐   ┌─────▼─────┐    ┌─────▼─────┐     ┌─────▼─────┐
   │ Labeling  │   │ Weighing  │    │   Waste   │◄────│    Geo    │
   │ QR/RFID,  │   │ bilance,  │    │ EER, FIR, │     │ indirizzi │
   │ etichette │   │ pesate    │    │ registro  │     └───────────┘
   └─────▲─────┘   └─────▲─────┘    └─────▲─────┘
   ┌─────┴─────┐         │          ┌─────┴─────┐
   │ Warehouse │         │          │  Rentri   │  client API RENTRI (FIR digitale, registri)
   │ unità,    │         │          └─────▲─────┘
   │ posizioni │         │                │
   └─────▲─────┘         │                │
   ┌─────┴─────┐         │                │
   │Processing │         │                │
   │ ordini,   │         │                │
   │ lineage   │         │                │
   └─────▲─────┘         │                │
         └───────────────┴──────┬─────────┘
                         ┌──────┴──────┐
                         │   Roosko    │  conferimenti, lotti, uscite, regole d'area, UI operatore
                         └─────────────┘
```

- I moduli generici (Labeling, Weighing, Warehouse, Processing) non sanno nulla di rifiuti.
- Il contenuto di dominio di una `StockUnit` è un `WasteLot` di Roosko (morph `subject`).
- Ogni modulo ha la sua connessione: le FK cross-modulo sono colonne indicizzate senza vincolo e le
  relazioni Eloquent interrogano ciascun modello sulla sua connessione (niente join tra moduli).
- Le actions di orchestrazione aprono una transazione per ogni connessione distinta coinvolta
  (`RunInPlantTransactionAction`): con un solo database è atomico, con database separati è
  best-effort (niente 2PC). Stampa etichette e chiamate RENTRI si accodano **dopo** il commit.

## Modelli

| Modello | Tabella | Note |
|---|---|---|
| `Delivery` | `deliveries` | conferimento `CNF-{anno}-{progr}`; stato `DeliveryStatusEnum` |
| `WasteLot` | `waste_lots` | contenuto di una StockUnit: EER, stato fisico, HP, classificazione, destino, origine |
| `Shipment` | `shipments` | uscita `USC-{anno}-{progr}`; documento `ShipmentDocumentEnum`; `operation` R/D a destino |
| `ShipmentItem` | `shipment_items` | unità caricata, snapshot dei kg |
| `AreaWasteRule` | `area_waste_rules` | codice autorizzato (AuthorizedWaste) ammesso in un'area (R13/D15), limite d'area |
| `Profile` | `profiles` | profilo utente del progetto: Xot lo usa quando `xra.main_module` è Roosko |

Enum: `DeliveryStatusEnum`, `ShipmentStatusEnum` (con `canTransitionTo()`), `LotClassificationEnum`,
`LotDestinationEnum`, `ShipmentDocumentEnum`. Etichette/colori/icone in `lang/{it,en}/*_enum.php`.

## Actions

Orchestrazione (firme della SPEC):

- `ReceiveDeliveryAction::execute(Fir $fir, OperatorSite $plant, Warehouse $warehouse, ?float $grossKg = null, ?Scale $scale = null): Delivery`
- `WeighDeliveryAction::execute(Delivery $delivery, float $tareKg): Delivery`
- `AcceptDeliveryAction::execute(Delivery $delivery, DeliveryAcceptanceData $data): Delivery`
- `RejectDeliveryAction::execute(Delivery $delivery, string $reason): Delivery`
- `StoreLotAction::execute(StockUnit $unit, Location $location): StockUnit`
- `StartTreatmentAction::execute(Machine $machine, WasteOperationEnum $operation, Collection $units): ProcessingOrder`
- `RegisterTreatmentOutputAction::execute(ProcessingOrder $order, TreatmentOutputData $data): StockUnit`
- `CompleteTreatmentAction::execute(ProcessingOrder $order, bool $force = false): ProcessingOrder`
- `CreateShipmentAction::execute(Warehouse $warehouse, Operator $recipient, ShipmentDocumentEnum $documentType, ?WasteOperationEnum $operation = null, ?OperatorSite $recipientSite = null, ?string $documentNumber = null, ?string $notes = null): Shipment`
- `AddShipmentItemAction::execute(Shipment $shipment, StockUnit $unit): ShipmentItem`
- `DispatchShipmentAction::execute(Shipment $shipment): Shipment`
- `GetPlantStockSummaryAction::execute(Warehouse $warehouse): Collection<int, StockSummaryRowData>`

Supporto: `RunInPlantTransactionAction`, `GenerateDocumentCodeAction` (progressivo annuale con
`lockForUpdate`), `ResolveWasteLotAction`, `ResolvePlantSiteAction` (magazzino → sito tramite le regole
d'area), `ResolvePlantRegisterAction`, `FindRentriCredentialAction`, `SubmitDigitalFirAcceptanceAction`,
`QueueUnitLabelAction`, `GetLotLoadEntryIdsAction`, `GetLotStockKgAction`, `TraceLotOriginAction`,
`ResolveScannedCodeAction`, `ResolveOrderWarehouseAction`, `GetLocationOptionsAction`, `CastHazardPropertiesAction`, `GetQueueNameAction`.

## Flussi

1. **Conferimento** — `ReceiveDeliveryAction` (pesata inbound aperta col lordo, FIR → `delivered`) →
   `WeighDeliveryAction` (tara, netto calcolato da Weighing) → `AcceptDeliveryAction`: verifica
   autorizzazione, somma unità = kg accettati (±1 g), per ogni unità `WasteLot` + `StockUnit`
   (movimento `receipt`) + QR, un carico di registro `from_third_parties`, accettazione sul FIR;
   dopo il commit stampa etichette e, per FIR digitale con credenziale del sito, invio a RENTRI.
2. **Stoccaggio** — `StoreLotAction` → `CheckPlacementAction` (guard di Warehouse, tra cui
   `WasteAuthorizationPlacementGuard`) → `PutAwayStockUnitAction`.
3. **Trattamento** — `StartTreatmentAction` (ordine creato e avviato, input consumati, scarichi
   `internal_treatment` con i carichi d'origine) → `RegisterTreatmentOutputAction` per ogni prodotto
   (lotto + output + QR + etichetta; carico `new_production` solo se ancora rifiuto) →
   `CompleteTreatmentAction` (bilancio di massa e lineage di Processing).
4. **Uscita** — `CreateShipmentAction` → `AddShipmentItemAction` (coerenza documento/classificazione/
   destino, un EER per FIR) → `DispatchShipmentAction` (unità `shipped`; per rifiuti FIR in uscita e
   scarichi `to_third_parties`, emissione su RENTRI accodata; per EoW numero della dichiarazione
   obbligatorio, nessuna scrittura di registro).

## Punto di estensione: `WasteAuthorizationPlacementGuard`

Registrato da `RooskoServiceProvider` con `config()->push('warehouse.placement_guards', ...)`.
Per una StockUnit con lotto Roosko nega il posizionamento se: l'area non ha regole; il lotto è
pericoloso e l'area non ha regole per codici pericolosi; il codice EER non è previsto nell'area;
carico d'area + unità > `max_storage_kg` della regola; carico d'impianto (tutte le aree mappate sullo
stesso codice autorizzato) + unità > `authorized_wastes.max_storage_kg`. Il motivo è tradotto
(`lang/*/placement_guard.php`). Lo spostamento di un'unità non la conta due volte.

## Pannello e UI

Pannello `roosko::admin` (`/roosko/admin`): dashboard con `AuthorizationUsageWidget` (soglie in
`roosko.authorization_usage_thresholds`), `PlantStockByEwcWidget`, `TodayDeliveriesWidget`;
resource `DeliveryResource` (registra arrivo, pesa tara, accetta con repeater delle unità, respingi),
`WasteLotResource` (view con unità, posizione, tracciabilità a monte), `ShipmentResource` (righe con
carico da scansione, spedisci), `AreaWasteRuleResource`.

### Lavorazioni in corso (`TreatmentPage`, `/roosko/admin/treatments`)

Gruppo «Impianto». Tabella degli ordini di Processing `in_progress` (avviati con «Avvia trattamento»
dalla UI operatore): ordine, macchina, operazione, kg in input, kg di output già registrati, avvio.
Per ogni riga:

- **Registra prodotto** (`RegisterTreatmentProductTableAction` → `RegisterTreatmentOutputAction`):
  codice EER, stato fisico, classi HP (solo per EER pericoloso), classificazione, destino, codice
  lotto EoW (solo per End of Waste), tipo output (default prodotto), contenitore, peso netto, tara,
  posizione facoltativa (posizioni del magazzino degli input, `ResolveOrderWarehouseAction`). Crea
  lotto, unità con QR ed etichetta e, se il prodotto è ancora un rifiuto, il carico `new_production`.
  Lo stato del form diventa `TreatmentOutputData::fromFormState()`. Da preferire al bottone
  «Registra output» di Processing, che crea l'unità senza lotto (niente EER né registro).
- **Completa trattamento** (`CompleteTreatmentTableAction` → `CompleteTreatmentAction`): conferma
  con «Forza la chiusura»; se la perdita supera `processing.max_loss_pct` il modale resta aperto con
  l'errore di dominio.

Testi in `lang/{it,en}/treatment.php` (pagina, colonne, bottoni), `register_treatment_product_table.php`
e `complete_treatment_table.php` (modali e campi).

### UI operatore (`OperatorScanPage`, `/roosko/admin/operator`)

- Campo di scansione sempre a fuoco: i lettori in **keyboard wedge** digitano il codice e Invio.
- Bottone fotocamera: componente Alpine `rooskoScanner` (`resources/js/scanner.js`, npm `qr-scanner`,
  bundle vite del modulo in `public_html/assets/roosko`, caricato solo su questa pagina).
- Scansione di un'unità → scheda (codice, EER, peso, posizione, stato) e azioni: Stocca/Sposta
  (seconda scansione = posizione), Avvia trattamento, Aggiungi a uscita, Ristampa etichetta,
  Rettifica peso. Scansione di una posizione → unità presenti.
- PWA minima: `public/manifest.webmanifest` + icona (pubblicati con
  `php artisan vendor:publish --tag=roosko-assets`), service worker servito dalla route
  `roosko.service-worker` (`/roosko/sw.js`, scope `/roosko/`) che mette in cache solo asset statici.

Build: `cd Modules/Roosko && npm install && npm run build`.

## Hardware supportato

- **Lettori barcode/RFID** (palmari Zebra, Chainway, Honeywell…) in modalità *keyboard wedge* con
  suffisso Invio: nessuna app nativa. Gli identificatori RFID UHF (EPC esadecimale) si normalizzano in
  Labeling (`ResolveIdentifiableAction`).
- **Fotocamera** del telefono/tablet (HTTPS obbligatorio per `getUserMedia`).
- **Stampanti ZPL di rete** (Zebra, porta 9100) tramite il driver `zpl_tcp` di Labeling; in alternativa
  PDF scaricabile.
- **Bilance**: pesa a ponte o a pianale con protocollo ASCII su TCP (driver `tcp_ascii` di Weighing),
  manuale o `fake` per demo/test.

## Configurazione (`config/config.php`)

- `queues.labels`, `queues.rentri`: code per stampa e RENTRI (null = default).
- `authorization_usage_thresholds.warning|danger`: soglie % dei widget.

## Dipendenze

Xot, User, Geo, Labeling, Weighing, Warehouse, Processing, Waste, Rentri (nessun pacchetto composer
aggiuntivo). npm: `qr-scanner`, `vite`, `laravel-vite-plugin`.

## Cosa resta da fare

- **Outbox offline** nella UI operatore: accodare le scansioni in IndexedDB quando la rete manca e
  inviarle (in ordine, idempotenti con un id client) al ritorno della connessione; oggi il service
  worker mette in cache solo gli asset statici.
- **Verifica RENTRI sullo Swagger demo** (`https://demoapi.rentri.gov.it/docs`): payload e codici
  mappati da Rentri (accettazione, emissione FIR in uscita, trasmissione registro) da provare end-to-end
  con una credenziale demo; la trasmissione periodica del registro non è ancora orchestrata da Roosko.
- Etichette delle posizioni: il codice stampato da Labeling per una `Location` è `LOC-{magazzino}-{codice}`;
  la UI operatore la risolve tramite l'identificatore assegnato (consigliato assegnarlo a tutte le posizioni).
- Ricalcolo dei limiti annui (`max_annual_kg`) nei widget (oggi solo il limite istantaneo).
