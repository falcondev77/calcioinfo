<?php
declare(strict_types=1);

require_once __DIR__ . '/teams.php';

/**
 * Scraper HTML "best-effort" per testate italiane: estrae link articolo
 * dalle landing page tramite pattern URL, deriva il titolo dal testo
 * dell'<a> (o dallo slug come fallback) e tenta di leggere data/immagine
 * dal contesto vicino. Robusto al cambio layout: se un sito cambia
 * markup vengono raccolti meno articoli, mai un errore fatale.
 */

function scraper_http_get(string $url, int $timeout = 15): ?string {
    $headers = [
        'User-Agent: Mozilla/5.0 (compatible; SerieANewsBot/1.0; +https://example.local)',
        'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.5',
        'Accept-Language: it-IT,it;q=0.9,en;q=0.6',
    ];
    $opts = [
        'http' => ['method' => 'GET', 'timeout' => $timeout, 'follow_location' => 1, 'header' => implode("\r\n", $headers)],
        'https' => ['method' => 'GET', 'timeout' => $timeout, 'follow_location' => 1, 'header' => implode("\r\n", $headers)],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ];
    $body = @file_get_contents($url, false, stream_context_create($opts));
    if ($body === false || $body === '') return null;
    return $body;
}

function scraper_abs_url(string $href, string $base): string {
    if (preg_match('#^https?://#i', $href)) return $href;
    if (str_starts_with($href, '//')) return 'https:' . $href;
    $b = parse_url($base);
    $scheme = $b['scheme'] ?? 'https';
    $host   = $b['host']   ?? '';
    if ($host === '') return $href;
    if (str_starts_with($href, '/')) return $scheme . '://' . $host . $href;
    return $scheme . '://' . $host . '/' . ltrim($href, '/');
}

function scraper_clean_text(string $s): string {
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = preg_replace('/<[^>]+>/', ' ', $s) ?? '';
    $s = preg_replace('/\s+/u', ' ', $s) ?? '';
    return trim($s);
}

/**
 * Cerca una data nel contesto attorno al link: <time datetime>, formati
 * italiani (DD/MM/YYYY, DD mese YYYY) o "X ore fa".
 * Tutti gli anni numerici sono limitati al range 2020-2099 per evitare
 * di catturare contatori (visualizzazioni, like) come anni.
 */
