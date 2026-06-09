type Props = {
  totalArticles: number;
  totalTeams: number;
};

export function Hero({ totalArticles, totalTeams }: Props) {
  const today = new Date().toLocaleDateString('it-IT', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
  return (
    <section className="hero">
      <div className="container hero__inner">
        <div className="hero__copy">
          <span className="hero__eyebrow">{today}</span>
          <h2 className="hero__title">
            Tutte le notizie di Serie A,
            <span className="hero__title-accent"> in tempo reale.</span>
          </h2>
          <p className="hero__sub">
            Aggreghiamo le fonti italiane piu autorevoli &mdash; Gazzetta, Sky Sport,
            Tuttosport, Corriere dello Sport, TMW e Calciomercato.com &mdash; per offrirti
            calciomercato, conferenze degli allenatori, formazioni e cambi tecnici delle 20
            squadre del prossimo campionato.
          </p>
        </div>
        <div className="hero__stats">
          <Stat value={totalArticles} label="Notizie raccolte" />
          <Stat value={totalTeams} label="Squadre seguite" />
          <Stat value={6} label="Fonti principali" />
        </div>
      </div>
    </section>
  );
}

function Stat({ value, label }: { value: number; label: string }) {
  return (
    <div className="stat">
      <div className="stat__value">{value}</div>
      <div className="stat__label">{label}</div>
    </div>
  );
}
