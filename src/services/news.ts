export type Category = 'calciomercato' | 'allenatore' | 'conferenza' | 'formazione' | 'generale';

export type Article = {
  id: string;
  teamId: number | null;
  teamName: string | null;
  title: string;
  summary: string;
  link: string;
  source: string;
  category: Category;
  imageUrl: string | null;
  publishedAt: Date;
};

type Feed = {
  source: string;
  url: string;
};

// Feed RSS italiani principali. Coprono calciomercato, news squadre, conferenze.
// I feed di TMW per-squadra sono i piu specifici e generano notizie targettizzate.
const TMW_TEAM_FEEDS: { slug: string; tmwId: string }[] = [
  { slug: 'milan',      tmwId: 'milan' },
  { slug: 'monza',      tmwId: 'monza' },
  { slug: 'fiorentina', tmwId: 'fiorentina' },
  { slug: 'roma',       tmwId: 'roma' },
  { slug: 'atalanta',   tmwId: 'atalanta' },
  { slug: 'bologna',    tmwId: 'bologna' },
  { slug: 'cagliari',   tmwId: 'cagliari' },
  { slug: 'como',       tmwId: 'como' },
  { slug: 'frosinone',  tmwId: 'frosinone' },
  { slug: 'genoa',      tmwId: 'genoa' },
  { slug: 'inter',      tmwId: 'inter' },
  { slug: 'juventus',   tmwId: 'juventus' },
  { slug: 'lazio',      tmwId: 'lazio' },
  { slug: 'parma',      tmwId: 'parma' },
  { slug: 'sassuolo',   tmwId: 'sassuolo' },
  { slug: 'napoli',     tmwId: 'napoli' },
  { slug: 'torino',     tmwId: 'torino' },
  { slug: 'udinese',    tmwId: 'udinese' },
  { slug: 'lecce',      tmwId: 'lecce' },
  { slug: 'venezia',    tmwId: 'venezia' }
];

export const FEEDS: Feed[] = [
  { source: 'TuttoMercatoWeb',   url: 'https://www.tuttomercatoweb.com/rss' },
  { source: 'Calciomercato.com', url: 'https://www.calciomercato.com/rss' },
  { source: 'Gazzetta',          url: 'https://www.gazzetta.it/rss/calcio.xml' },
  { source: 'Sky Sport',         url: 'https://sport.sky.it/rss/calcio.xml' },
  { source: 'Corriere dello Sport', url: 'https://www.corrieredellosport.it/rss/calcio.xml' },
  { source: 'Tuttosport',        url: 'https://www.tuttosport.com/rss/calcio.xml' },
  ...TMW_TEAM_FEEDS.map(t => ({
    source: `TMW ${t.slug.charAt(0).toUpperCase() + t.slug.slice(1)}`,
    url: `https://www.tuttomercatoweb.com/rss/${t.tmwId}`
  }))
];

// allorigins.win proxy per superare CORS sul lato client
function proxyUrl(url: string): string {
  return `https://api.allorigins.win/raw?url=${encodeURIComponent(url)}`;
}

function pickText(parent: Element, tag: string): string {
  const el = parent.getElementsByTagName(tag)[0];
  return el?.textContent?.trim() ?? '';
}

function stripHtml(html: string): string {
  const div = document.createElement('div');
  div.innerHTML = html;
  return (div.textContent || '').replace(/\s+/g, ' ').trim();
}

function extractImage(item: Element, descriptionHtml: string): string | null {
  const enclosure = item.getElementsByTagName('enclosure')[0];
  const enclosureUrl = enclosure?.getAttribute('url');
  if (enclosureUrl) return enclosureUrl;
  const mediaContent = item.getElementsByTagName('media:content')[0];
  const mediaUrl = mediaContent?.getAttribute('url');
  if (mediaUrl) return mediaUrl;
  const match = descriptionHtml.match(/<img[^>]+src="([^"]+)"/i);
  if (match) return match[1];
  return null;
}

