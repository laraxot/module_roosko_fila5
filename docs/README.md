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

Area clienti: `GetCustomerOperatorIdsAction` (soggetti del cliente, base di ogni scope),
`ResolveCustomerSiteOperatorAction` (soggetto del cantiere creato dal cliente),
`GetCustomerSiteDeliveredKgAction` (kg accettati per cantiere, anche da una data).

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

### Area clienti (`roosko::customer`, `/roosko/customer/{cliente}`)

Pannello separato (`CustomerPanelProvider`, titolo «Roosko — Area clienti») per le imprese che
conferiscono all'impianto. Tenancy Filament: il cliente è il `Tenant` del modulo User
(`XotData::getTenantClass()`), identificato nell'URL dallo `slug`. Scopre solo
`app/Filament/Customer/{Resources,Pages,Widgets}`: nessuna resource amministrativa.
Story: [area-cliente-cantieri](stories/01.area-cliente-cantieri.story.md).

| Voce di menu (gruppo «I miei rifiuti») | Resource | Modello | Operazioni |
|---|---|---|---|
| Riepilogo (home) | `CustomerDashboard` | — | widget «Conferito nel mese per cantiere», «Ultimi conferimenti» (10) |
| Cantieri (`/sites`) | `CustomerSiteResource` | `Waste\OperatorSite` (`is_plant = false`) | elenco, crea, modifica, dettaglio; colonne nome, n. iscrizione RENTRI, attivo, kg conferiti |
| Conferimenti (`/deliveries`) | `CustomerDeliveryResource` | `Delivery` | elenco e dettaglio: codice, arrivo, FIR, cantiere, EER, kg accettati, stato |
| Formulari (`/firs`) | `CustomerFirResource` | `Waste\Fir` | elenco e dettaglio: numero, data, cantiere, EER, quantità, stato |

**Cosa vede il cliente.** Il legame record → cliente è indiretto e passa sempre dai suoi soggetti
Waste (`operators.tenant_id`, unica fonte): `GetCustomerOperatorIdsAction::execute(?Model $tenant): list<int>`.
Cantieri = unità locali non d'impianto di quei soggetti; FIR = `producer_operator_id` fra quei
soggetti; conferimenti = quelli con un FIR del cliente. Le resource estendono `CustomerBaseResource`:
`$isScopedToTenant = false` (niente scope automatico di Filament), scope in `getEloquentQuery()`, che
usano anche route binding, badge e widget. Senza cliente corrente le query non restituiscono nulla.

**Operazioni.** Le policy dei modelli restano del personale dell'impianto; nell'area clienti
`CustomerBaseResource::getAuthorizationResponse()` ammette solo le abilità della resource
(`viewAny`/`view`, più `create`/`update` per i cantieri) e solo su record del cliente. In creazione
del cantiere `operator_id` e `is_plant = false` li fissa il server (`ResolveCustomerSiteOperatorAction`):
con un solo soggetto si usa quello, con più soggetti il cliente sceglie fra i suoi.
I kg per cantiere li calcola `GetCustomerSiteDeliveredKgAction`: solo FIR con il cantiere come luogo di
produzione **e** il suo soggetto titolare come produttore; con i moduli sullo stesso database un'unica
query aggregata (join), altrimenti query per connessione. La tabella cantieri la chiama una volta per
richiesta. Gli id dei soggetti del cliente (`GetCustomerOperatorIdsAction`) sono memoizzati per richiesta.

Il **numero di iscrizione RENTRI** del cantiere è modificabile dal cliente solo finché nessun FIR indica
il cantiere (`IsSiteRegistrationLockedAction`): poi il campo è in sola lettura e `EditCustomerSite` lo
scarta anche se arriva nei dati; la variazione si chiede all'impianto.

**Come dare accesso a un cliente.**

1. Modulo User: creare il cliente (`Tenant`, nome e slug: lo slug è il pezzo di URL).
2. Modulo User: creare l'utente (o usarne uno esistente) e aggiungerlo come membro del cliente
   (`tenant_user`). Un utente può essere membro di più clienti e passa dall'uno all'altro dal menu.
3. Assegnare all'utente il ruolo `roosko::customer` (nome = id del pannello, richiesto da
   `BaseUser::canAccessPanel`; il ruolo lo crea `CustomerRoleSeeder`).
4. Pannello Waste, scheda del soggetto (impresa produttrice): campo *Cliente* = il cliente. Un
   cliente può avere più soggetti (gruppo di imprese).

L'utente entra da `/roosko/customer/login` e arriva su `/roosko/customer/{slug}`. Senza ruolo o senza
clienti non entra; un cliente di cui non è membro risponde 404.

