---
title: Roosko — rapporto finale del ciclo audit → implementazione → verifica
type: audit
module: Roosko
created: 2026-11-10
updated: 2026-11-11
scope: [base_roosko_fila5@dev, Roosko, Waste, Warehouse, Processing, Weighing, Labeling, Rentri, Tenant, User, Xot, Geo, Lang, TwentyOne]
qmd: "rapporto finale roosko audit implementazione test phpstan rischi residui decisioni aperte"
---

# Roosko — rapporto finale

Questo rapporto chiude il ciclo iniziato con l'[audit del 5 novembre](README.md): rilievi →
correzioni → test → review → nuove correzioni. Il [backlog](backlog.md) e la [roadmap](roadmap.md)
restano la pianificazione. Qui c'e' lo stato verificato al 10 novembre 2026, aggiornato l'11 novembre
dopo un secondo giro sui flussi nel browser e sulla messa in produzione (sezione 5, «11 novembre»).

Differenza principale rispetto all'audit: allora **nessun test era stato eseguito** (niente PHP 8.4,
niente `vendor/`). Ora ogni numero qui sotto viene da un'esecuzione reale. L'ambiente e' PHP 8.4.27,
Laravel 13.40, l'app base con i moduli di `dev` e sqlite come DB di test.

## 1. Sintesi

- **Tutte le suite dei 7 moduli di dominio sono verdi**: 561 test, 0 falliti.
- **PHPStan livello 10 a zero** sulle app dei 7 moduli di dominio e sui file toccati di Tenant.
- **Corretti i rilievi critici e alti dell'audit di sicurezza**:
  - esecuzione di codice dai template delle etichette;
  - escalation a super-admin;
  - abilita' Filament non dichiarate concesse per default;
  - azioni senza autorizzazione;
  - segreti committati.
- **L'area cliente e' operativa e testata**: pannello `roosko::customer` con isolamento fra clienti verificato da test.
- **Il repository base e' riproducibile**:
  - Xot e' un albero normale e non piu' un gitlink;
  - i moduli di dominio sono allineati ai loro repo;
  - via i residui di FixCity;
  - via la copia annidata dell'intera app dentro `Modules/Weighing`, 30.878 file.
- **La CI esegue davvero i test**: nuovo `tests.yml` con PHP 8.4, un job Pest per modulo e PHPStan.
- **Rilascio preparato** (11 novembre):
  - `deploy.sh` con backup, link degli asset, migrazioni, cache, riavvio delle code;
  - il comando `roosko:production-check`;
  - configurazione di supervisor e cron;
  - runbook di produzione;
  - asset compilati versionati: in produzione non serve Node;
  - CI con controllo dei bundle e numerazione del registro su MariaDB.
- **Restano fuori dal codice**, perché servono accessi o decisioni del committente:
  - prova RENTRI sull'ambiente demo (R3);
  - rotazione dei segreti esposti (R1);
  - prova di ripristino del backup sul server;
  - le decisioni della sezione 7.

## 2. Tabella sintetica

| Area | Stato | Confidenza | Problemi aperti | Priorita' |
|---|---|---|---|---|
| Flussi d'impianto (conferimento → unita' → stoccaggio → lavorazione → uscita → FIR emesso) | implementati, testati e percorsi nel browser | alta | — | — |
| Area cliente / cantieri | implementata, isolamento testato | alta | il cliente modifica `rentri_site_number` (decisione, sez. 7) | P2 |
| Sicurezza applicativa | rilievi critici/alti corretti | alta | rotazione dei segreti esposti | P0 |
| Registro e numerazione | immutabile, concorrenza testata con processi reali su sqlite e MariaDB | alta | — | — |
| RENTRI | client completo, idempotenza anche sul registro, redazione | media | 35 valori `DA VERIFICARE`; nessuna prova demo | P1 |
| Tenancy / connessioni | alias delle connessioni per transazioni atomiche | alta | 17 test di Tenant falliti, preesistenti, non di Roosko | P2 |
| Repo base / CI | riproducibile; Pest, PHPStan, bundle e MariaDB in CI; workflow rotti del template rimossi | alta | `composer.lock` non versionato (decisione) | P2 |
| Moduli condivisi nel base | allineati ai repo (Xot, User, Tenant, Lang, Geo, tema) | alta | suite condivise con fallimenti preesistenti | P2 |
| Documentazione | story BMAD per ogni intervento, manuale per ruolo con schermate reali, PDF, runbook | alta | — | — |
| Produzione | deploy, controlli, worker, cron, backup scritti | media | prova di restore sul server; RENTRI demo | P1 |

