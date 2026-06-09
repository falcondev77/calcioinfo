<?php
declare(strict_types=1);

require_once __DIR__ . '/rss.php';
require_once __DIR__ . '/scraper.php';
require_once __DIR__ . '/matcher.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/teams.php';

/**
 * Quanti giorni indietro accettare. Articoli piu vecchi vengono scartati
 * sia in fase di import che in fase di pulizia del DB.
 */
const FRESHNESS_DAYS = 14;

/**
 * Lista feed RSS italiani: generali + per-squadra (TMW).
 */
function feeds_all(): array {
    $generic = [
        ['source' => 'TuttoMercatoWeb',     'url' => 'https://www.tuttomercatoweb.com/rss'],
        ['source' => 'Calciomercato.com',   'url' => 'https://www.calciomercato.com/rss'],
        ['source' => 'Gazzetta',            'url' => 'https://www.gazzetta.it/rss/calcio.xml'],
        ['source' => 'Sky Sport',           'url' => 'https://sport.sky.it/rss/calcio.xml'],
        ['source' => 'Corriere dello Sport','url' => 'https://www.corrieredellosport.it/rss/calcio.xml'],
        ['source' => 'Tuttosport',          'url' => 'https://www.tuttosport.com/rss/calcio.xml'],
    ];
    $perTeam = [];
    foreach (teams_all() as $team) {
        $perTeam[] = [
            'source' => 'TMW ' . $team['name'],
            'url'    => 'https://www.tuttomercatoweb.com/rss/' . $team['slug'],
        ];
    }
    return array_merge($generic, $perTeam);
}

/**
 * Esegue il fetch di tutti i feed e popola il database.
 * Scarta articoli con data non parsabile o piu vecchi di FRESHNESS_DAYS.
 * Ritorna un report con feed totali, falliti, nuovi articoli e scartati.
 */
function fetcher_run(int $maxAgeDays = FRESHNESS_DAYS): array {
    $started      = date('c');
    $cutoffTs     = time() - ($maxAgeDays * 86400);
    $feeds        = feeds_all();
    $sites        = scraper_targets();
    $failed       = [];
    $collected    = [];
    $skippedStale = 0;
    $skippedNoDate= 0;
    $rssItems     = 0;
    $scrapeItems  = 0;

    // ---- 1) RSS feeds ----------------------------------------------------
    foreach ($feeds as $feed) {
        $body = rss_http_get($feed['url']);
        if ($body === null) {
            $failed[] = 'RSS:' . $feed['source'];
            continue;
        }
        $items = rss_parse($body);
        if ($items === []) {
            $failed[] = 'RSS:' . $feed['source'];
            continue;
        }
        foreach ($items as $item) {
            $rssItems++;
            $row = build_article_row($item, $feed['source'], $cutoffTs, $skippedStale, $skippedNoDate);
            if ($row !== null) $collected[] = $row;
        }
    }

    // ---- 2) HTML scraping ------------------------------------------------
    foreach ($sites as $site) {
        $html = scraper_http_get($site['url']);
        if ($html === null) {
            $failed[] = 'WEB:' . $site['source'];
            continue;
        }
        $items = scraper_extract_articles($html, $site['url'], $site['pattern']);
        if ($items === []) {
            $failed[] = 'WEB:' . $site['source'];
            continue;
        }
        foreach ($items as $item) {
            $scrapeItems++;
            $row = build_article_row($item, $site['source'], $cutoffTs, $skippedStale, $skippedNoDate);
            if ($row !== null) $collected[] = $row;
        }
    }

    // ---- 3) persist ------------------------------------------------------
    $purged   = db_purge_older_than($cutoffTs);
    $inserted = db_upsert_articles($collected);
    $finished = date('c');
    db_set_run_meta($started, $finished, $failed, $inserted, count($feeds) + count($sites));

    return [
        'inserted'        => $inserted,
        'feeds_total'     => count($feeds),
        'sites_total'     => count($sites),
        'feeds_failed'    => count($failed),
        'failed'          => $failed,
        'rss_items'       => $rssItems,
        'scrape_items'    => $scrapeItems,
        'skipped_stale'   => $skippedStale,
        'skipped_no_date' => $skippedNoDate,
        'purged_old'      => $purged,
        'cutoff_days'     => $maxAgeDays,
        'started_at'      => $started,
        'finished_at'     => $finished,
    ];
}

/**
 * Trasforma un item RSS/scraper in record compatibile con db_upsert_articles.
 * Applica match team, classifica categoria, controlla freschezza.
 * Ritorna null se va scartato; aggiorna i contatori per riferimento.
 */
function build_article_row(array $item, string $source, int $cutoffTs, int &$skippedStale, int &$skippedNoDate): ?array {
    $description = $item['description'] ?? '';
    $haystack    = ($item['title'] ?? '') . ' ' . rss_clean_html((string)$description);
    $teamId      = match_team($haystack);
    if ($teamId === null) return null;

    $publishedIso = !empty($item['pubDate']) ? rss_parse_date((string)$item['pubDate']) : null;
    if ($publishedIso === null) { $skippedNoDate++; return null; }

    $publishedTs = strtotime($publishedIso);
    if ($publishedTs === false || $publishedTs < $cutoffTs) { $skippedStale++; return null; }
    if ($publishedTs > time() + 86400) { $skippedStale++; return null; } // date future implausibili

    $summary = substr(rss_clean_html((string)$description), 0, 320);
    return [
        'team_id'      => $teamId,
        'title'        => $item['title'],
        'summary'      => $summary,
        'link'         => $item['link'],
        'source'       => $source,
        'category'     => classify_category($item['title'], $summary),
        'image_url'    => $item['image'] ?? null,
        'published_at' => $publishedIso,
    ];
}
