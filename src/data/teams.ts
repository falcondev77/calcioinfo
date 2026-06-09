export type Team = {
  id: number;
  name: string;
  slug: string;
  city: string;
  primary: string;
  secondary: string;
  aliases: string[];
};

export const TEAMS: Team[] = [
  { id: 1,  name: 'Milan',      slug: 'milan',      city: 'Milano',    primary: '#FB090B', secondary: '#000000', aliases: ['milan', 'rossoneri', 'diavolo', 'ac milan'] },
  { id: 2,  name: 'Monza',      slug: 'monza',      city: 'Monza',     primary: '#E2001A', secondary: '#FFFFFF', aliases: ['monza', 'brianzoli'] },
  { id: 3,  name: 'Fiorentina', slug: 'fiorentina', city: 'Firenze',   primary: '#592C82', secondary: '#FFFFFF', aliases: ['fiorentina', 'viola', 'gigliati'] },
  { id: 4,  name: 'Roma',       slug: 'roma',       city: 'Roma',      primary: '#8E1F2F', secondary: '#F0BC42', aliases: ['roma', 'giallorossi', 'as roma'] },
  { id: 5,  name: 'Atalanta',   slug: 'atalanta',   city: 'Bergamo',   primary: '#1E71B8', secondary: '#000000', aliases: ['atalanta', 'dea', 'bergamaschi'] },
  { id: 6,  name: 'Bologna',    slug: 'bologna',    city: 'Bologna',   primary: '#A8132F', secondary: '#0E2240', aliases: ['bologna', 'felsinei'] },
  { id: 7,  name: 'Cagliari',   slug: 'cagliari',   city: 'Cagliari',  primary: '#A50026', secondary: '#0066B3', aliases: ['cagliari', 'isolani', 'sardi'] },
  { id: 8,  name: 'Como',       slug: 'como',       city: 'Como',      primary: '#0066B3', secondary: '#FFFFFF', aliases: ['como', 'lariani'] },
  { id: 9,  name: 'Frosinone',  slug: 'frosinone',  city: 'Frosinone', primary: '#FFD700', secondary: '#0033A0', aliases: ['frosinone', 'canarini', 'ciociari'] },
  { id: 10, name: 'Genoa',      slug: 'genoa',      city: 'Genova',    primary: '#C8102E', secondary: '#002F6C', aliases: ['genoa', 'grifone'] },
  { id: 11, name: 'Inter',      slug: 'inter',      city: 'Milano',    primary: '#0033A0', secondary: '#000000', aliases: ['inter', 'nerazzurri', 'internazionale', 'biscione'] },
  { id: 12, name: 'Juventus',   slug: 'juventus',   city: 'Torino',    primary: '#000000', secondary: '#FFFFFF', aliases: ['juventus', 'juve', 'bianconeri', 'vecchia signora'] },
  { id: 13, name: 'Lazio',      slug: 'lazio',      city: 'Roma',      primary: '#87CEEB', secondary: '#0E2240', aliases: ['lazio', 'biancocelesti', 'aquile'] },
  { id: 14, name: 'Parma',      slug: 'parma',      city: 'Parma',     primary: '#FFD700', secondary: '#0033A0', aliases: ['parma', 'ducali', 'crociati'] },
  { id: 15, name: 'Sassuolo',   slug: 'sassuolo',   city: 'Sassuolo',  primary: '#00A859', secondary: '#000000', aliases: ['sassuolo', 'neroverdi'] },
  { id: 16, name: 'Napoli',     slug: 'napoli',     city: 'Napoli',    primary: '#0073CE', secondary: '#FFFFFF', aliases: ['napoli', 'partenopei', 'azzurri napoli'] },
  { id: 17, name: 'Torino',     slug: 'torino',     city: 'Torino',    primary: '#8B1A1A', secondary: '#FFFFFF', aliases: ['torino', 'toro', 'granata'] },
  { id: 18, name: 'Udinese',    slug: 'udinese',    city: 'Udine',     primary: '#000000', secondary: '#FFFFFF', aliases: ['udinese', 'friulani', 'zebrette'] },
  { id: 19, name: 'Lecce',      slug: 'lecce',      city: 'Lecce',     primary: '#FFD700', secondary: '#A50026', aliases: ['lecce', 'salentini'] },
  { id: 20, name: 'Venezia',    slug: 'venezia',    city: 'Venezia',   primary: '#FF6600', secondary: '#008000', aliases: ['venezia', 'arancioneroverdi', 'lagunari'] }
];