## 3. Punteggi (0–100)

| Dimensione | 5 nov | 10 nov | 11 nov | Perche' e' cambiato |
|---|---|---|---|---|
| Architettura di dominio | 80 | 85 | 88 | lock e vincoli unici sui punti di concorrenza, lineage a diamante, `effective()` per le rettifiche; policy con permessi operativi (il ruolo del pannello non da' piu' tutto) |
| Completezza funzionale (impianto) | 65 | 75 | 88 | prenotazioni sulle uscite, scansione LOC, lavorazioni in corso, firma FIR; 11/11: flusso completo fino al FIR emesso, operazioni filtrate per autorizzazione, default dove la scelta e' unica |
| Completezza funzionale (area cliente) | 65 | 80 | 85 | test di isolamento eseguiti e verdi |
| Sicurezza | 60 | 80 | 85 | C1, A1–A4, M1, M2, M4, M5, B1, B2, B5 e B7 corretti; restano rotazione dei segreti e M6 |
| Testing | 50 | 80 | 85 | 548 test eseguiti e verdi; concorrenza reale con `pcntl_fork` |
| Production readiness | 25 | 45 | 75 | 11/11: deploy, controlli di produzione, worker, cron, backup, runbook, asset versionati; mancano RENTRI demo, rotazione dei segreti e prova di restore |
| Documentazione | 55 | 75 | 90 | story per ogni intervento, manuale con screenshot per ruolo |

## 4. Risultati verificati

### Test Pest

Eseguiti nella harness con PHP 8.4.27 e i moduli a `dev` dell'11/11; gli stessi job girano in CI.

| Modulo | Esito | Asserzioni |
|---|---|---|
| Labeling | 48 passed | 214 |
| Weighing | 50 passed | 186 |
| Warehouse | 77 passed | 192 |
| Processing | 56 passed | 258 |
| Waste | 147 passed | 329 |
| Rentri | 58 passed | 185 |
| Roosko | 125 passed | 561 |
| Tenant | 208 passed, 17 failed, 40 skipped | 1123 |

**CI del base** (run del commit `1041c298`, 11/11): tutti i job verdi.
- Pest dei 7 moduli di dominio;
- PHPStan;
- bundle Vite di Geo e Roosko allineati ai sorgenti;
- numerazione concorrente del registro su **MariaDB 11.4**: 4 processi, 40 scritture, numeri 1..40 senza buchi né duplicati.

I 17 test falliti di Tenant sono preesistenti. Prima dell'intervento erano 18. Riguardano funzioni
di Tenant non usate da Roosko: domini e configurazioni JSON. Nessuno tocca l'alias delle connessioni.

### PHPStan

PHPStan livello 10 con la config unica `laravel/phpstan.neon`:
- **0 errori** su `Modules/{Roosko,Warehouse,Processing,Weighing,Labeling,Waste,Rentri}/app`;
- **0 errori** su `Tenant/app/Providers/TenantServiceProvider.php`.

## 5. Che cosa e' stato corretto

Ogni intervento ha una story in `docs/stories/` del modulo che lo possiede.

### Sicurezza

