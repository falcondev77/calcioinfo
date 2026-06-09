<?php
declare(strict_types=1);

/**
 * Parser RSS regex-based (DOMDocument/SimpleXML non disponibili in questo
 * ambiente PHP). Estrae item con title, link, pubDate, description, image.
 */

function rss_http_get(string $url, int $timeout = 12): ?string {
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => $timeout,
            'follow_location' => 1,
            'header'        => implode("\r\n", [
                'User-Agent: SerieANewsBot/1.0 (+https://example.local)',
                'Accept: application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.5',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.6',
            ]),
        ],
        'https' => [
            'method'        => 'GET',
            'timeout'       => $timeout,
            'follow_location' => 1,
            'header'        => implode("\r\n", [
                'User-Agent: SerieANewsBot/1.0 (+https://example.local)',
                'Accept: application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.5',
                'Accept-Language: it-IT,it;q=0.9,en;q=0.6',
            ]),
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') return null;
    return $body;
}

function rss_clean_html(string $html): string {
    $txt = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $txt = preg_replace('/<[^>]+>/', ' ', $txt) ?? '';
    $txt = preg_replace('/\s+/u', ' ', $txt) ?? '';
    return trim($txt);
}

function rss_extract_cdata_or_text(string $segment, string $tag): string {
    // <tag><![CDATA[ ... ]]></tag>
    if (preg_match('#<' . $tag . '\b[^>]*>\s*<!\[CDATA\[(.*?)\]\]>\s*</' . $tag . '>#si', $segment, $m)) {
        return $m[1];
    }
    if (preg_match('#<' . $tag . '\b[^>]*>(.*?)</' . $tag . '>#si', $segment, $m)) {
        return $m[1];
    }
    return '';
}

function rss_extract_image(string $segment): ?string {
    if (preg_match('#<enclosure\b[^>]*url="([^"]+)"[^>]*>#si', $segment, $m)) return $m[1];
    if (preg_match('#<media:content\b[^>]*url="([^"]+)"[^>]*>#si', $segment, $m)) return $m[1];
    if (preg_match('#<media:thumbnail\b[^>]*url="([^"]+)"[^>]*>#si', $segment, $m)) return $m[1];
    $desc = rss_extract_cdata_or_text($segment, 'description');
    if ($desc !== '' && preg_match('#<img[^>]+src="([^"]+)"#si', $desc, $m)) return $m[1];
    if (preg_match('#<image\b[^>]*>\s*<url>(.*?)</url>#si', $segment, $m)) return trim($m[1]);
    return null;
}

/**
 * Ritorna array di item: [title, link, pubDate (string), description, image].
 */
function rss_parse(string $xml): array {
    if ($xml === '') return [];
    $items = [];
    if (preg_match_all('#<item\b[^>]*>(.*?)</item>#si', $xml, $matches)) {
        foreach ($matches[1] as $body) {
            $title       = rss_clean_html(rss_extract_cdata_or_text($body, 'title'));
            $link        = trim(rss_extract_cdata_or_text($body, 'link'));
            $pubDate     = trim(rss_extract_cdata_or_text($body, 'pubDate'));
            if ($pubDate === '') {
                $pubDate = trim(rss_extract_cdata_or_text($body, 'dc:date'));
            }
            $description = rss_extract_cdata_or_text($body, 'description');
            $image       = rss_extract_image($body);
            if ($title === '' || $link === '') continue;
            $items[] = [
                'title'       => $title,
                'link'        => $link,
                'pubDate'     => $pubDate,
                'description' => $description,
                'image'       => $image,
            ];
        }
    } elseif (preg_match_all('#<entry\b[^>]*>(.*?)</entry>#si', $xml, $matches)) {
        // fallback Atom
        foreach ($matches[1] as $body) {
            $title = rss_clean_html(rss_extract_cdata_or_text($body, 'title'));
            $link = '';
            if (preg_match('#<link\b[^>]*href="([^"]+)"#si', $body, $m)) $link = $m[1];
            $pubDate = trim(rss_extract_cdata_or_text($body, 'updated'));
            if ($pubDate === '') $pubDate = trim(rss_extract_cdata_or_text($body, 'published'));
            $description = rss_extract_cdata_or_text($body, 'summary');
            if ($description === '') $description = rss_extract_cdata_or_text($body, 'content');
            $image = rss_extract_image($body);
            if ($title === '' || $link === '') continue;
            $items[] = compact('title','link','pubDate','description','image');
        }
    }
    return $items;
}

function rss_parse_date(string $raw): ?string {
    if ($raw === '') return null;
    $ts = strtotime($raw);
    if ($ts === false) return null;
    return gmdate('Y-m-d\TH:i:s\Z', $ts);
}
