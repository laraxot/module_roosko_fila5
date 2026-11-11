---
title: Roosko — runbook di produzione
type: runbook
module: Roosko
created: 2026-11-10
updated: 2026-11-11
qmd: "roosko produzione deploy rilascio backup restore cron scheduler supervisor code rentri worker controllo"
related:
  - "../audit/final-report.md"
  - "../manuale/amministrazione.md"
---

# Roosko — runbook di produzione

Cosa serve sul server, come si rilascia, come si controlla e come si ripristina. I file citati sono nel
repository base (`base_roosko_fila5`).

## 1. Requisiti del server

| Cosa | Versione / nota |
|---|---|
| PHP | 8.4, con `intl`, `mbstring`, `bcmath`, `gd`, `zip`, `pdo_mysql`, `pcntl`, `posix`, `exif` |
| Database | MySQL 8 o MariaDB 10.11+, **due database**: applicazione (`DB_DATABASE`) e utenti (`DB_DATABASE_USER`) |
| Web server | document root su `public_html/`, HTTPS obbligatorio (QR delle etichette, fotocamera del telefono) |
| Node.js | non serve in produzione: gli asset compilati sono nel repository (solo `BUILD_ASSETS=1` li ricompila) |
| Supervisor | worker delle code |
| Cron | scheduler di Laravel e backup notturno |
| `mysqldump` | backup |

## 2. Prima installazione

1. **Codice**: clone del base su `dev` o del tag di rilascio.
2. **Configurazione**:
   - `cp laravel/.env.production.example laravel/.env`;
   - compilare i valori tra `<>`;
   - `php artisan key:generate` (solo ora: la chiave cifra la password del certificato RENTRI).
3. **Rilascio**: `./deploy.sh`.
4. **Worker**: copiare `deploy/supervisor/roosko-workers.conf` in `/etc/supervisor/conf.d/`, adattare percorso e utente, poi `supervisorctl reread && supervisorctl update`.
5. **Cron**: `crontab -u www-data deploy/cron/roosko.cron`, adattando il percorso.
6. **Ruoli e permessi**: `php artisan module:seed Roosko` e lo stesso per Waste, Warehouse, Processing, Weighing, Labeling, Rentri. Poi il primo super-admin con `user:super-admin`.
7. **Dati dell'impianto** dai pannelli (vedi [manuale, Amministrazione](../manuale/amministrazione.md)):
   - impianto (unità locale con «è un impianto»);
   - autorizzazioni con codici EER e operazioni;
   - magazzino, aree, posizioni, regole di stoccaggio;
   - bilance, stampanti;
   - credenziale RENTRI.
8. **Verifica**: `php artisan roosko:production-check` deve dire «Nessun controllo bloccante».

## 3. Rilascio di una nuova versione

```bash
git pull origin dev   # oppure checkout del tag
./deploy.sh
```

`deploy.sh` esegue, in ordine:
1. manutenzione;
2. backup dei due database;
3. dipendenze PHP;
4. symlink relativi degli asset: `public_html/assets/{modulo}` → `../../laravel/Modules/{Modulo}/public` e `public_html/themes/{Tema}` → `../../laravel/Themes/{Tema}/public` (`xot:module-link`, `xot:theme-link`). I link non si committano: li elencano `public_html/assets/.gitignore` e `public_html/themes/.gitignore`, che i comandi aggiornano da soli;
5. migrazioni;
6. cache;
7. `queue:restart`;
8. uscita dalla manutenzione;
9. `roosko:production-check`.

Se un passo fallisce l'app **resta in manutenzione**: si legge l'errore, si corregge e si rilancia.
`php artisan up` a mano solo se codice e database sono coerenti.

Variabili utili:
- `BUILD_ASSETS=1` per ricompilare gli asset sul server (serve Node 20+); di norma non serve, perché i bundle sono versionati;
- `ASSET_DIRS="..."` per scegliere cosa ricompilare (default: Geo, Roosko e il tema TwentyOne).

