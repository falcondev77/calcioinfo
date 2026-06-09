# Serie A News (PHP)

Aggregatore di notizie sulle 20 squadre del prossimo campionato di Serie A.
Raccoglie titoli, sommari, immagini e date dai principali feed RSS italiani
(Gazzetta, Sky Sport, Tuttosport, Corriere dello Sport, TMW, Calciomercato.com)
e li filtra per squadra, categoria (calciomercato, allenatori, conferenze,
formazioni) e ricerca testuale.

## Stack
- **PHP** puro 8.x (nessun framework, nessuna dipendenza esterna).
- **Storage locale**: schema SQL canonico in `database.sql`, persistenza
  runtime in `data/db.json` (l'ambiente di esecuzione non ha `pdo_sqlite`,
  quindi usiamo un file JSON con la stessa forma logica delle tabelle).
- **Frontend**: HTML/CSS server-side + un piccolo JS per il bottone "Aggiorna".

## Struttura
```
database.sql          schema + seed teams (compatibile SQLite/MySQL/Postgres)
index.php             UI principale
fetch.php             endpoint che lancia il crawl di tutti i feed
lib/teams.php         20 squadre con alias e colori sociali
lib/storage.php       layer JSON-backed (CRUD su articles)
lib/rss.php           parser RSS regex-based + HTTP getter
lib/matcher.php       match squadra via alias + classifica categoria
lib/fetcher.php       coordina feed + parser + storage
assets/styles.css     stili
data/db.json          dati runtime (creato al primo refresh)
```

## Come si usa
Servire la cartella con il built-in server di PHP:

```sh
php -S 0.0.0.0:8000
```

Apri `http://localhost:8000/`. Al primo avvio non ci sono notizie: premi
**Aggiorna** per fare il fetch da tutti i feed RSS configurati. Da quel
momento la pagina mostra le notizie filtrate per squadra/categoria/ricerca.

Refresh anche da CLI / cron:

```sh
php -r 'require "lib/fetcher.php"; print_r(fetcher_run());'
```

## Migrare a un DB reale
`database.sql` contiene lo schema canonico:

```sh
sqlite3 database.db < database.sql
# poi adattare lib/storage.php a PDO sqlite/mysql/pgsql
```
