<?php
declare(strict_types=1);

require_once __DIR__ . '/teams.php';

/**
 * Categorizza una notizia in base a keyword nel titolo + sommario.
 */
function classify_category(string $title, string $summary): string {
    $text = strtolower($title . ' ' . $summary);
    if (preg_match('/\b(conferenza|conferenza stampa|presentazione)\b/u', $text))                       return 'conferenza';
    if (preg_match('/\b(formazione|probabili|modulo|tattic|schema|undici|titolari)\b/u', $text))        return 'formazione';
    if (preg_match('/\b(allenatore|tecnico|panchina|esonero|esonerato|nuovo mister|sostituto|dimission)\b/u', $text)) return 'allenatore';
    if (preg_match('/\b(mercato|acquist|cession|offert|trattativ|firma|firmato|rinnovo|prestit|riscatt|colpo|ufficiale|annuncio|addio)\b/u', $text)) return 'calciomercato';
    return 'generale';
}

/**
 * Match alla squadra tramite alias. Ritorna l'id della squadra o null.
 */
function match_team(string $text): ?int {
    $lower = strtolower($text);
    foreach (teams_all() as $team) {
        foreach ($team['aliases'] as $alias) {
            $pat = '/\b' . preg_quote($alias, '/') . '\b/u';
            if (preg_match($pat, $lower)) {
                return $team['id'];
            }
        }
    }
    return null;
}
