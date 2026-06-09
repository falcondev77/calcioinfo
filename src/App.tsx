import { useEffect, useMemo, useRef, useState } from 'react';
import { TEAMS } from './data/teams';
import { fetchAllNews, type Article, type Category } from './services/news';
import { Header } from './components/Header';
import { TeamFilter } from './components/TeamFilter';
import { CategoryFilter } from './components/CategoryFilter';
import { ArticleList } from './components/ArticleList';
import { Hero } from './components/Hero';
import { Footer } from './components/Footer';

export function App() {
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [progress, setProgress] = useState({ done: 0, total: 0, label: '' });
  const [failed, setFailed] = useState<string[]>([]);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [selectedTeamId, setSelectedTeamId] = useState<number | 'all'>('all');
  const [selectedCategory, setSelectedCategory] = useState<Category | 'all'>('all');
  const [search, setSearch] = useState('');

  const abortRef = useRef<AbortController | null>(null);

  async function load() {
    abortRef.current?.abort();
    const ctrl = new AbortController();
    abortRef.current = ctrl;
    setLoading(true);
    setError(null);
    setProgress({ done: 0, total: 0, label: '' });
    try {
      const { articles, failedFeeds } = await fetchAllNews(
        (done, total, source) => setProgress({ done, total, label: source }),
        ctrl.signal
      );
      if (ctrl.signal.aborted) return;
      setArticles(articles);
      setFailed(failedFeeds);
      setLastUpdated(new Date());
      if (articles.length === 0) {
        setError('Nessuna notizia ricevuta. Riprova tra qualche istante.');
      }
    } catch (e) {
      if (!ctrl.signal.aborted) {
        setError((e as Error).message || 'Errore durante il caricamento.');
      }
    } finally {
      if (!ctrl.signal.aborted) setLoading(false);
    }
  }

  useEffect(() => {
    load();
    return () => abortRef.current?.abort();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    return articles.filter(a => {
      if (selectedTeamId !== 'all' && a.teamId !== selectedTeamId) return false;
      if (selectedCategory !== 'all' && a.category !== selectedCategory) return false;
      if (q && !(a.title.toLowerCase().includes(q) || a.summary.toLowerCase().includes(q))) return false;
      return true;
    });
  }, [articles, selectedTeamId, selectedCategory, search]);

  const counts = useMemo(() => {
    const byTeam: Record<number, number> = {};
    for (const a of articles) {
      if (a.teamId == null) continue;
      byTeam[a.teamId] = (byTeam[a.teamId] || 0) + 1;
    }
    return { total: articles.length, byTeam };
  }, [articles]);

  return (
    <div className="app">
      <Header
        loading={loading}
        progress={progress}
        lastUpdated={lastUpdated}
        onRefresh={load}
      />
      <Hero totalArticles={counts.total} totalTeams={TEAMS.length} />
      <main className="container">
        <section className="filters">
          <div className="search-wrap">
            <SearchIcon />
            <input
              className="search"
              placeholder="Cerca per giocatore, allenatore, parola chiave..."
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
            {search && (
              <button className="clear-search" onClick={() => setSearch('')} aria-label="Pulisci">
                &times;
              </button>
            )}
          </div>
          <CategoryFilter selected={selectedCategory} onChange={setSelectedCategory} />
        </section>
        <TeamFilter
          teams={TEAMS}
          selected={selectedTeamId}
          counts={counts.byTeam}
          onChange={setSelectedTeamId}
        />
        {error && <div className="banner banner--error">{error}</div>}
        {failed.length > 0 && !error && (
          <div className="banner banner--warn">
            Alcune fonti non sono raggiungibili in questo momento: {failed.slice(0, 4).join(', ')}
            {failed.length > 4 ? `, +${failed.length - 4}` : ''}.
          </div>
        )}
        <ArticleList
          loading={loading}
          articles={filtered}
          totalAll={articles.length}
        />
      </main>
      <Footer />
    </div>
  );
}

function SearchIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="11" cy="11" r="7"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
  );
}
