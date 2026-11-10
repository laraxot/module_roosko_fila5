---
title: Manuale — accettazione e uscite
type: manual
module: Roosko
updated: 2026-11-05
---

# Ufficio pesa e accettazione

[← Indice del manuale](README.md)

Pannello Roosko, gruppo **Impianto**: **Conferimenti**, **Lotti**, **Uscite**.

## Conferimenti (ingresso)

![Elenco dei conferimenti con stato, FIR e chili accettati.](img/03-conferimenti.png)

*Elenco dei conferimenti con stato, FIR e chili accettati.*

### 1. Registra arrivo

Pulsante **Registra arrivo**. Si compilano:

- **FIR**: cercato per numero. Il FIR va registrato prima nel pannello Rifiuti, oppure arriva da RENTRI se e' digitale.
- **Impianto** e **Magazzino** di destinazione.
- **Peso lordo** se il mezzo e' sulla pesa a ponte, con la **Bilancia** usata. Si lascia vuoto
  se si pesa a pianale.

Il conferimento nasce nello stato **Arrivato**.

### 2. Pesa tara

![Conferimento pesato in attesa di decisione: in alto i pulsanti per accettare o respingere.](img/04-conferimento-pesato.png)

*Conferimento pesato in attesa di decisione: in alto i pulsanti per accettare o respingere.*

A scarico avvenuto, pulsante **Pesa tara**: si inserisce la **Tara** (peso del mezzo vuoto). Il
netto lo calcola il sistema. Stato: **Pesato**.

### 3. Ispezione (facoltativa)

Pulsante **Ispezione**: note di ispezione e la spunta **Analisi richiesta**.

### 4a. Accetta

![Conferimento accettato: pesata, accettazione e unita' create.](img/05-conferimento-accettato.png)

*Conferimento accettato: pesata, accettazione e unita' create.*

Pulsante **Accetta**. Il carico accettato si divide in **unita'**, cioe' i contenitori fisici:

- **Kg accettati** ed eventuali **Kg respinti**, con il **Motivo del respingimento parziale**.
- **Operazione** di gestione (es. *R13 messa in riserva*).
- Per ogni unita': **Contenitore**, **Peso netto**, **Tara contenitore** e, se si vuole subito,
  la **Posizione**. La posizione si puo' anche assegnare dopo, con la scansione.

La somma dei pesi netti delle unita' deve coincidere con i kg accettati: se non coincide, il
sistema non accetta.

Il sistema poi crea le unita' con il loro QR e manda in stampa le etichette. Scrive il carico sul
registro e l'accettazione sul FIR; se il FIR e' digitale, invia l'accettazione a RENTRI. Stato:
**Accettato** oppure **Accettato parzialmente**.

### 4b. Respingi

Pulsante **Respingi**, con il **Motivo**. Il carico non entra: non si creano unita' ne' carichi di
registro. Il respingimento viene annotato sul FIR e inviato a RENTRI se il FIR e' digitale. Stato: **Respinto**.

### Stati del conferimento

**Arrivato** → **Pesato** → (**In verifica**) → **Accettato** / **Accettato parzialmente** /
**Respinto** → **Chiuso**. Un conferimento si puo' eliminare solo finche' e' aperto.

## Lotti

![Lotti con classificazione, destino e stato delle unita'.](img/06-lotti.png)

*Lotti con classificazione, destino e stato delle unita'.*

**Lotti** mostra, per ogni unita' accettata o prodotta, il codice EER, la classificazione
(**Rifiuto**, **End of Waste**, **Sottoprodotto**) e il destino (**Trattamento interno**,
**Vendita**, **Recupero presso terzi**, **Smaltimento**, **Bonifica**). Dal dettaglio si vede la
tracciabilita' a monte, cioe' da quali unita' e conferimenti deriva il lotto. Con **Modifica** si
cambiano il destino e i riferimenti End of Waste.

## Uscite

1. **Nuova uscita**: **Magazzino**, **Destinatario** e **Sede di destinazione**, **Documento**
   (FIR, DDT o Dichiarazione di conformita' EoW), **Operazione a destino** (codice R/D) ed
   eventuale **Numero documento**. Per il FIR il numero si genera alla spedizione.
2. **Carica le unita'**: si scansiona il QR o si digita il **Codice unita'**. Dal piazzale si puo'
   usare anche «Aggiungi a uscita». Il sistema controlla che le unita' siano coerenti con il
   documento, la classificazione e il destino: per esempio un solo codice EER per FIR.
3. **Spedisci**: le unita' risultano uscite. Per i rifiuti si crea il FIR (digitale se il sito ha
   la credenziale RENTRI) e si scrive lo scarico di registro. Per l'End of Waste serve il numero
   della dichiarazione e non si scrive nulla sul registro.

Stati dell'uscita: **Bozza** → **In carico** → **Pronta** → **Spedita** → **Consegnata**
(oppure **Annullata** prima della partenza).

### Togliere un'unita' o annullare un'uscita

- **Togli**, sulla riga di un'unita' dell'uscita, la toglie dall'uscita e la libera: torna in giacenza
  e si puo' stoccare o caricare altrove.
- **Annulla uscita** annulla un'uscita non ancora spedita e libera tutte le unita' caricate. Un'uscita
  gia' spedita non si annulla: ha generato FIR e scarico di registro.
