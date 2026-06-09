type Props = {
  loading: boolean;
  progress: { done: number; total: number; label: string };
  lastUpdated: Date | null;
  onRefresh: () => void;
};

export function Header({ loading, progress, lastUpdated, onRefresh }: Props) {
  const pct = progress.total > 0 ? Math.round((progress.done / progress.total) * 100) : 0;
  return (
    <header className="header">
      <div className="header__inner container">
        <div className="brand">
          <div className="brand__mark" aria-hidden="true">
            <span className="brand__stripe brand__stripe--g" />
            <span className="brand__stripe brand__stripe--w" />
            <span className="brand__stripe brand__stripe--r" />
          </div>
          <div className="brand__text">
            <h1>Serie A News</h1>
            <p>Calciomercato, allenatori, conferenze e formazioni</p>
          </div>
        </div>
        <div className="header__actions">
          <div className="updated">
            {loading ? (
              <span className="updated__live">
                <span className="dot" />
                Aggiornamento in corso {pct}%
                {progress.label ? ` - ${progress.label}` : ''}
              </span>
            ) : lastUpdated ? (
              <span>Aggiornato alle {formatTime(lastUpdated)}</span>
            ) : (
              <span>In attesa...</span>
            )}
          </div>
          <button className="btn btn--primary" onClick={onRefresh} disabled={loading}>
            <RefreshIcon spinning={loading} />
            <span>Aggiorna</span>
          </button>
        </div>
      </div>
      {loading && (
        <div className="header__progress" aria-hidden="true">
          <div className="header__progress-bar" style={{ width: `${pct}%` }} />
        </div>
      )}
    </header>
  );
}

function formatTime(d: Date): string {
  return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

function RefreshIcon({ spinning }: { spinning: boolean }) {
  return (
    <svg
      className={spinning ? 'icon icon--spin' : 'icon'}
      width="16"
      height="16"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <polyline points="23 4 23 10 17 10" />
      <polyline points="1 20 1 14 7 14" />
      <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10" />
      <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14" />
    </svg>
  );
}