function scraper_extract_date(string $context): ?string {
    if (preg_match('#<time[^>]+datetime=["\']([^"\']+)["\']#i', $context, $m)) {
        $ts = strtotime($m[1]);
        if ($ts !== false) return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }
    if (preg_match('#"datePublished"\s*:\s*"([^"]+)"#i', $context, $m)) {
        $ts = strtotime($m[1]);
        if ($ts !== false) return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }
    if (preg_match('#(\d{1,2})/(\d{1,2})/(20\d{2})(?:\s+(\d{1,2}):(\d{2}))?#', $context, $m)) {
        $h = isset($m[4]) ? (int)$m[4] : 12;
        $i = isset($m[5]) ? (int)$m[5] : 0;
        $ts = mktime($h, $i, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
        if ($ts !== false) return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }
    $months = [
        'gennaio'=>1,'febbraio'=>2,'marzo'=>3,'aprile'=>4,'maggio'=>5,'giugno'=>6,
        'luglio'=>7,'agosto'=>8,'settembre'=>9,'ottobre'=>10,'novembre'=>11,'dicembre'=>12,
        'gen'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'mag'=>5,'giu'=>6,'lug'=>7,'ago'=>8,'set'=>9,'sett'=>9,'ott'=>10,'nov'=>11,'dic'=>12,
    ];
    if (preg_match('#(\d{1,2})\s+([A-Za-zà]+)\s+(20\d{2})#i', $context, $m)) {
        $mon = strtolower($m[2]);
        if (isset($months[$mon])) {
            $ts = mktime(12, 0, 0, $months[$mon], (int)$m[1], (int)$m[3]);
            if ($ts !== false) return gmdate('Y-m-d\TH:i:s\Z', $ts);
        }
    }
    if (preg_match('#(\d+)\s*(min|minut[oi]|ore?)\s+fa#i', $context, $m)) {
        $n = (int)$m[1];
        $unit = strtolower($m[2]);
        $secs = str_starts_with($unit, 'min') ? $n * 60 : $n * 3600;
        return gmdate('Y-m-d\TH:i:s\Z', time() - $secs);
    }
    if (preg_match('#\b(oggi|adesso|poco\s+fa)\b#i', $context)) {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
    if (preg_match('#\bieri\b#i', $context)) {
        return gmdate('Y-m-d\TH:i:s\Z', time() - 86400);
    }
    return null;
}

function scraper_extract_image(string $context, string $base): ?string {
    if (preg_match('#<meta\s+(?:property|name)=["\']og:image["\']\s+content=["\']([^"\']+)#i', $context, $m)) {
        return scraper_abs_url($m[1], $base);
    }
    if (preg_match('#<img[^>]+(?:data-src|data-original|data-lazy-src|src)=["\']([^"\']+)["\']#i', $context, $m)) {
        $u = $m[1];
        if (str_starts_with($u, 'data:')) return null;
        return scraper_abs_url($u, $base);
    }
    return null;
}

function scraper_title_from_slug(string $url): string {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $segments = array_values(array_filter(explode('/', $path)));
    if (!$segments) return '';
    $last = end($segments);
    $last = preg_replace('/\.\w+$/', '', $last);          // .shtml, .html
    $last = preg_replace('/_\d+$/', '', $last);           // _12345
    $last = preg_replace('/-\d+$/', '', $last);           // -12345
    $last = str_replace(['-', '_'], ' ', $last);
    $last = preg_replace('/\s+/', ' ', $last);
    return trim(ucfirst((string)$last));
}

/**
 * Estrae articoli da un HTML usando un pattern di URL articolo.
 */
function scraper_extract_articles(string $html, string $base, string $linkPattern): array {
    $items = [];
    if (!preg_match_all('#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#si', $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $items;
    }
    $seen = [];
    foreach ($matches[1] as $i => $hrefMatch) {
        $href = $hrefMatch[0];
        $absolute = scraper_abs_url($href, $base);
        if (!preg_match($linkPattern, $absolute)) continue;
        $cleanLink = preg_replace('/[#?].*$/', '', $absolute) ?? $absolute;
        if (isset($seen[$cleanLink])) continue;
        $seen[$cleanLink] = true;

        $rawAnchor = $matches[2][$i][0];
        $title = scraper_clean_text($rawAnchor);
        if (strlen($title) < 18) {
            // prova attributi title / aria-label
            if (preg_match('#title=["\']([^"\']{18,})["\']#i', $matches[0][$i][0], $tm)) {
                $title = scraper_clean_text($tm[1]);
            } elseif (preg_match('#aria-label=["\']([^"\']{18,})["\']#i', $matches[0][$i][0], $tm)) {
                $title = scraper_clean_text($tm[1]);
            }
        }
        if (strlen($title) < 18) {
            $title = scraper_title_from_slug($cleanLink);
        }
        if (strlen($title) < 12) continue;

        $offset  = $matches[0][$i][1];
        $context = substr($html, max(0, $offset - 1500), 3000);
        $pubDate = scraper_extract_date($context);
        $image   = scraper_extract_image($context, $base);

        $items[] = [
            'title'       => $title,
            'link'        => $cleanLink,
            'pubDate'     => $pubDate,
            'description' => '',
            'image'       => $image,
        ];
    }
    return $items;
}

/**
 * Profili dei siti da scrapare. Ognuno: source label, URL da fetchare,
 * regex che valida un URL come articolo. Tutte le pagine-squadra di TMW
 * sono incluse per massimizzare la quantita' di notizie.
 */
function scraper_targets(): array {
    $targets = [
        ['source' => 'TuttoMercatoWeb',     'url' => 'https://www.tuttomercatoweb.com/',        'pattern' => '#https?://www\.tuttomercatoweb\.com/[a-z0-9\-]+/[a-z0-9\-]+/\d+#i'],
        ['source' => 'Calciomercato.com',   'url' => 'https://www.calciomercato.com/',          'pattern' => '#https?://www\.calciomercato\.com/news/[^"\' <>]+#i'],
        ['source' => 'Gazzetta',            'url' => 'https://www.gazzetta.it/Calcio/Serie-A/', 'pattern' => '#https?://www\.gazzetta\.it/Calcio/[^"\' <>]+\.shtml#i'],
        ['source' => 'Sky Sport',           'url' => 'https://sport.sky.it/calcio/serie-a',     'pattern' => '#https?://sport\.sky\.it/calcio/[^"\' <>]+#i'],
        ['source' => 'Tuttosport',          'url' => 'https://www.tuttosport.com/news/calcio',  'pattern' => '#https?://www\.tuttosport\.com/news/calcio/[^"\' <>]+#i'],
        ['source' => 'Corriere dello Sport','url' => 'https://www.corrieredellosport.it/news/calcio', 'pattern' => '#https?://www\.corrieredellosport\.it/news/calcio/[^"\' <>]+#i'],
        ['source' => 'Goal.com',            'url' => 'https://www.goal.com/it/serie-a/2kwbbcootiqqgmrzs6o5inle5', 'pattern' => '#https?://www\.goal\.com/it/[a-z0-9\-]+/[a-z0-9\-]+/[a-z0-9]+#i'],
        ['source' => 'FcInterNews',         'url' => 'https://www.fcinternews.it/',             'pattern' => '#https?://www\.fcinternews\.it/[a-z0-9\-]+/[a-z0-9\-]+-\d+\.html#i'],
        ['source' => 'MilanNews',           'url' => 'https://www.milannews.it/',               'pattern' => '#https?://www\.milannews\.it/[a-z0-9\-]+/[a-z0-9\-]+-\d+#i'],
    ];
    foreach (teams_all() as $team) {
        $targets[] = [
            'source'  => 'TMW ' . $team['name'],
            'url'     => 'https://www.tuttomercatoweb.com/' . $team['slug'] . '/',
            'pattern' => '#https?://www\.tuttomercatoweb\.com/' . preg_quote($team['slug'], '#') . '/[a-z0-9\-]+/\d+#i',
        ];
    }
    return $targets;
}