| Rilievo | Correzione |
|---|---|
| C1 | Template delle etichette a segnaposto chiusi, senza Blade, quindi niente RCE. Errori di stampa visibili. |
| A1 | `RolePolicy` riservata al super-admin. |
| A2 | Trait Xot `DeniesUndeclaredFilamentAbilities` nelle policy di dominio. |
| A3 | `authorize()` sulle azioni custom e `canAccess()` sulle pagine. |
| A4 | Via `.env.fixcity`, `.env.latest` e le configurazioni di altri tenant con segreti. La chiave Google e' ora in env. |
| M1 | `created_by` e `deleted_by` valorizzati dall'Updater di Xot. |
| M2 | Bilance e stampanti TCP solo su LAN o su host in allowlist (`WEIGHING_ALLOWED_SCALE_HOSTS`). |
| M3 | FIR immutabile fuori dalla bozza a livello di modello. Vale anche per il super-admin. |
| M4 | Seeder dei permessi per modulo. Ruoli `roosko.operator`, `roosko.acceptance`, `roosko.technical_manager`. |
| M5 | Planimetrie solo raster, su disco privato. |
| B1, B2 | Throttle su `/l/{code}` e URL dei QR da `APP_URL`. |
| B5 | Rate limit sulla lettura della bilancia. |
| B7 | Redazione dei payload RENTRI. |

### Integrita' dei dati

- **Lock**:
  - sul conferimento, con `open_fir_id` unico;
  - sulla capacita' delle aree;
  - sul registro, con numerazione senza buchi provata da 4 processi concorrenti;
  - nella guardia di stoccaggio.
- **Autorizzazioni dell'impianto valutate alla data dell'operazione**:
  - accettazione alla data di arrivo del conferimento;
  - stoccaggio e avvio del trattamento a oggi, non "una qualsiasi autorizzazione".
- **Tabelle legali che rifiutano `down()`**: registro e trasmissioni.
- **Tabella `locations` di Warehouse rinominata** in `warehouse_locations`, per la collisione con Geo.
- **Connessioni dei moduli**: puntano alla stessa istanza della connessione default finche' la
  config coincide, quindi le transazioni fra moduli sono atomiche. Una config ridefinita apre una
  connessione propria; lo usa il test di concorrenza di Waste.

### Prestazioni

- Indici sulle colonne di ricerca.
- Eliminati gli N+1 nelle tabelle e nei widget.
- Eliminati i `whereIn` illimitati.

### Repository e CI

- Xot versionato.
- Moduli di dominio allineati.
- Weighing ripulito, sia nel repo del modulo sia nel base.
- 162 file `.merge_file_*` rimossi.
- Nuovo `tests.yml`.
- Rimossi `ci-cd.yml` (FixCity) e `pint.yml`, che usava PHP 8.3 dalla cartella sbagliata.
- Migrazione di User solo-MySQL resa neutra sugli altri driver: prima bloccava `migrate` su sqlite.

### Bug trovati avviando l'applicazione vera (10 novembre)

I test di action non li vedevano: sono emersi aprendo i pannelli nel browser con dati di prova,
e sono tutti corretti.

| Bug | Effetto | Correzione |
|---|---|---|
| `IsUserDatabaseConfiguredAction` usata da User ma mai committata | ogni login in 500 | action aggiunta con test (User `b6607e7af`) |
| `roosko::admin` reso tenant-aware dal commit "." `2868288` | il personale dell'impianto non entrava in dashboard, conferimenti, pagina operatore | tenancy tolta, resta solo sull'area clienti (Roosko `2764122`) |
| Azioni di riga di `HasXotTable` tipizzate `Model` | dashboard dell'impianto in 500 (tabella con record array) | closure su `mixed` (Xot `0b456a417`) |
| Pagina operatore sul telefono | campo, bottoni e azioni tagliati fuori schermo; testo per screen reader visibile | CSS corretto, verificato a 412 px (Roosko `03c454d`) |
| `laravel-vite-plugin` ^1.3 con vite 8 | `npm install` del modulo fallisce, la pagina operatore va in 500 senza bundle | ^3.2 (Roosko `b22ef7b`) |
| Logo non configurato | immagine rotta su ogni pagina | logo Roosko in `config/local/roosko/metatag.php`; via i loghi di FixCity |
| Migrazione User solo MySQL | `migrate` si fermava su sqlite | uscita anticipata sugli altri driver (User `bdd4e646f`) |
| `merge-plugin` su `Modules/*` | `composer install` falliva da zero (modulo AI spento) | include solo dei moduli attivi |
| Lang nel base senza i trait estratti | ogni comando artisan in errore in CI | file aggiunti dal repo di Lang |