### UI operatore (`OperatorScanPage`, `/roosko/admin/operator`)

- Campo di scansione sempre a fuoco: i lettori in **keyboard wedge** digitano il codice e Invio.
- Bottone fotocamera: componente Alpine `rooskoScanner` (`resources/js/scanner.js`, npm `qr-scanner`,
  bundle vite in `public/assets` del modulo, servito da `public_html/assets/roosko` tramite symlink, caricato solo su questa pagina).
- Scansione di un'unità → scheda (codice, EER, peso, posizione, stato) e azioni: Stocca/Sposta
  (seconda scansione = posizione), Avvia trattamento, Aggiungi a uscita, Ristampa etichetta,
  Rettifica peso. Scansione di una posizione → unità presenti.
- Codici riconosciuti (`ResolveScannedCodeAction`): identificatori di Labeling (QR, barcode, RFID, NFC,
  URL `/l/{code}`), poi il codice dell'etichetta ripulito (caratteri dei lettori wedge, URL del QR):
  unità `SU-…`, posizione `LOC-{magazzino}-{codice}` (anche senza identificatore assegnato), codice
  interno della posizione digitato a mano.
- Tastiera: il campo ha `inputmode="none"` (niente tastiera virtuale coi lettori wedge); il bottone
  tastiera passa a `inputmode="text"` per digitare il codice dal telefono (scelta ricordata sul dispositivo).
- Un'unità caricata su un'uscita è `reserved`: Stocca/Sposta e Avvia trattamento non sono disponibili
  finché non viene tolta dall'uscita (o l'uscita annullata).
- PWA minima: `public/manifest.webmanifest` + icona (serviti dal symlink
  `public_html/assets/roosko` → `Modules/Roosko/public`, creato da `php artisan xot:module-link`), service worker servito dalla route
  `roosko.service-worker` (`/roosko/sw.js`, scope `/roosko/`) che mette in cache solo asset statici.

Build: `cd Modules/Roosko && npm ci && npm run build`. L'output (`public/manifest.json`, `public/assets/`)
è versionato: il server di produzione non compila e non ha bisogno di Node. Dopo una modifica a
`resources/js/` si ricompila e si committa anche il bundle.

## Integrità e concorrenza

- **Transazione d'impianto** (`RunInPlantTransactionAction`): le connessioni dei moduli si deduplicano
  per istanza. Con l'alias del modulo Tenant (stessa istanza `Connection` per i moduli sullo stesso
  database) è una sola transazione vera; con database separati l'atomicità è best-effort. La
  transazione più esterna si ritenta fino a 3 volte su deadlock/serializzazione.
- **Decisioni sul conferimento** (accetta, respingi): conferimento e FIR si rileggono con
  `lockForUpdate` dentro la transazione (`LockDeliveryForDecisionAction`) e si ricontrolla lo stato.
- **Un FIR = un conferimento vivo**: lock del FIR in ricezione + colonna generata
  `deliveries.open_fir_id` (fir_id se non respinto né eliminato) con indice unique.
- **FIR in bozza**: in ricezione il FIR avanza `draft → issued → in_transit → delivered` con
  `ChangeFirStatusAction` (solo transizioni consentite); un FIR in stato finale non si conferisce.
- **Uscite**: caricare un'unità la porta a `reserved` (lock dell'uscita e dell'unità); togliere la riga
  (`RemoveShipmentItemAction`) o annullare l'uscita (`CancelShipmentAction`) la riporta a `stored`. La
  spedizione rilegge righe e stato sotto lock e usa i kg del movimento di uscita (peso attuale) per riga,
  FIR e scarico di registro.
- **Registro**: i carichi d'origine di un lotto sono solo le scritture effettive (dopo una rettifica,
  la rettifica).
- **Query limitate**: niente `whereIn` con liste di id che crescono con la storia
  (`WhereInModuleQueryAction`: subquery quando i moduli condividono la connessione); tabelle e widget
  con eager loading e aggregati (numero di query costante, test con Livewire).

## Ruoli operativi e permessi

`RooskoPermissionSeeder` (richiamato da `RooskoDatabaseSeeder`, idempotente) crea i permessi delle
policy di Roosko e tre ruoli operativi con i permessi dei moduli di dominio (nomi `{modello}.{abilità}`
letti dalle policy dei moduli). I ruoli danno le **abilità**; l'accesso a un pannello lo dà il ruolo
col nome del pannello (`roosko::admin`, `waste::admin`, …, vedi `BaseUser::canAccessPanel`): a un
utente si assegnano quindi il ruolo operativo **e** i ruoli dei pannelli che usa (colonna *Pannelli*).
Tutte le policy di Roosko usano `DeniesUndeclaredFilamentAbilities` (eliminazione massiva, riordino,
replica, collegamenti negati se non dichiarati).

