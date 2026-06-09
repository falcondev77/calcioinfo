<?php
declare(strict_types=1);

/**
 * Storage JSON backed che emula la tabella `articles` di database.sql.
 * Tutta la persistenza vive in data/db.json.
 */

const DB_PATH = __DIR__ . '/../data/db.json';

function db_load(): array {
    if (!is_file(DB_PATH)) {
        return ['articles' => [], 'last_run' => null, 'failed_feeds' => []];
    }
    $raw = @file_get_contents(DB_PATH);
    if ($raw === false || $raw === '') {
        return ['articles' => [], 'last_run' => null, 'failed_feeds' => []];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['articles' => [], 'last_run' => null, 'failed_feeds' => []];
    }
    $decoded['articles']     = $decoded['articles']     ?? [];
    $decoded['last_run']     = $decoded['last_run']     ?? null;
    $decoded['failed_feeds'] = $decoded['failed_feeds'] ?? [];
    return $decoded;
}

function db_save(array $state): void {
    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $tmp = DB_PATH . '.tmp';
    file_put_contents($tmp, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    rename($tmp, DB_PATH);
}

/**
 * Inserisce articoli, deduplicando per link (UNIQUE su `articles.link`).
 * Ritorna il numero di nuovi articoli effettivamente aggiunti.
 */
function db_upsert_articles(array $incoming): int {
    $state = db_load();
    $byLink = [];
    foreach ($state['articles'] as $a) {
        $byLink[$a['link']] = true;
    }
    $added = 0;
    foreach ($incoming as $a) {
        $link = $a['link'] ?? '';
        if ($link === '' || isset($byLink[$link])) continue;
        $a['id']         = $a['id']         ?? bin2hex(random_bytes(8));
        $a['fetched_at'] = $a['fetched_at'] ?? date('c');
        $state['articles'][] = $a;
        $byLink[$link] = true;
        $added++;
    }
    // ordina per published_at desc e mantiene solo gli ultimi 1500 per non far crescere il file
    usort($state['articles'], fn($a, $b) => strcmp((string)$b['published_at'], (string)$a['published_at']));
    if (count($state['articles']) > 1500) {
        $state['articles'] = array_slice($state['articles'], 0, 1500);
    }
    db_save($state);
    return $added;
}

function db_set_run_meta(string $started_at, string $finished_at, array $failed_feeds, int $inserted, int $totalFeeds): void {
    $state = db_load();
    $state['last_run'] = [
        'started_at'   => $started_at,
        'finished_at'  => $finished_at,
        'inserted'     => $inserted,
        'feeds_total'  => $totalFeeds,
        'feeds_failed' => count($failed_feeds),
    ];
    $state['failed_feeds'] = $failed_feeds;
    db_save($state);
}

/**
 * Filtra gli articoli secondo team_id, category, query testuale e
 * (opzionale) cutoff temporale: solo articoli con published_at >= cutoff.
 */
function db_query_articles(?int $team_id, ?string $category, string $q, ?int $minTs = null): array {
    $state = db_load();
    $q = trim(strtolower($q));
    $out = [];
    foreach ($state['articles'] as $a) {
        if ($team_id !== null && (int)$a['team_id'] !== $team_id) continue;
        if ($category !== null && $category !== 'all' && $a['category'] !== $category) continue;
        if ($minTs !== null) {
            $ts = strtotime((string)($a['published_at'] ?? ''));
            if ($ts === false || $ts < $minTs) continue;
        }
        if ($q !== '') {
            $blob = strtolower(($a['title'] ?? '') . ' ' . ($a['summary'] ?? ''));
            if (!str_contains($blob, $q)) continue;
        }
        $out[] = $a;
    }
    return $out;
}

function db_counts_by_team(?int $minTs = null): array {
    $state = db_load();
    $counts = [];
    foreach ($state['articles'] as $a) {
        if ($minTs !== null) {
            $ts = strtotime((string)($a['published_at'] ?? ''));
            if ($ts === false || $ts < $minTs) continue;
        }
        $tid = (int)$a['team_id'];
        $counts[$tid] = ($counts[$tid] ?? 0) + 1;
    }
    return $counts;
}

function db_meta(): array {
    $state = db_load();
    return [
        'last_run'     => $state['last_run']     ?? null,
        'failed_feeds' => $state['failed_feeds'] ?? [],
        'total'        => count($state['articles']),
    ];
}

/**
 * Rimuove articoli con published_at piu vecchio del timestamp passato.
 * Ritorna il numero di articoli rimossi.
 */
function db_purge_older_than(int $cutoffTs): int {
    $state = db_load();
    $kept = [];
    $removed = 0;
    foreach ($state['articles'] as $a) {
        $ts = strtotime((string)($a['published_at'] ?? ''));
        if ($ts === false || $ts < $cutoffTs) {
            $removed++;
            continue;
        }
        $kept[] = $a;
    }
    if ($removed > 0) {
        $state['articles'] = $kept;
        db_save($state);
    }
    return $removed;
}