## 4. Controlli

`php artisan roosko:production-check`:

| Controllo | Bloccante se |
|---|---|
| `app.debug` | `APP_DEBUG=true` in produzione |
| `app.key` | chiave assente |
| `app.url` | non in https |
| `queue` | `QUEUE_CONNECTION=sync` |
| `queue.retry_after` | `retry_after` della coda non supera il timeout dei job RENTRI (~150 s): rischio di doppio invio |
| `scheduler` | il cron di `schedule:run` non ha girato negli ultimi 5 minuti |
| `writable.*` | `storage` o `bootstrap/cache` non scrivibili |
| `data.municipalities` | elenco dei comuni incompleto (indirizzi non compilabili) |

Sono solo avvisi:
- asset non compilati: la pagina operatore funziona senza fotocamera;
- mail in `log`;
- nessun impianto, nessuna autorizzazione valida, nessuna credenziale RENTRI.

## 5. Code e processi

| Coda | Cosa | Worker |
|---|---|---|
| `default` | stampa delle etichette, lavoro asincrono | 2 processi, `--tries=3` |
| `rentri` | FIR digitali, accettazioni, invii al registro | 1 processo, `--tries=1 --timeout=180` |

Il worker RENTRI ha **un solo tentativo**: un invio non si ripete alla cieca. Gli errori restano in
*Trasmissioni RENTRI* con il pulsante *Riprova*.

Lo scheduler verifica ogni minuto gli esiti delle transazioni RENTRI (`rentri:poll-pending-transactions`).

## 6. Backup e ripristino

- **Notturno** (cron) e **prima di ogni rilascio**: `deploy/backup-db.sh` scrive in `laravel/storage/backups` i due database compressi e tiene 30 giorni.
- **Copia fuori dal server**: il registro di carico e scarico e le trasmissioni RENTRI sono dati legali, quindi una copia notturna va su un altro host (rsync, storage esterno).
- **File da salvare** oltre al database:
  - `laravel/.env`;
  - `laravel/storage/app` (planimetrie e documenti delle autorizzazioni sul disco privato, certificati RENTRI).

Ripristino:

```bash
php artisan down
gunzip -c storage/backups/<db>-<data>.sql.gz | mysql -h <host> -u <utente> -p <db>
gunzip -c storage/backups/<db_utenti>-<data>.sql.gz | mysql -h <host> -u <utente> -p <db_utenti>
php artisan optimize:clear && php artisan up
```

**Prova di ripristino**: almeno una volta prima dell'avvio e poi ogni trimestre, su un database di prova.

## 7. Segreti

- **`APP_KEY`**: per ruotarla, la vecchia va in `APP_PREVIOUS_KEYS`, così la password del certificato RENTRI resta leggibile. Dopo, si risalva la credenziale RENTRI per cifrarla con la chiave nuova.
- **Segreti committati in passato**: le chiavi presenti in vecchi commit del repository (vedi [rapporto finale](../audit/final-report.md), R1) vanno considerate pubbliche e cambiate.

## 8. Problemi frequenti

| Sintomo | Causa | Rimedio |
|---|---|---|
| Login: «I dati di accesso non sono corretti» con password giusta | manca il ruolo del pannello (`roosko::admin`, `waste::admin`, …) | assegnarlo dal modulo User |
| Pagina operatore senza fotocamera | manca il symlink `public_html/assets/roosko` | `php artisan xot:module-link` (lo esegue `deploy.sh`) |
| Pagine pubbliche (login, recupero password) in errore «Vite manifest not found» | manca il symlink del tema | `php artisan xot:theme-link` (lo esegue `deploy.sh`) |
| FIR digitali fermi «in attesa» | worker `rentri` o cron fermi | `supervisorctl status`, `roosko:production-check` |
| Select dei comuni vuota | `comuni-istat.json` mancante nel modulo Geo | ripristinare il file dal repository di Geo |
