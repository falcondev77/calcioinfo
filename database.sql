-- =====================================================================
-- Serie A News Aggregator - Schema locale
-- Compatibile con MySQL/MariaDB, SQLite, PostgreSQL.
-- =====================================================================
-- Note MySQL/MariaDB:
--   - tutti i campi indicizzati o UNIQUE sono VARCHAR di lunghezza
--     compatibile col limite di 3072 byte/key in utf8mb4
--     (es. VARCHAR(500) -> 2000 byte).
--   - i campi liberi non indicizzati (title, summary) restano TEXT.
--
-- L'applicazione PHP, in assenza di pdo_sqlite/pdo_mysql nell'ambiente
-- locale, salva i dati in data/db.json con la stessa forma logica delle
-- tabelle qui sotto. Per importare in un DB reale:
--   sqlite3 database.db < database.sql
--   mysql -u root -p miodb < database.sql
-- =====================================================================

DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS teams;

-- ---------------------------------------------------------------------
-- TEAMS - 20 squadre del prossimo campionato di Serie A
-- ---------------------------------------------------------------------
CREATE TABLE teams (
  id            INT NOT NULL,
  name          VARCHAR(80)  NOT NULL,
  slug          VARCHAR(40)  NOT NULL,
  city          VARCHAR(60)  NOT NULL,
  primary_hex   VARCHAR(7)   NOT NULL,
  secondary_hex VARCHAR(7)   NOT NULL,
  aliases       VARCHAR(500) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE (name),
  UNIQUE (slug)
);

-- ---------------------------------------------------------------------
-- ARTICLES - notizie aggregate dai feed RSS italiani
-- ---------------------------------------------------------------------
CREATE TABLE articles (
  id            BIGINT       NOT NULL AUTO_INCREMENT,
  team_id       INT          NOT NULL,
  title         VARCHAR(500) NOT NULL,
  summary       TEXT,
  link          VARCHAR(500) NOT NULL,
  source        VARCHAR(80)  NOT NULL,
  category      VARCHAR(20)  NOT NULL,
  image_url     VARCHAR(500),
  published_at  DATETIME     NOT NULL,
  fetched_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_link (link),
  KEY idx_articles_team_published (team_id, published_at),
  KEY idx_articles_category       (category, published_at),
  KEY idx_articles_published      (published_at),
  CONSTRAINT fk_article_team FOREIGN KEY (team_id) REFERENCES teams (id)
);

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
