---
title: Manuale — amministrazione
type: manual
module: Roosko
updated: 2026-11-05
---

# Amministrazione

[← Indice del manuale](README.md)

## Configurazione iniziale dell'impianto (in quest'ordine)

1. **Rifiuti → Soggetti**: l'impresa che gestisce l'impianto, poi produttori, trasportatori e
   destinatari. **Unita' locali**: le sedi; quella dell'impianto ha la spunta di impianto.
2. **Rifiuti → Autorizzazioni impianto**: l'atto autorizzativo con i codici EER, le operazioni e i limiti.
3. **Magazzino** (`/warehouse/admin`): **Magazzini**, **Aree** e **Posizioni**. La **Mappa
   magazzino** mostra la disposizione. Conviene assegnare un'etichetta a ogni posizione: il piazzale la scansiona.
4. **Autorizzazioni → Regole di stoccaggio** (pannello Roosko): quali codici possono stare in
   quale area. Senza regole un'area non accetta rifiuti.
5. **Pesatura** (`/weighing/admin`): le bilance.
6. **Etichettatura** (`/labeling/admin`): stampanti e modelli di etichetta.
7. **Lavorazioni** (`/processing/admin`): le macchine.
8. **RENTRI** (`/rentri/admin`): le credenziali delle unita' locali.

## Utenti e accessi

- Il **super amministratore** si crea da riga di comando:
  `php artisan user:super-admin --email=...`. Il comando assegna anche l'accesso a tutti i
  pannelli dei moduli presenti; se in seguito si aggiunge un modulo, va rilanciato.
- Gli altri utenti si creano nel modulo utenti e ricevono un ruolo. L'accesso a un pannello
  richiede il ruolo con lo stesso nome del pannello (es. `roosko::admin`); le operazioni dentro
  il pannello richiedono i permessi (es. vedere o creare conferimenti).

### Ruoli del personale d'impianto

I ruoli e i permessi si creano con i seeder dei moduli (`php artisan module:seed Roosko`, e lo
stesso per Waste, Warehouse, Processing, Weighing, Labeling, Rentri). A ogni persona si danno **due
cose**: il ruolo operativo, che dice cosa puo' fare, e i ruoli dei pannelli in cui deve entrare.

| Chi | Ruolo operativo | Ruoli dei pannelli |
|---|---|---|
| Operatore di piazzale | `roosko.operator` | `roosko::admin` |
| Pesa e accettazione | `roosko.acceptance` | `roosko::admin`, `waste::admin`, `weighing::admin` |
| Responsabile tecnico | `roosko.technical_manager` | `roosko::admin`, `waste::admin`, `warehouse::admin`, `processing::admin`, `labeling::admin`, `weighing::admin`, `rentri::admin` |

Senza il ruolo del pannello il login a quel pannello risponde «I dati di accesso non sono
corretti», anche con la password giusta: e' il primo controllo da fare quando qualcuno non entra.
Vale anche per il super amministratore creato a mano, che va abilitato ai pannelli con
`user:super-admin`.

## Dare accesso a un cliente all'area clienti

1. Modulo utenti: creare il **cliente** con nome e codice (slug). Il codice compare nell'indirizzo.
2. Creare l'utente del cliente, o usarne uno esistente, e aggiungerlo come **membro** del
   cliente. Un utente puo' essere membro di piu' clienti.
3. Assegnare all'utente il ruolo **`roosko::customer`**.
4. Pannello Rifiuti → **Soggetti** → scheda dell'impresa produttrice: campo **Cliente (area
   clienti)** = il cliente. Un cliente puo' avere piu' soggetti, per esempio un gruppo di imprese.

L'utente entra da `/roosko/customer/login`. Vede solo i cantieri, i FIR e i conferimenti dei
soggetti collegati al suo cliente. Vedi [Area clienti](area-clienti.md).
