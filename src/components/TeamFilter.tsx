import type { Team } from '../data/teams';

type Props = {
  teams: Team[];
  selected: number | 'all';
  counts: Record<number, number>;
  onChange: (id: number | 'all') => void;
};

export function TeamFilter({ teams, selected, counts, onChange }: Props) {
  return (
    <div className="team-filter">
      <button
        className={`team-chip team-chip--all ${selected === 'all' ? 'is-active' : ''}`}
        onClick={() => onChange('all')}
      >
        <span className="team-chip__name">Tutte le squadre</span>
      </button>
      {teams.map(team => {
        const count = counts[team.id] || 0;
        const active = selected === team.id;
        return (
          <button
            key={team.id}
            className={`team-chip ${active ? 'is-active' : ''}`}
            onClick={() => onChange(team.id)}
            style={{
              ['--team-primary' as string]: team.primary,
              ['--team-secondary' as string]: team.secondary
            }}
          >
            <span className="team-chip__dot" aria-hidden="true" />
            <span className="team-chip__name">{team.name}</span>
            <span className="team-chip__count">{count}</span>
          </button>
        );
      })}
    </div>
  );
}