| Ruolo | Chi | Pannelli |
| --- | --- | --- |
| `roosko.operator` | operatore di piazzale | `roosko::admin` |
| `roosko.acceptance` | ufficio accettazione e pesatura | `roosko::admin`, `waste::admin`, `weighing::admin` |
| `roosko.technical_manager` | responsabile tecnico | tutti i pannelli dei moduli di dominio |

| Modello | Operatore | Accettazione | Responsabile tecnico |
| --- | --- | --- | --- |
| `area` | viewAny, view | — | viewAny, view, update |
| `area_waste_rule` | — | — | viewAny, view, create, update, delete |
| `authorized_waste` | — | — | viewAny, view, create, update, delete |
| `delivery` | viewAny, view | viewAny, view, create, update, weigh, accept, reject | viewAny, view, create, update, weigh, accept, reject, delete |
| `ewc_code` | — | viewAny, view | viewAny, view |
| `fir` | — | viewAny, view, create, update | viewAny, view, create, update |
| `identifier` | viewAny, view | — | viewAny, view |
| `label_print` | viewAny, view, create | viewAny, view, create | viewAny, view, create |
| `lineage_link` | — | — | viewAny, view |
| `location` | viewAny, view | viewAny, view | viewAny, view, update |
| `machine` | viewAny, view | — | viewAny, view |
| `operator` | — | viewAny, view, create, update | viewAny, view, create, update |
| `operator_site` | — | viewAny, view, create, update | viewAny, view, create, update |
| `plant_authorization` | — | — | viewAny, view, create, update, delete |
| `processing_input` | — | — | viewAny, view |
| `processing_order` | viewAny, view, registerOutput, complete | — | viewAny, view, registerOutput, complete, create, update, start, addInput, forceComplete, cancel |
| `processing_output` | — | — | viewAny, view |
| `register` | — | viewAny, view | viewAny, view |
| `register_entry` | — | viewAny, view | viewAny, view, rectify |
| `rentri_credential` | — | — | viewAny, view |
| `rentri_transmission` | — | viewAny, view | viewAny, view, retry, sign |
| `scale` | — | viewAny, view, readScale | viewAny, view, readScale |
| `shipment` | viewAny, view, load | viewAny, view, create, update, load, dispatch, cancel | viewAny, view, load, create, update, dispatch, cancel, delete |
| `shipment_item` | viewAny, view | viewAny, view, delete | viewAny, view, delete |
| `stock_movement` | — | — | viewAny, view |
| `stock_unit` | viewAny, view | viewAny, view | viewAny, view, update |
| `warehouse` | viewAny, view | viewAny, view | viewAny, view |
| `waste_lot` | viewAny, view, store, treat, reprintLabel | viewAny, view, update, reprintLabel, adjustWeight | viewAny, view, store, treat, reprintLabel, update, adjustWeight |
| `weighing` | — | viewAny, view, create, update, void | viewAny, view, create, update, void |

Azioni e abilità controllate (bottone nascosto e chiamata diretta rifiutata):

| Azione | Abilità |
| --- | --- |
| Registra arrivo | `delivery.create` |
| Pesa tara | `delivery.weigh` |
| Accetta / Respingi | `delivery.accept` / `delivery.reject` |
| Nuova uscita | `shipment.create` |
| Carica unità (uscita, pagina Operatore) | `shipment.load` |
| Spedisci / Annulla uscita | `shipment.dispatch` / `shipment.cancel` |
| Togli unità dall'uscita | `shipment_item.delete` |
| Pagina Operatore | `waste_lot.viewAny` |
| Stocca/Sposta, Avvia trattamento | `waste_lot.store`, `waste_lot.treat` |
| Ristampa etichetta, Rettifica peso | `waste_lot.reprintLabel`, `waste_lot.adjustWeight` |
| Lavorazioni in corso | `processing_order.viewAny` |
| Registra prodotto / Completa | `processing_order.registerOutput` / `processing_order.complete` |
| Forza la chiusura | `processing_order.forceComplete` |

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

- `queues.labels`, `queues.rentri`: code per stampa e RENTRI. `null` = coda dell'action: per RENTRI è
  la coda dedicata `rentri` (`rentri.queue.name`, vedi README di Rentri); per le etichette la coda di
  default del connettore.
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
- Ricalcolo dei limiti annui (`max_annual_kg`) nei widget (oggi solo il limite istantaneo).

## Audit

Manuale d'uso per ruolo: [manuale/README.md](manuale/README.md).

Stato del progetto, rilievi con evidenze, backlog P0–P3 e roadmap: [audit/README.md](audit/README.md).
