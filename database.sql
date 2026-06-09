-- =====================================================================
-- Serie A News Aggregator - Schema locale (SQLite/PostgreSQL compatibile)
-- =====================================================================
-- Questo file definisce lo schema usato dal sito per archiviare
-- squadre e notizie raccolte dai feed RSS italiani.
-- Non viene eseguito automaticamente: e' qui per uso locale / backup.
-- =====================================================================

DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS teams;

-- ---------------------------------------------------------------------
-- TEAMS
-- ---------------------------------------------------------------------
CREATE TABLE teams (
  id           INTEGER PRIMARY KEY,
  name         TEXT NOT NULL UNIQUE,
  slug         TEXT NOT NULL UNIQUE,
  city         TEXT NOT NULL,
  primary_hex  TEXT NOT NULL,
  secondary_hex TEXT NOT NULL,
  -- alias usati per il match testuale nei titoli/sommari
  aliases      TEXT NOT NULL
);

-- ---------------------------------------------------------------------
-- ARTICLES
-- ---------------------------------------------------------------------
CREATE TABLE articles (
  id            INTEGER PRIMARY KEY,
  team_id       INTEGER NOT NULL REFERENCES teams(id),
  title         TEXT NOT NULL,
  summary       TEXT,
  link          TEXT NOT NULL UNIQUE,
  source        TEXT NOT NULL,        -- es: "Gazzetta", "TMW", "Calciomercato.com"
  category      TEXT NOT NULL,        -- calciomercato | allenatore | conferenza | formazione | generale
  image_url     TEXT,
  published_at  TIMESTAMP NOT NULL,   -- data e ora dell'articolo originale
  fetched_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_articles_team_published ON articles (team_id, published_at DESC);
CREATE INDEX idx_articles_category       ON articles (category, published_at DESC);
CREATE INDEX idx_articles_published      ON articles (published_at DESC);

-- ---------------------------------------------------------------------
-- SEED: 20 squadre del prossimo campionato di Serie A
-- ---------------------------------------------------------------------
INSERT INTO teams (id, name, slug, city, primary_hex, secondary_hex, aliases) VALUES
  (1,  'Milan',     'milan',     'Milano',  '#FB090B', '#000000', 'milan,rossoneri,diavolo,ac milan'),
  (2,  'Monza',     'monza',     'Monza',   '#E2001A', '#FFFFFF', 'monza,brianzoli,biancorossi'),
  (3,  'Fiorentina','fiorentina','Firenze', '#592C82', '#FFFFFF', 'fiorentina,viola,gigliati'),
  (4,  'Roma',      'roma',      'Roma',    '#8E1F2F', '#F0BC42', 'roma,giallorossi,as roma,lupi'),
  (5,  'Atalanta',  'atalanta',  'Bergamo', '#1E71B8', '#000000', 'atalanta,dea,nerazzurri bergamo,bergamaschi'),
  (6,  'Bologna',   'bologna',   'Bologna', '#A8132F', '#0E2240', 'bologna,rossoblu,felsinei'),
  (7,  'Cagliari',  'cagliari',  'Cagliari','#A50026', '#0066B3', 'cagliari,rossoblu sardi,sardi,isolani'),
  (8,  'Como',      'como',      'Como',    '#0066B3', '#FFFFFF', 'como,lariani,azzurri como'),
  (9,  'Frosinone', 'frosinone', 'Frosinone','#FFD700','#0033A0', 'frosinone,canarini,giallazzurri,ciociari'),
  (10, 'Genoa',     'genoa',     'Genova',  '#C8102E', '#002F6C', 'genoa,grifone,rossoblu liguri'),
  (11, 'Inter',     'inter',     'Milano',  '#0033A0', '#000000', 'inter,nerazzurri,internazionale,biscione'),
  (12, 'Juventus',  'juventus',  'Torino',  '#000000', '#FFFFFF', 'juventus,juve,bianconeri,vecchia signora'),
  (13, 'Lazio',     'lazio',     'Roma',    '#87CEEB', '#FFFFFF', 'lazio,biancocelesti,aquile,laziali'),
  (14, 'Parma',     'parma',     'Parma',   '#FFD700', '#0033A0', 'parma,ducali,gialloblu parma,crociati'),
  (15, 'Sassuolo',  'sassuolo',  'Sassuolo','#00A859', '#000000', 'sassuolo,neroverdi'),
  (16, 'Napoli',    'napoli',    'Napoli',  '#0073CE', '#FFFFFF', 'napoli,azzurri,partenopei'),
  (17, 'Torino',    'torino',    'Torino',  '#8B1A1A', '#FFFFFF', 'torino,toro,granata'),
  (18, 'Udinese',   'udinese',   'Udine',   '#000000', '#FFFFFF', 'udinese,bianconeri friulani,friulani,zebrette'),
  (19, 'Lecce',     'lecce',     'Lecce',   '#FFD700', '#A50026', 'lecce,giallorossi salentini,salentini'),
  (20, 'Venezia',   'venezia',   'Venezia', '#FF6600', '#008000', 'venezia,arancioneroverdi,lagunari');
