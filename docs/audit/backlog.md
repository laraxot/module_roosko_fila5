---
title: Roosko — backlog dei gap (P0–P3)
type: audit
module: Roosko
created: 2026-11-05
updated: 2026-11-05
qmd: "backlog gap roosko P0 P1 area cliente cantieri tenant operator permessi rentri ci gitlink"
---

# Backlog dei gap

Sforzo: S (meno di 1 giorno), M (1–3 giorni), L (piu' di 3 giorni). Riferimenti ai rilievi in
[findings.md](findings.md).

## P0 — Blocking

### B-00 · Base riproducibile: Xot nel repository
- **Modulo**: base · **Evidenza**: F2 (gitlink `laravel/Modules/Xot` senza `.gitmodules`)
- **Attuale**: un clone pulito non ha Xot, quindi l'app non si avvia. **Atteso**: clone + `composer install` funzionano.
- **Strategia**: allineare Xot agli altri moduli (tree versionato come User, Waste ecc.), oppure creare un `.gitmodules` reale. Rimuovere il file vuoto `laravel/Xot`.
- **Accettazione**: in un clone pulito `ls laravel/Modules/Xot/app` non e' vuoto e `php artisan about` termina senza errori. · **Sforzo** S · **Confidenza** alta

### B-02 · CI che esegue davvero i test
- **Evidenza**: F1 · **Dipende da**: B-00
- **Strategia**: `tests.yml` con PHP 8.4, `composer install` prima di `key:generate`, DB del progetto (non `fixcity_test`), sqlite in memoria per gli unit test.
- **Accettazione**: run verde su `dev` per le suite dei 7 moduli di dominio, User e Xot. · **Sforzo** M · **Confidenza** alta

### B-03 · Ruoli e permessi di prodotto
- **Evidenza**: C3 · **Impatto**: errore 500 per ogni utente che non e' super-admin
- **Strategia**: seeder idempotente nel modulo Roosko con i ruoli `pesa`, `operatore`, `responsabile-tecnico`, `cliente` e i permessi `{model}.{ability}` di tutte le policy di dominio. In alternativa: policy su `hasPermissionToOrCreate`, come in User.
- **Accettazione**: test Pest in cui un utente `operatore` apre la lista dei conferimenti (200) e un utente senza permesso riceve 403, mai 500. · **Sforzo** M · **Confidenza** media-alta

## P1 — Essential

### B-01 · Decisione: chi e' il tenant del pannello cliente — **DECISA**

Scelta nella story `01.area-cliente-cantieri`: il cliente e' `User\Tenant` e il legame passa da
`operators.tenant_id` (nessun `tenant_id` sui dati di dominio, scope nelle resource). Restano le
opzioni sotto come storico della discussione.

- **Evidenza**: B3, B4, B5 · **Blocca**: B-04, B-05
- **Opzione A (raccomandata)**: il tenant Filament del pannello cliente e' `Waste\Operator` (il
  produttore), con pivot `operator_user` e ownership via le relazioni gia' esistenti
  (`OperatorSite::operator`, `Fir::producer`, `WasteLot::producer`). Pro: nessuna migrazione
  dei dati di dominio, si sfrutta lo schema reale. Contro: serve un modello tenant configurabile
  per pannello in `XotBasePanelProvider` (B5). Inoltre `Delivery` arriva al produttore solo
  passando da `fir`: lo scoping automatico di Filament non copre le relazioni through, quindi la
  sua resource cliente va filtrata a mano
  (`getEloquentQuery()->whereHas('fir', fn ($q) => $q->whereBelongsTo(Filament::getTenant(), 'producer'))`), con un test dedicato.
- **Opzione B**: `User\Tenant` come cliente, con `operators.tenant_id` e `tenant_id` denormalizzato
  sulle tabelle di dominio. Pro: usa `InteractsWithTenant` cosi' com'e'. Contro: dato duplicato
  (operator → tenant) da tenere coerente, contrario a "no duplicate state".
- **Accettazione**: decisione registrata nella story `01.area-cliente-cantieri` con la motivazione. · **Sforzo** S

### B-04 · Pannello cliente "I miei cantieri" — **implementato** (`4d7d350`, review `7e9b90a`), da verificare con i test
- **Dipende da**: B-01, B-03 · **Strategia**: pannello con `$hasTenancy = true` e
  `$componentsDirectory = 'Customer'`, ospitato da Roosko. Resource in sola lettura: cantieri
  (`OperatorSite`), FIR e conferimenti dei suoi cantieri, stato e quantita' accettate/respinte. Niente registrazione tenant.
- **Accettazione**: (1) il cliente A non vede i record del cliente B ne' in lista ne' via URL diretto (404); (2) cambiando l'id del tenant nell'URL riceve 403 o 404; (3) il super-admin vede tutto dai pannelli di gestione; (4) test Pest per ciascun punto. · **Sforzo** L · **Confidenza** alta

### B-05 · Utenti del cliente: invito e collegamento
- Il super-admin crea il cliente e invita i suoi utenti; un utente puo' avere piu' clienti (switcher Filament). Accettazione: test sul flusso di invito e su `canAccessTenant`. · **Sforzo** M

### B-06 · RENTRI verificato sull'ambiente demo
- **Evidenza**: E1 · **Strategia**: chiudere i 35 `DA VERIFICARE` contro lo Swagger demo; una prova end-to-end per FIR digitale, accettazione e registro.
- **Incluso**: la trasmissione periodica del registro, che oggi Roosko non orchestra (limite noto in `docs/README.md`).
- **Accettazione**: zero `DA VERIFICARE` in config; trasmissioni demo con esito positivo; esiti registrati nelle trasmissioni. · **Sforzo** L · **Confidenza** alta

### B-07 · Allineare i moduli condivisi nel base
- **Evidenza**: A3 · **Accettazione**: `diff -rq` tra il base e `module_{user,xot}_fila5@dev` vuoto, oppure differenze motivate. · **Sforzo** S

## P2 — Important

- **B-08** · Togliere da `BaseUser::canAccessPanel` il ramo `restaurant::admin` (A2) e spostarlo nel progetto che lo usa. Accettazione: nessun id di pannello di progetto nel modulo User. · S
- **B-09** · Pulire le migrazioni `tenants` duplicate e il file `.LARAXOT_CORRECT` (D3). · S
- **B-10** · Documentare la scelta delle FK applicative (D2) in `Waste/docs`. · S
- **B-11** · Provare end-to-end in browser i flussi d'impianto (conferimento → unita' → stoccaggio → trattamento → uscita) e la UI operatore (G1). · M
- **B-17** · Controllo e widget del limite annuo `max_annual_kg` (oggi solo il limite istantaneo; limite noto in `docs/README.md`). · M
- **B-12** · Eseguire Larastan/Semgrep/composer audit nella CI dopo B-02. · S

## P3 — Enhancement

- **B-13** · Impersonate del cliente dal pannello di gestione (idea da tomatophp/filament-tenancy).
- **B-14** · `GET /l/{code}`: risposta uniforme per non autenticati (C4).
- **B-15** · Pulizia della radice del base dai report generati (H1).
- **B-16** · `user:super-admin` o hook che assegna `{modulo}::admin` ai moduli aggiunti dopo (C6).