Restano piccole cose viste nelle schermate, non bloccanti:
- il pulsante *Nuovo* sui widget-tabella di riepilogo, aggiunto da `HasXotTable`;
- i badge di navigazione mostrano `--`;
- il titolo «Rentri Status Page» non e' tradotto;
- la pagina 404 del tema e' quella di FixCity.

### 11 novembre: flussi percorsi di nuovo, messa in produzione

Tutti i flussi sono stati percorsi di nuovo nel browser con i ruoli veri:

- conferimento, pesata, accettazione, stoccaggio, trattamento e prodotto;
- uscita e FIR emesso;
- area clienti.

Ogni difetto trovato e' corretto, con una story nel modulo proprietario.

| Difetto | Effetto | Correzione |
|---|---|---|
| Il ruolo del pannello (`{modulo}::admin`) concedeva ogni abilita' | l'operatore di piazzale poteva rettificare pesi ed eliminare conferimenti | trait `RequiresOperationalPermissions` su tutte le 32 policy di dominio (Xot, Roosko, Waste, Warehouse, Processing, Weighing, Labeling, Rentri) |
| `Spedisci` compariva solo ricaricando la pagina | dopo aver caricato le unita' l'uscita sembrava non spedibile | ricarica della pagina anche dopo le action delle tabelle collegate (Roosko) |
| FIR d'uscita in bozza invisibile nella scheda dell'uscita | non si capiva dove completarlo | «#id · Bozza» con collegamento al pannello Rifiuti e istruzioni (Roosko) |
| FIR cartaceo emesso senza numero e trasportatore | formulario non piu' modificabile e incompleto | blocco all'uscita dalla bozza, messaggio con i campi mancanti (Waste) |
| Registro RENTRI: scritture in attesa di esito reinviabili | movimenti doppi sul registro nazionale (R2) | lock del registro ed esclusione delle scritture `pending`/`submitted` (Rentri) |
| Operazioni del trattamento non filtrate | si poteva scegliere un'operazione non autorizzata per quel codice | elenco dalle operazioni autorizzate (Waste `GetAuthorizedOperationsAction`) |
| Indirizzo dei cantieri con codici ISTAT | «Viale delle Nazioni 12, 39014, 39, 48122» | «… 48122 Ravenna (RA)» (Geo `FormatAddressAction`) |
| Elenco dei comuni di 4 righe | indirizzi in Emilia-Romagna non registrabili | elenco ISTAT completo, 7.904 comuni (Geo) |
| Riepiloghi delle dashboard con «Nuovo», selezione e cambio layout | anche il cliente vedeva «Nuovo» | trait `ReadOnlyTableWidget` (Roosko) |
| «Pericoloso» con spunta verde | lettura invertita | triangolo ambra / trattino (Roosko) |
| Tabella utenti in inglese, 2.271 traduzioni segnaposto | etichette come «Is active», aiuti uguali al nome del campo | traduzioni vere e comando `lang:clean-placeholders` (Lang, User e altri moduli) |
| Build degli asset rotte (Geo con vite 8, Activity, tema) | `deploy.sh` si fermava lasciando l'app in manutenzione | bundle versionati nella cartella `public/` di moduli e temi, serviti da symlink relativi (`public_html/themes/<Tema>` → `../../laravel/Themes/<Tema>/public`); link relativi in Xot; `symlink:publish --force` non cancella piu' cartelle reali |
| Workflow del template sempre rossi | rumore in CI | rimossi; CI con Pest per modulo, PHPStan, controllo dei bundle, numerazione su MariaDB |

Per la produzione:

- `deploy.sh`;
- `deploy/backup-db.sh`, con i due database, compresso e conservato 30 giorni;
- `deploy/supervisor/roosko-workers.conf`, con la coda `rentri` a un tentativo e `--timeout=180`;
- `deploy/cron/roosko.cron`;
- `queue.retry_after` portato a 240 s, sopra il timeout dei job RENTRI;
- `.env.production.example`;
- il comando `roosko:production-check`;
- il [runbook](../operations/production-runbook.md);
- la pagina d'ingresso `/it`;
- le pagine di errore di Roosko.

