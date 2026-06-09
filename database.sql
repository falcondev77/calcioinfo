-- =====================================================================
-- Serie A News Aggregator - Schema locale (compatibile SQLite/MySQL/Postgres)
-- =====================================================================
-- Questo file contiene lo schema canonico del database.
-- L'applicazione PHP, in assenza di pdo_sqlite nell'ambiente, mantiene i
-- dati in un file JSON (data/db.json) con la stessa forma logica delle
-- tabelle qui sotto. Lo schema serve come fonte di verita' e per importare
-- i dati in un vero database con un comando come:
--   sqlite3 database.db < database.sql
-- =====================================================================

DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS teams;

-- ---------------------------------------------------------------------
-- TEAMS - 20 squadre del prossimo campionato di Serie A
-- ---------------------------------------------------------------------
CREATE TABLE teams (
  id            INTEGER PRIMARY KEY,
  name          TEXT NOT NULL UNIQUE,
  slug          TEXT NOT NULL UNIQUE,
  city          TEXT NOT NULL,
  primary_hex   TEXT NOT NULL,
  secondary_hex TEXT NOT NULL,
  aliases       TEXT NOT NULL  -- alias separati da virgola
);

-- ---------------------------------------------------------------------
-- ARTICLES - notizie aggregate dai feed RSS italiani
-- ---------------------------------------------------------------------
CREATE TABLE articles (
  id            INTEGER PRIMARY KEY,
  team_id       INTEGER NOT NULL REFERENCES teams(id),
  title         TEXT NOT NULL,
  summary       TEXT,
  link          TEXT NOT NULL UNIQUE,
  source        TEXT NOT NULL,
  category      TEXT NOT NULL,        -- calciomercato | allenatore | conferenza | formazione | generale
  image_url     TEXT,
  published_at  TIMESTAMP NOT NULL,
  fetched_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_articles_team_published ON articles (team_id, published_at DESC);
CREATE INDEX idx_articles_category       ON articles (category, published_at DESC);
CREATE INDEX idx_articles_published      ON articles (published_at DESC);

-- ---------------------------------------------------------------------
-- SEED: squadre Serie A
-- ---------------------------------------------------------------------
INSERT INTO teams (id, name, slug, city, primary_hex, secondary_hex, aliases) VALUES
  (1,  'Milan',     'milan',     'Milano',  '#FB090B', '#000000', 'milan,rossoneri,diavolo,ac milan'),
  (2,  'Monza',     'monza',     'Monza',   '#E2001A', '#FFFFFF', 'monza,brianzoli,biancorossi'),
  (3,  'Fiorentina','fiorentina','Firenze', '#592C82', '#FFFFFF', 'fiorentina,viola,gigliati'),
  (4,  'Roma',      'roma',      'Roma',    '#8E1F2F', '#F0BC42', 'roma,giallorossi,as roma,lupi'),
  (5,  'Atalanta',  'atalanta',  'Bergamo', '#1E71B8', '#000000', 'atalanta,dea,bergamaschi'),
  (6,  'Bologna',   'bologna',   'Bologna', '#A8132F', '#0E2240', 'bologna,rossoblu,felsinei'),
  (7,  'Cagliari',  'cagliari',  'Cagliari','#A50026', '#0066B3', 'cagliari,sardi,isolani'),
  (8,  'Como',      'como',      'Como',    '#0066B3', '#FFFFFF', 'como,lariani'),
  (9,  'Frosinone', 'frosinone', 'Frosinone','#FFD700','#0033A0', 'frosinone,canarini,ciociari'),
  (10, 'Genoa',     'genoa',     'Genova',  '#C8102E', '#002F6C', 'genoa,grifone'),
  (11, 'Inter',     'inter',     'Milano',  '#0033A0', '#000000', 'inter,nerazzurri,internazionale,biscione'),
  (12, 'Juventus',  'juventus',  'Torino',  '#000000', '#FFFFFF', 'juventus,juve,bianconeri,vecchia signora'),
  (13, 'Lazio',     'lazio',     'Roma',    '#87CEEB', '#0E2240', 'lazio,biancocelesti,aquile'),
  (14, 'Parma',     'parma',     'Parma',   '#FFD700', '#0033A0', 'parma,ducali,crociati'),
  (15, 'Sassuolo',  'sassuolo',  'Sassuolo','#00A859', '#000000', 'sassuolo,neroverdi'),
  (16, 'Napoli',    'napoli',    'Napoli',  '#0073CE', '#FFFFFF', 'napoli,partenopei,azzurri napoli'),
  (17, 'Torino',    'torino',    'Torino',  '#8B1A1A', '#FFFFFF', 'torino,toro,granata'),
  (18, 'Udinese',   'udinese',   'Udine',   '#000000', '#FFFFFF', 'udinese,friulani,zebrette'),
  (19, 'Lecce',     'lecce',     'Lecce',   '#FFD700', '#A50026', 'lecce,salentini'),
  (20, 'Venezia',   'venezia',   'Venezia', '#FF6600', '#008000', 'venezia,arancioneroverdi,lagunari');
