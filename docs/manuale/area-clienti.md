---
title: Manuale — area clienti
type: manual
module: Roosko
updated: 2026-11-05
---

# Area clienti

[← Indice del manuale](README.md)

L'area clienti e' per le imprese che conferiscono i loro rifiuti all'impianto. Ogni cliente vede
**solo i propri** cantieri, formulari e conferimenti, mai quelli di altre imprese.

## Accesso

Si entra da `/roosko/customer/login` con le credenziali ricevute dall'impianto. Se si lavora per
piu' imprese clienti, si passa dall'una all'altra dal menu con il nome del cliente, in cima al menu laterale.

Se non si riesce a entrare, l'account non e' ancora abilitato: va contattato l'impianto.

## Riepilogo

La prima pagina mostra:

- **Conferito nel mese per cantiere**: per ogni cantiere, i kg **Accettati nel mese**.
- **Ultimi conferimenti**: gli ultimi dieci, con stato ed esito.

## I miei rifiuti

### Cantieri

L'elenco dei cantieri, con nome, numero di iscrizione RENTRI dell'unita' locale, stato (attivo o
no) e **Conferiti (kg)** in totale.

- **Nuovo cantiere**: nome, numero di iscrizione RENTRI, attivo, indirizzo. Se l'impresa ha piu'
  soggetti si sceglie a quale intestarlo; altrimenti lo intesta il sistema.
- Un cantiere si puo' **modificare** ma non eliminare. Per chiuderlo si toglie la spunta **attivo**.

I kg di un cantiere contano i conferimenti accettati il cui FIR indica quel cantiere come luogo
di produzione e la vostra impresa come produttore.

### Conferimenti

Ogni carico arrivato in impianto, in sola lettura: **Codice**, **Arrivo**, **FIR**,
**Cantiere**, **EER**, **Accettati (kg)** e **Stato**. Il dettaglio mostra:

- **Conferimento**: stato, data e ora di arrivo, numero FIR, cantiere, codice EER e quantita'
  dichiarata sul FIR.
- **Pesata**: **Lordo**, **Tara**, **Netto**.
- **Esito**: kg **Accettati** e **Respinti**, con il **Motivo del respingimento**.

Stati: **Arrivato**, **Pesato**, **In verifica**, **Accettato**, **Accettato parzialmente**,
**Respinto**, **Chiuso**.

### Formulari

I FIR in cui la vostra impresa e' il produttore, in sola lettura: numero, digitale (xFIR) o no,
soggetti (produttore, cantiere, trasportatore, destinatario), rifiuto (codice EER e descrizione,
operazione, quantita' e se il peso era stimato), trasporto e accettazione.

## Domande frequenti

- **Un conferimento non compare nel totale del cantiere.** Probabilmente il FIR non indica il
  cantiere come luogo di produzione: il conferimento si vede comunque in **Conferimenti**.
  Segnalatelo all'impianto per correggere il dato.
- **Vedo un'impresa in meno.** Il soggetto non e' collegato al vostro cliente: va chiesto
  all'impianto.