### Moduli condivisi, suite complete

Xot e User aggiornati a `dev` del 10/11:
- Xot: 868 test passati, 268 falliti;
- User: 1012 test passati, 436 falliti.

I fallimenti sono del codice condiviso, non di Roosko: i test sulle tabelle Xot falliscono
uguali con e senza le correzioni di oggi. Vanno ripresi da chi mantiene quei moduli.

## 6. Rischi residui

| # | Rischio | Probabilita' | Impatto | Mitigazione |
|---|---|---|---|---|
| R1 | `APP_KEY`, password DB e IMAP, chiavi Matomo, Google Maps e Supermemory sono state committate in passato e restano nella storia git | certa (esposti) | alto | ruotarle. `APP_KEY` va ruotata con `APP_PREVIOUS_KEYS`, perche' la password del certificato RENTRI e' cifrata con quella chiave |
| R2 | ~~RENTRI: trasmissione duplicata dopo timeout e retry~~ **chiuso l'11/11**: FIR e accettazioni con `exchangeOnce`, registro con lock ed esclusione delle scritture in trasmissione, `retry_after` 240 s sopra il timeout dei job | bassa | alto (dato legale) | resta la prova sull'ambiente demo (R3) |
| R3 | 35 parametri RENTRI marcati `DA VERIFICARE` sullo Swagger demo | certa | alto | verifica con le credenziali demo dell'impianto |
| R4 | ~~Lock di riga provato solo su sqlite~~ **chiuso l'11/11**: `ConcurrentRegisterNumberingTest` gira anche su MariaDB (job CI dedicato) | bassa | alto | — |
| R5 | Backup, cron, supervisor e deploy ora scritti e documentati (runbook); **manca la prova di ripristino sul server** | bassa | alto | prova di restore prima dell'avvio, poi ogni trimestre |
| R6 | Licenza OSL-3.0 di `spipu/html2pdf`, usata per le etichette PDF | da verificare | medio | parere legale o sostituzione |
| R7 | `composer.lock` non versionato: ogni installazione risolve versioni diverse | media | medio | versionare il lock dell'app base (decisione, sez. 7) |
| R8 | ~~Copie dei moduli condivisi non allineate~~ **chiuso l'11/11**: Xot, User, Tenant, Lang, Geo e il tema allineati ai repo | — | — | — |

## 7. Decisioni che spettano al committente

1. **Pannello `roosko::admin` tenant-aware.** Un commit "." (`2868288`) lo aveva reso tale. Il
   personale non entrava piu', quindi la tenancy e' stata tolta. Se serviva una tenancy anche
   lato impianto (piu' impianti?), va progettata nella story dell'area clienti.
2. **Il cliente puo' modificare il numero di iscrizione RENTRI e lo stato attivo dei suoi
   cantieri.** Lo prevede la story (AC 4), ma il dato finisce nei payload RENTRI dell'impianto.
   Alternative: sola lettura, oppure modifica con approvazione dell'accettazione.
3. **Versionare `composer.lock`** dell'app base: oggi e' in `.gitignore`.
4. **Rotazione dei segreti** (R1): servono gli accessi ai servizi.
5. **Il ramo `restaurant::admin` in `BaseUser::canAccessPanel`** (modulo User condiviso) apre
   `/admin` a un ruolo di un altro progetto. Si toglie dal modulo condiviso o resta?

## 8. Prossimi passi consigliati

1. Rotazione dei segreti (R1) e conferma delle decisioni 1–3.
2. RENTRI sull'ambiente demo con le credenziali dell'impianto (R3).
3. Sul server: prima installazione seguendo il runbook, `roosko:production-check` senza bloccanti, prova di ripristino del backup.
4. Monitoraggio degli errori (Sentry o simile) e allarme sul controllo dello scheduler.
5. Suite dei moduli condivisi (Xot, User, Geo, Lang): i fallimenti preesistenti vanno ripresi da chi li mantiene.
