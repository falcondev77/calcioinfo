import { TEAMS } from '../data/teams';
import type { Article } from '../services/news';

type Props = {
  loading: boolean;
  articles: Article[];
  totalAll: number;
};

export function ArticleList({ loading, articles, totalAll }: Props) {
  if (loading && articles.length === 0) {
    return (
      <div className="grid">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="card card--skeleton">
            <div className="skel skel--img" />
            <div className="skel skel--line skel--w70" />
            <div className="skel skel--line skel--w90" />
            <div className="skel skel--line skel--w50" />
          </div>
        ))}
      </div>
    );
  }
  if (!loading && articles.length === 0) {
    return (
      <div className="empty">
        <h3>Nessuna notizia trovata</h3>
        <p>
          {totalAll === 0
            ? 'Non siamo riusciti a recuperare articoli dalle fonti. Riprova tra poco.'
            : 'Prova a cambiare squadra, categoria o testo di ricerca.'}
        </p>
      </div>
    );
  }
  return (
    <div className="grid">
      {articles.map(a => (
        <ArticleCard key={a.id} article={a} />
      ))}
    </div>
  );
}

function ArticleCard({ article }: { article: Article }) {
  const team = TEAMS.find(t => t.id === article.teamId);
  const date = article.publishedAt;
  const dateStr = date.toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });
  const timeStr = date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
  const relative = relativeTime(date);
  return (
    <a
      href={article.link}
      target="_blank"
      rel="noopener noreferrer"
      className="card"
      style={team ? { ['--team-primary' as string]: team.primary } : undefined}
    >
      {article.imageUrl ? (
        <div className="card__img">
          <img src={article.imageUrl} alt="" loading="lazy" onError={hideOnError} />
          <span className={`badge badge--${article.category}`}>{categoryLabel(article.category)}</span>
        </div>
      ) : (
        <div className="card__img card__img--placeholder">
          <span className={`badge badge--${article.category}`}>{categoryLabel(article.category)}</span>
        </div>
      )}
      <div className="card__body">
        {team && (
          <div className="card__team" style={{ ['--team-primary' as string]: team.primary }}>
            <span className="card__team-dot" />
            <span>{team.name}</span>
          </div>
        )}
        <h3 className="card__title">{article.title}</h3>
        {article.summary && <p className="card__summary">{article.summary}</p>}
        <div className="card__meta">
          <span className="card__source">{article.source}</span>
          <span className="card__sep">&bull;</span>
          <span title={`${dateStr} - ${timeStr}`}>{relative}</span>
        </div>
        <div className="card__time">
          <ClockIcon /> {dateStr} &middot; {timeStr}
        </div>
      </div>
    </a>
  );
}

function hideOnError(e: React.SyntheticEvent<HTMLImageElement>) {
  (e.currentTarget.parentElement as HTMLElement).classList.add('card__img--placeholder');
  e.currentTarget.style.display = 'none';
}

function categoryLabel(c: Article['category']): string {
  switch (c) {
    case 'calciomercato': return 'Calciomercato';
    case 'allenatore':    return 'Allenatore';
    case 'conferenza':    return 'Conferenza';
    case 'formazione':    return 'Formazione';
    default:              return 'News';
  }
}

function relativeTime(d: Date): string {
  const diffMs = Date.now() - d.getTime();
  const m = Math.floor(diffMs / 60000);
  if (m < 1) return 'adesso';
  if (m < 60) return `${m} min fa`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h} or${h === 1 ? 'a' : 'e'} fa`;
  const days = Math.floor(h / 24);
  if (days < 7) return `${days} giorn${days === 1 ? 'o' : 'i'} fa`;
  return d.toLocaleDateString('it-IT', { day: '2-digit', month: 'short' });
}

function ClockIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <polyline points="12 7 12 12 15 14" />
    </svg>
  );
}
