---
title: Roosko — audit di progetto e roadmap di completamento
type: audit
module: Roosko
created: 2026-11-05
updated: 2026-11-10
scope: [base_roosko_fila5@7bf1639a, Roosko, Waste, Warehouse, Processing, Weighing, Labeling, Rentri, User, Xot, Tenant]
qmd: "audit roosko stato completamento gap roadmap area cliente cantieri tenancy rentri"
---

# Roosko — audit di progetto

> **Stato al 2026-11-10**: questo e' l'audit iniziale (solo lettura). Lo stato verificato dopo
> implementazione e test e' in [final-report.md](final-report.md).

Indice: [final-report.md](final-report.md) (stato verificato, rischi, decisioni) · [findings.md](findings.md) (rilievi con evidenze) · [backlog.md](backlog.md) (gap P0–P3
con criteri di accettazione) · [roadmap.md](roadmap.md) (fasi e percorso critico).

Modalita': **audit e pianificazione**. Nessuna modifica al codice applicativo in questa esecuzione.
Le uniche modifiche di codice citate (User `2aefb572`, Xot `f90357c6`) sono state fatte *prima*
dell'avvio dell'audit, sulla tenancy, e sono riportate come evidenze.

## 1. Executive summary

Roosko e' un **gestionale per un impianto di trattamento/cernita rifiuti**. Il personale
d'impianto lo usa su pannelli Filament e su una UI mobile "scan first" per operatori. Copre:
conferimento con FIR e pesata, unita' etichettate QR/RFID, stoccaggio in aree autorizzate,
lavorazione con tracciabilita' (lineage), uscite, registro di carico/scarico e RENTRI.

Il **dominio e' ben progettato**: 7 moduli separati, azioni Spatie, registro immutabile con
rettifiche e numerazione concorrente testata, guardie di autorizzazione allo stoccaggio, mass
balance. Il **prodotto non e' ancora pronto per utenti reali** per tre motivi verificati:

1. **L'area cliente c'e' ma non e' verificata in esecuzione.** Il pannello `roosko::customer`
   (story `01.area-cliente-cantieri`, commit `4d7d350`) e' arrivato su `dev` durante l'audit. La
   review indipendente non ha trovato fughe fra clienti e ha corretto i kg per cantiere (`7e9b90a`),
   ma i test di isolamento non sono stati eseguiti (vedi limiti).
2. **RENTRI non e' verificato.** Il client e' completo come struttura (JWT, firma, digest,
   polling), ma `Rentri/config` contiene **35 valori marcati `DA VERIFICARE`** sullo Swagger
   demo. Nessuna trasmissione e' stata provata contro l'ambiente demo.
3. **Il repository base non e' riproducibile ne' testato in CI.** `Modules/Xot` e' un gitlink
   senza `.gitmodules`, quindi un clone pulito non ha Xot. `tests.yml` usa PHP 8.3, mentre
   `composer.json` richiede `^8.4`, ed e' copiato da Fixcity.

## 2. Che cosa e' il progetto (evidenze)

| Fonte | Contenuto |
|---|---|
| `PRODUCT.md` (base) | utenti: operatori di piazzale, pesa/accettazione, responsabile tecnico, amministratore |
| `Modules/Roosko/docs/SPEC.md` | specifica dei 7 moduli e convenzioni Laraxot |
| `modules_statuses.json` | 19 moduli attivi: 7 di dominio + base Laraxot (Xot, User, Tenant, UI, Lang, Media, Geo, Job, Activity, Notify, Cms, Gdpr) |
| migrazioni `Roosko`, `Waste` | deliveries, waste_lots, shipments, area_waste_rules; ewc_codes, operators, operator_sites, plant_authorizations, authorized_wastes, firs, registers, register_entries |

**Il cliente nel dominio.** Il produttore e' `Waste\Operator` con il ruolo produttore. I suoi
"cantieri" sono `Waste\OperatorSite` (`operator_sites.operator_id`). I dati che deve vedere
sono collegati a lui da `firs.producer_operator_id` / `producer_site_id` e
`waste_lots.producer_operator_id`. **Nessuna tabella di dominio ha `tenant_id`.** Questo e'
il punto di progetto da decidere prima di costruire il pannello: vedi B-01 in backlog.

## 3. Matrice di conoscenza e confidenza