const TITLE_KEYWORDS: Record<Category, RegExp> = {
  conferenza:    /\b(conferenza|conferenza stampa|presentazione)\b/i,
  formazione:    /\b(formazione|probabili|modulo|tattic|schema|undici|titolari)\b/i,
  allenatore:    /\b(allenatore|tecnico|panchina|esonero|esonerato|nuovo mister|sostituto|dimissioni)\b/i,
  calciomercato: /\b(mercato|acquist|cession|offert|trattativ|firma|firmato|rinnovo|prestit|riscatt|cessi|colpo|ufficiale|annuncio|addio)\b/i,
  generale:      /.*/
};

function classify(title: string, summary: string): Category {
  const text = `${title} ${summary}`;
  if (TITLE_KEYWORDS.conferenza.test(text)) return 'conferenza';
  if (TITLE_KEYWORDS.formazione.test(text)) return 'formazione';
  if (TITLE_KEYWORDS.allenatore.test(text)) return 'allenatore';
  if (TITLE_KEYWORDS.calciomercato.test(text)) return 'calciomercato';
  return 'generale';
}

import { TEAMS } from '../data/teams';

function matchTeam(text: string): { id: number; name: string } | null {
  const lower = text.toLowerCase();
  for (const team of TEAMS) {
    for (const alias of team.aliases) {
      const re = new RegExp(`\\b${alias.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&')}\\b`, 'i');
      if (re.test(lower)) {
        return { id: team.id, name: team.name };
      }
    }
  }
  return null;
}

async function fetchFeed(feed: Feed, signal?: AbortSignal): Promise<Article[]> {
  const res = await fetch(proxyUrl(feed.url), { signal });
  if (!res.ok) throw new Error(`feed ${feed.source} HTTP ${res.status}`);
  const xml = await res.text();
  const doc = new DOMParser().parseFromString(xml, 'application/xml');
  if (doc.getElementsByTagName('parsererror').length > 0) {
    throw new Error(`feed ${feed.source} non e' XML valido`);
  }
  const items = Array.from(doc.getElementsByTagName('item'));
  const out: Article[] = [];
  for (const item of items) {
    const title = pickText(item, 'title');
    const link = pickText(item, 'link');
    if (!title || !link) continue;
    const pubDate = pickText(item, 'pubDate');
    const descriptionHtml = pickText(item, 'description');
    const summary = stripHtml(descriptionHtml).slice(0, 300);
    const matched = matchTeam(`${title} ${summary}`);
    if (!matched) continue;
    const publishedAt = pubDate ? new Date(pubDate) : new Date();
    if (Number.isNaN(publishedAt.getTime())) continue;
    out.push({
      id: link,
      teamId: matched.id,
      teamName: matched.name,
      title,
      summary,
      link,
      source: feed.source,
      category: classify(title, summary),
      imageUrl: extractImage(item, descriptionHtml),
      publishedAt
    });
  }
  return out;
}

export async function fetchAllNews(
  onProgress?: (done: number, total: number, source: string) => void,
  signal?: AbortSignal
): Promise<{ articles: Article[]; failedFeeds: string[] }> {
  const failed: string[] = [];
  const all: Article[] = [];
  const total = FEEDS.length;
  let done = 0;
  const results = await Promise.allSettled(FEEDS.map(async (feed) => {
    try {
      const items = await fetchFeed(feed, signal);
      done += 1;
      onProgress?.(done, total, feed.source);
      return items;
    } catch (e) {
      done += 1;
      onProgress?.(done, total, feed.source);
      failed.push(feed.source);
      throw e;
    }
  }));
  for (const r of results) {
    if (r.status === 'fulfilled') all.push(...r.value);
  }
  // dedup per link
  const seen = new Set<string>();
  const unique: Article[] = [];
  for (const a of all) {
    if (seen.has(a.link)) continue;
    seen.add(a.link);
    unique.push(a);
  }
  unique.sort((a, b) => b.publishedAt.getTime() - a.publishedAt.getTime());
  return { articles: unique, failedFeeds: failed };
}
