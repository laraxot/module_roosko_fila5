---
title: Roosko — roadmap di completamento
type: audit
module: Roosko
created: 2026-11-05
updated: 2026-11-05
qmd: "roadmap roosko fasi percorso critico area cliente produzione"
---

# Roadmap di completamento

## Percorso critico

```
B-00 Xot nel repo ──► B-02 CI verde ──► B-12 analisi statica in CI
        │
        └──► B-03 ruoli/permessi ──┐
B-01 decisione tenant ─────────────┴──► B-04 pannello cliente ──► B-05 utenti cliente
B-06 RENTRI demo (in parallelo, indipendente)
```

Senza B-00 e B-02 nessun altro intervento e' verificabile: sono la prima cosa da fare.

| Fase | Obiettivo | Attivita' | Completata quando | Rischi residui |
|---|---|---|---|---|
| 1 · Stabilizzazione | ambiente riproducibile e testato | B-00, B-02, B-03, B-07 | clone pulito avviabile; CI verde; nessun 500 da permessi | test esistenti che falliscono al primo vero run |
| 2 · Completamento funzionale | il cliente vede i suoi cantieri | B-01, B-04, B-05 | test di isolamento tra due clienti verdi | dati di dominio senza produttore valorizzato |
| 3 · Qualita' e affidabilita' | conformita' e flussi provati | B-06, B-11, B-12 | zero `DA VERIFICARE`; e2e dei flussi d'impianto | cambi di specifica RENTRI |
| 4 · Ottimizzazione | debito ridotto | B-08, B-09, B-10, B-15 | modulo User senza riferimenti a progetti | — |
| 5 · Production readiness | rilascio affidabile | backup/restore del DB, worker e scheduler (polling RENTRI ogni minuto), monitoraggio errori, rollback | runbook in `Roosko/docs` provato una volta | da verificare: hosting e deploy (`deploy.sh` non analizzato) |
| 6 · Evoluzione | valore aggiunto | B-13 impersonate, B-14, B-16; notifiche al cliente sull'esito dei conferimenti | — | — |

## Cosa fare subito, dopo, piu' tardi

- **Subito**: B-00, B-02, B-03 (ognuno S/M, sbloccano tutto il resto) e la decisione B-01.
- **Dopo**: B-04/B-05 area cliente, B-06 RENTRI in parallelo.
- **Rimandare**: B-13..B-16 e la pulizia della radice del base.