Criterio della conoscenza: quota dei file chiave dell'area letti davvero (modelli, migrazioni,
provider, azioni principali). Criterio della confidenza: le conclusioni sono verificate da piu'
fonti o da esecuzione (alta), oppure solo dalla lettura del codice (media).

| Area | Conoscenza | Confidenza | Evidenze | Incognite | Per aumentarla |
|---|---|---|---|---|---|
| Struttura repo e moduli | 80% | 85% | ls-tree, statuses, diff base↔repo modulo | contenuto del gitlink Xot `3e6edae6` | clone con submodule |
| Dominio Roosko/Waste | 55% | 70% | migrazioni, policy, modelli Operator/Site | state machine complete, azioni di accettazione | lettura delle 28 azioni Roosko |
| Tenancy / area cliente | 85% | 80% | Xot panel provider, User traits, docs Filament 5 | story parallela non pubblicata | leggere la story quando sara' pushata |
| Sicurezza | 45% | 60% | grep raw SQL, blade non escapate, route pubbliche, cast credenziali | policy di ogni modello, upload | Semgrep/Larastan in ambiente con vendor |
| RENTRI | 40% | 75% | config (35 `DA VERIFICARE`), lista azioni, scheduler | conformita' payload | prova end-to-end sull'ambiente demo |
| Test | 50% | 40% | 44 file di test nei 7 moduli | **non eseguiti** | Pest con PHP 8.4 |
| CI/DevOps | 50% | 80% | `tests.yml`, `.gitmodules` assente | workflow davvero attivi | log delle run GitHub Actions |
| UX operatore | 15% | 30% | `PRODUCT.md`, hook PWA | resa sul campo | test con browser o dispositivo |

## 4. Punteggi (provvisori dove indicato)

| Dimensione | Punteggio | Motivazione |
|---|---|---|
| Architettura di dominio | 80 | moduli generici separati dal dominio, azioni, guardie, immutabilita' |
| Completezza funzionale (impianto) | 65 | flussi principali presenti e testati a livello di azione; UX non verificata |
| Completezza funzionale (area cliente) | 65 *(provvisorio)* | pannello, scope, test scritti; manca l'esecuzione dei test |
| Sicurezza | 60 *(provvisorio)* | policy per permesso e bypass super-admin; isolamento cliente assente; nessun SAST eseguito |
| Testing | 50 *(provvisorio)* | buona copertura delle regole di dominio, ma suite non eseguita e CI rotta |
| Production readiness | 25 | CI rotta, repo non riproducibile, RENTRI non verificato |
| Documentazione | 55 | SPEC solida; radice del base piena di report generati obsoleti |

## 5. Tabella sintetica

| Area | Stato | Confidenza | Problemi critici | Priorita' |
|---|---|---|---|---|
| Area cliente / cantieri | implementata, revisionata, test non eseguiti | media | dipende da B-03 (permessi) per gli utenti non super-admin | P1 |
| Tenancy condivisa (User/Xot) | corretta il 2026-11-05 | media (test non eseguiti) | `tenant_id` saltato con chiave stringa (risolto) | P0 → fatto |
| RENTRI | implementato, non verificato | alta | 35 valori `DA VERIFICARE` | P1 |
| Repo base / CI | rotto | alta | Xot gitlink senza `.gitmodules`; CI PHP 8.3 e Fixcity | P0 |
| Flussi impianto | implementati | media | da provare end-to-end in UI | P2 |
| Sync moduli condivisi nel base | in deriva | alta | User 190 file, Xot 30 file diversi dai repo | P1 |

## 6. Limiti di questo audit

- **Non eseguiti**: Pest, PHPStan, Larastan, Semgrep, composer audit, npm audit, browser.
  La sandbox ha PHP 8.3 (il progetto richiede `^8.4`), nessun `vendor/` e nel clone del base
  manca Xot. Ogni rilievo nasce da lettura del codice o da grep, ed e' marcato cosi'.
- Letti in profondita': tenancy (Xot/User), schema Roosko/Waste, policy, route, config RENTRI, CI.
  Solo inventariati: azioni di Processing/Weighing/Labeling e le viste.
- Moduli presenti nel base ma non attivi (AI, Blog, Comment, Event, Mobile, Rating, Seo): fuori scopo.
