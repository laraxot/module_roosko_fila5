---
type: story
slug: flow-walkthrough-fixes
title: "Collaudo dei flussi nel browser: correzioni"
status: done
owner: Roosko
created: 2026-11-10
updated: 2026-11-10
related:
  - "../audit/final-report.md"
  - "../../../Waste/docs/stories/authorized-operations-options.story.md"
  - "../../../Geo/docs/stories/comuni-istat-dataset.story.md"
  - "../../../Lang/docs/stories/placeholder-translations.story.md"
---

# Collaudo dei flussi nel browser: correzioni

## Come e' stato fatto

App avviata con dati di prova, Playwright come utente reale:

- tutte le 97 pagine dei pannelli, come amministratore e come cliente;
- il flusso completo, passo per passo:
  1. FIR in arrivo;
  2. registrazione dell'arrivo;
  3. tara;
  4. accettazione in due unita';
  5. stoccaggio dal telefono;
  6. avvio del trattamento;
  7. registrazione del prodotto;
  8. chiusura del trattamento;
  9. nuova uscita, carico e spedizione;
- un cantiere creato dall'area clienti.

## Corretto

| Problema | Effetto | Correzione |
|---|---|---|
| Il ruolo del pannello `roosko::admin` concedeva tutte le abilita' (`XotBasePolicy::before`) | l'operatore di piazzale poteva rettificare pesi ed eliminare conferimenti | trait Xot `RequiresOperationalPermissions` in tutte le policy dei moduli di dominio; test in `AuthorizationTest` |
| Tabella delle unita' ferma dopo "Accetta" | il conferimento accettato mostrava "Nessun risultato" | `NotifiesDomainErrors` ricarica la pagina di dettaglio dopo l'azione |
| FIR senza numero nella select di arrivo | `#8`, `#9` indistinguibili | etichetta: numero, produttore, EER, quantita' dichiarata |
| Impianto e magazzino da scegliere anche se unici | un passaggio in piu' a ogni arrivo e uscita | gia' selezionati quando c'e' una sola scelta |
| Operazione proposta R13 fissa all'accettazione | l'operatore doveva correggerla | l'operazione dichiarata sul FIR |
| 30 operazioni R/D sul telefono all'avvio del trattamento | scelta lunga e quasi tutta vietata | solo le operazioni autorizzate per l'EER dell'unita' (Waste `GetAuthorizedOperationsAction`); macchina gia' scelta se unica |
| Destinatari senza ruolo di destinatario nella nuova uscita con FIR | errore scoperto solo alla spedizione, dopo il carico | elenco filtrato per ruolo; il messaggio d'errore dice il ruolo in italiano |
| Date all'americana (`nov 10, 2026`) | poco leggibili | `d/m/Y` e `d/m/Y H:i` in tutte le tabelle e schede |
| Quantita' del FIR non formattata (`1800.000`) | incoerente con le pesate | `1.800,000 kg`, con "stimata" quando lo e' |
| "Analisi richiesta: no" con una X rossa | sembrava un errore | trattino grigio |
| Aiuto del campo Bilancia con il nome del campo | `scale_id` sotto la select | testo d'aiuto vero |

## Corretto nei moduli condivisi, visto da qui

- **Xot**:
  - pulsante "Indietro" non tradotto;
  - titolo della barra laterale in inglese;
  - link "Main Panel" anche dove non si puo' entrare, area clienti compresa;
  - ritorno dopo "Salva" verso pagine di altri pannelli;
  - badge di navigazione `--` per la cartella dei JSON mancante.
- **Lang**:
  - titolo di ogni sezione ripetuto due volte;
  - testi d'aiuto e segnaposto uguali al nome del campo, salvati dal salvataggio automatico.
- **Geo**: i comuni erano solo 4 e il CAP mandava in errore la select: non si poteva registrare un cantiere fuori da Lombardia e Sicilia.
- **Waste**: pulsante "Modifica" su FIR non in bozza, che portava a una pagina 403.
- **Rentri**: titolo della pagina di stato non tradotto.
- **App**: 107 testi di Filament senza traduzione italiana (`lang/vendor`).

## Seconda prova dell'uscita (2026-11-11)

- **Spedisci compariva solo ricaricando la pagina**: caricare o togliere un'unità (tabella collegata) cambia
  stato e totale dell'uscita, ma la testata restava quella di prima. `NotifiesDomainErrors::reloadRecordPage()`
  ora ricarica la pagina anche dopo le action dei relation manager.
- **FIR d'uscita invisibile**: nella scheda dell'uscita il campo FIR era vuoto, perché la bozza non ha numero. Ora
  mostra «#id · Bozza» con il collegamento alla scheda nel pannello Rifiuti (solo per chi può aprirla) e spiega cosa
  completare prima di emetterlo.
- **Testo fuorviante**: «per il FIR si genera alla spedizione» → alla spedizione nasce la bozza; numero e
  trasportatore si completano nel pannello Rifiuti, mentre il FIR digitale lo numera RENTRI.
- In Waste il FIR cartaceo non si emette più senza numero e trasportatore (story `fir-cartaceo-numero-trasportatore`).
