import type { Category } from '../services/news';

type Item = { value: Category | 'all'; label: string };

const ITEMS: Item[] = [
  { value: 'all',           label: 'Tutte' },
  { value: 'calciomercato', label: 'Calciomercato' },
  { value: 'allenatore',    label: 'Allenatori' },
  { value: 'conferenza',    label: 'Conferenze' },
  { value: 'formazione',    label: 'Formazioni' },
  { value: 'generale',      label: 'Generale' }
];

type Props = {
  selected: Category | 'all';
  onChange: (c: Category | 'all') => void;
};

export function CategoryFilter({ selected, onChange }: Props) {
  return (
    <div className="cat-filter" role="tablist">
      {ITEMS.map(item => (
        <button
          key={item.value}
          role="tab"
          aria-selected={selected === item.value}
          className={`cat-tab ${selected === item.value ? 'is-active' : ''}`}
          onClick={() => onChange(item.value)}
        >
          {item.label}
        </button>
      ))}
    </div>
  );
}
