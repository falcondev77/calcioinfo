<?php
declare(strict_types=1);

require_once __DIR__ . '/rss.php';
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
    $failed       = [];
    $collected    = [];
    $skippedStale = 0;
    $skippedNoDate= 0;

    foreach ($feeds as $feed) {
        $body = rss_http_get($feed['url']);
        if ($body === null) {
            $failed[] = $feed['source'];
            continue;
        }
        $items = rss_parse($body);
        if ($items === []) {
            $failed[] = $feed['source'];
            continue;
        }
        foreach ($items as $item) {
            $haystack = $item['title'] . ' ' . rss_clean_html($item['description']);
            $teamId = match_team($haystack);
            if ($teamId === null) continue;

            $publishedIso = rss_parse_date($item['pubDate']);
            if ($publishedIso === null) { $skippedNoDate++; continue; }

            $publishedTs = strtotime($publishedIso);
            if ($publishedTs === false || $publishedTs < $cutoffTs) {
                $skippedStale++;
                continue;
            }

            $summary = substr(rss_clean_html($item['description']), 0, 320);
            $collected[] = [
                'team_id'      => $teamId,
                'title'        => $item['title'],
                'summary'      => $summary,
                'link'         => $item['link'],
                'source'       => $feed['source'],
                'category'     => classify_category($item['title'], $summary),
                'image_url'    => $item['image'],
                'published_at' => $publishedIso,
            ];
        }
    }

    $purged   = db_purge_older_than($cutoffTs);
    $inserted = db_upsert_articles($collected);
    $finished = date('c');
    db_set_run_meta($started, $finished, $failed, $inserted, count($feeds));

    return [
        'inserted'        => $inserted,
        'feeds_total'     => count($feeds),
        'feeds_failed'    => count($failed),
        'failed'          => $failed,
        'skipped_stale'   => $skippedStale,
        'skipped_no_date' => $skippedNoDate,
        'purged_old'      => $purged,
        'cutoff_days'     => $maxAgeDays,
        'started_at'      => $started,
        'finished_at'     => $finished,
    ];
}
