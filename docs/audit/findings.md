---
title: Roosko — rilievi dell'audit con evidenze
type: audit
module: Roosko
created: 2026-11-05
updated: 2026-11-05
qmd: "rilievi audit roosko sicurezza tenancy ci gitlink rentri da verificare restaurant canAccessPanel"
---

# Rilievi dell'audit

Classi: **BUG** dimostrato · **PROB** problema altamente probabile · **DEBT** debito tecnico ·
**INC** funzionalita' incompleta · **IPO** ipotesi da verificare. Percorsi relativi a
`base_roosko_fila5/laravel/` salvo indicazione.

## A. Architettura

- **A1 (positivo)** — Dipendenze di dominio pulite: Labeling/Weighing/Warehouse non conoscono i
  rifiuti, Waste e' il dominio normativo, Roosko orchestra (`Modules/Roosko/docs/SPEC.md`).
- **A2 DEBT** — Il modulo condiviso User contiene logica di un altro prodotto:
  `Modules/User/app/Models/BaseUser.php` `canAccessPanel()` ha un ramo per `'restaurant::admin'`
  e lo cita anche nel fallback. In Roosko e' codice morto; in generale accoppia un modulo
  condiviso a un singolo progetto. Ci va un'estensione per progetto, non un `if` sull'id.
- **A3 PROB** — Moduli condivisi in deriva: rispetto ai repo `laraxot/module_*_fila5@dev`
  il base ha 190 file diversi in User e 30 in Xot (`diff -rq`). Domain module allineati.
- **A4 DEBT** — Due significati di "tenant": il modulo `Tenant` (configurazione per
  installazione, `config/local/...`) e `User\Tenant` (tenant Filament). Vanno tenuti distinti nei docs
  e nei nomi: l'area cliente usa il secondo.

## B. Tenancy e area cliente (isolamento multi-tenant)

- **B1 BUG (risolto, User `2aefb572`)** — `InteractsWithTenant` aveva
  `setTenantIdAttribute(?int)` e `is_int()` mentre `tenants.id` e' `string`: l'associazione
  automatica al tenant andava in TypeError o saltava. `TenantScope` usava `tenant_id` non
  qualificato (ambiguo nelle join). Test aggiunti, **non eseguiti** (vedi limiti).
- **B2 DEBT (risolto, Xot `f90357c6`)** — `ApplyTenancyToPanelAction` era morta e configurava
  registrazione tenant aperta e ownership `tenants`. Sostituita da `XotBasePanelProvider::$hasTenancy`.
- **B3 INC** — Nessun legame utente↔cliente. `Waste\Operator` ha solo `sites()`. Nessuna
  pivot con gli utenti e nessuna relazione con `User\Tenant`. Senza questo legame il pannello cliente
  non ha nulla da filtrare.
- **B4 INC** — Le tabelle di dominio non hanno `tenant_id` (grep `tenant_id` nei 7 moduli: 0
  risultati). L'appartenenza al cliente passa da `producer_operator_id`. Vedi la decisione B-01.
- **B5 IPO** — `XotBasePanelProvider::$hasTenancy` usa sempre `XotData::getTenantClass()`.
  Se si sceglie `Operator` come tenant del pannello cliente, serve un override del modello
  tenant per pannello. Da coordinare con la story Xot `tenancy-pannelli`.

## C. Sicurezza

- **C1 (positivo)** — Policy per permesso su tutti i modelli di dominio, con `before()` super-admin
  (`XotBasePolicy`). Delete vincolato allo stato (es. `DeliveryPolicy::delete`).
- **C2 (positivo)** — `RentriCredential.certificate_password` ha il cast `encrypted` ed e' in `$hidden`.
  Unico raw SQL nel dominio: `orderByRaw` con binding (`Rentri/.../ResolveCredentialAction.php:33`).
- **C3 PROB** — Permessi come `delivery.viewAny` non risultano creati da nessun seeder dei
  7 moduli (grep). Le policy di dominio chiamano `hasPermissionTo()` di Spatie, che **lancia
  `PermissionDoesNotExist`** se il permesso non esiste, mentre le policy di User usano
  `hasPermissionToOrCreate()`. Per l'utente non super-admin (pesa, operatori, futuro cliente)
  il primo controllo quindi produce un errore 500 invece di un 403. Il super-admin non lo vede
  perche' `before()` risponde prima. Serve un seeder di ruoli e permessi di prodotto.
- **C4 basso** — `GET /l/{code}` (Labeling) non e' autenticato: risponde con redirect o 404 e
  quindi rivela se un codice esiste. La pagina di destinazione richiede comunque il login.
- **C5 basso** — `{!! $scannerScript !!}` in `pwa-head.blade.php`: il contenuto e' generato da
  Vite lato server, non arriva dall'utente. Accettabile, va solo annotato.
- **C6 nota** — L'accesso ai pannelli di modulo richiede il ruolo `{modulo}::admin`.
  `user:super-admin` li assegna per i moduli presenti in quel momento: per un modulo aggiunto
  dopo va rilanciato.

## D. Dati

- **D1 (positivo)** — Registro: `unique(register_id, year, number)`, rettifiche via
  `rectifies_entry_id`, test di concorrenza (`Waste/tests/Feature/ConcurrentRegisterNumberingTest.php`).
- **D2 DEBT** — Le FK sono `unsignedBigInteger()->index()` senza `foreign()`: l'integrita'
  referenziale e' affidata all'applicazione. Puo' essere una scelta Laraxot (connessioni per modulo):
  documentarla.
- **D3 DEBT** — `Modules/User/database/migrations` ha tre `create_tenants_table` (2023, 2026-01,
  2026-09) e un file `*.php.LARAXOT_CORRECT`.

## E. Integrazione RENTRI

- **E1 INC** — `Modules/Rentri/config/config.php`: 35 occorrenze di `DA VERIFICARE` (path,
  campi, codici) rispetto allo Swagger demo. Il client e' completo come struttura: JWT di
  autorizzazione e integrita', digest, firma remota, polling ogni minuto con `withoutOverlapping`.
  La conformita' non e' provata.

## F. Test e CI

- **F1 BUG** — `.github/workflows/tests.yml`: `php-version: '8.3'` contro `"php": "^8.4"`;
  `php artisan key:generate` prima di `composer install`; DB `fixcity_test`, cioe' copiato da Fixcity;
  checkout senza submodule. Non puo' produrre una run verde.
- **F2 BUG** — `laravel/Modules/Xot` e' un gitlink (`160000 3e6edae6`) senza `.gitmodules`
  (esiste solo `gitmodules.ini`): un clone pulito non ha Xot e l'app non parte.
- **F3 positivo** — 44 file di test sulle regole di dominio: immutabilita', guardie di
  stoccaggio, mass balance, lineage, flussi FIR/RENTRI.

## G. UX
- **G1 IPO** — La UI operatore (PWA, scanner) non e' stata provata: nessun ambiente avviabile.

## H. Documentazione
- **H1 DEBT** — La radice del base contiene decine di report generati e obsoleti
  (`PHPSTAN_*_REPORT.md`, `phpstan_*.json`, `report.txt`, `x`) e bootstrap per 4 agenti.
  Rumore che fa sprecare contesto agli agenti.
- **H2 DEBT** — `laravel/Xot` e' un file vuoto in radice dell'app.
