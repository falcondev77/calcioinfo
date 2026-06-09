<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/teams.php';

$teamSlug = isset($_GET['team']) ? trim((string)$_GET['team']) : '';
$category = isset($_GET['cat'])  ? trim((string)$_GET['cat'])  : 'all';
$query    = isset($_GET['q'])    ? trim((string)$_GET['q'])    : '';
$days     = isset($_GET['days']) ? (int)$_GET['days']           : 7;

$validDays = [1, 3, 7, 14];
if (!in_array($days, $validDays, true)) $days = 7;
$minTs = time() - ($days * 86400);

$validCats = ['all','calciomercato','allenatore','conferenza','formazione','generale'];
if (!in_array($category, $validCats, true)) $category = 'all';

$selectedTeamId = null;
foreach (teams_all() as $t) {
    if ($t['slug'] === $teamSlug) { $selectedTeamId = $t['id']; break; }
}

$articles  = db_query_articles($selectedTeamId, $category === 'all' ? null : $category, $query, $minTs);
$counts    = db_counts_by_team($minTs);
$meta      = db_meta();
$lastRun   = $meta['last_run'];
$failed    = $meta['failed_feeds'];
$totalAll  = count($articles);
$totalDb   = $meta['total'];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function fmt_relative(string $iso): string {
    $ts = strtotime($iso);
    if ($ts === false) return $iso;
    $diff = time() - $ts;
    if ($diff < 60) return 'adesso';
    if ($diff < 3600) { $m = (int)($diff / 60); return $m . ' min fa'; }
    if ($diff < 86400) { $h = (int)($diff / 3600); return $h . ' or' . ($h === 1 ? 'a' : 'e') . ' fa'; }
    if ($diff < 86400 * 7) { $d = (int)($diff / 86400); return $d . ' giorn' . ($d === 1 ? 'o' : 'i') . ' fa'; }
    return date('d M', $ts);
}

function fmt_datetime(string $iso): array {
    $ts = strtotime($iso);
    if ($ts === false) return [$iso, ''];
    $months = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
    $date = date('d', $ts) . ' ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
    $time = date('H:i', $ts);
    return [$date, $time];
}

function cat_label(string $c): string {
    return [
        'calciomercato' => 'Calciomercato',
        'allenatore'    => 'Allenatore',
        'conferenza'    => 'Conferenza',
        'formazione'    => 'Formazione',
        'generale'      => 'News',
    ][$c] ?? 'News';
}

function build_url(array $overrides): string {
    $params = [
        'team' => $_GET['team'] ?? '',
        'cat'  => $_GET['cat']  ?? '',
        'q'    => $_GET['q']    ?? '',
        'days' => $_GET['days'] ?? '',
    ];
    foreach ($overrides as $k => $v) $params[$k] = $v;
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    if (!$params) return '?';
    return '?' . http_build_query($params);
}

$today = ucfirst(strftime_compat('%A %e %B %Y'));

function strftime_compat(string $fmt): string {
    $months = ['gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'];
    $days   = ['domenica','lunedi','martedi','mercoledi','giovedi','venerdi','sabato'];
    $ts = time();
    $out = $fmt;
    $out = str_replace('%A', $days[(int)date('w', $ts)], $out);
    $out = str_replace('%e', date('j', $ts), $out);
    $out = str_replace('%B', $months[(int)date('n', $ts) - 1], $out);
    $out = str_replace('%Y', date('Y', $ts), $out);
    return $out;
}

$cats = [
    ['all',           'Tutte'],
    ['calciomercato', 'Calciomercato'],
    ['allenatore',    'Allenatori'],
    ['conferenza',    'Conferenze'],
    ['formazione',    'Formazioni'],
    ['generale',      'Generale'],
];
?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Serie A News<?= $selectedTeamId ? ' - ' . h(team_by_id($selectedTeamId)['name']) : '' ?></title>
<meta name="description" content="Tutte le notizie piu recenti sulle 20 squadre di Serie A: calciomercato, allenatori, conferenze stampa e formazioni.">
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="app">

<header class="header">
  <div class="header__inner container">
    <a href="?" class="brand">
      <span class="brand__mark" aria-hidden="true">
        <span class="brand__stripe brand__stripe--g"></span>
        <span class="brand__stripe brand__stripe--w"></span>
        <span class="brand__stripe brand__stripe--r"></span>
      </span>
      <span class="brand__text">
        <span class="brand__title">Serie A News</span>
        <span class="brand__sub">Calciomercato, allenatori, conferenze e formazioni</span>
      </span>
    </a>
    <div class="header__actions">
      <div class="updated" id="updated">
        <?php if ($lastRun): ?>
          Aggiornato alle <?= h(date('H:i', strtotime($lastRun['finished_at']))) ?>
        <?php else: ?>
          Mai aggiornato &mdash; premi Aggiorna
        <?php endif; ?>
      </div>
      <button class="btn btn--primary" id="refreshBtn">
        <svg class="icon" id="refreshIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="23 4 23 10 17 10"></polyline>
          <polyline points="1 20 1 14 7 14"></polyline>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"></path>
          <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"></path>
        </svg>
        <span id="refreshLabel">Aggiorna</span>
      </button>
    </div>
  </div>
  <div class="header__progress" id="progress" hidden><div class="header__progress-bar"></div></div>
</header>

<section class="hero">
  <div class="container hero__inner">
    <div class="hero__copy">
      <span class="hero__eyebrow"><?= h($today) ?></span>
      <h1 class="hero__title">
        Tutte le notizie di Serie A,
        <span class="hero__title-accent">in tempo reale.</span>
      </h1>
      <p class="hero__sub">
        Aggreghiamo le fonti italiane piu autorevoli &mdash; Gazzetta, Sky Sport,
        Tuttosport, Corriere dello Sport, TMW e Calciomercato.com &mdash; per offrirti
        calciomercato, conferenze degli allenatori, formazioni e cambi tecnici delle 20
        squadre del prossimo campionato.
      </p>
    </div>
    <div class="hero__stats">
      <div class="stat"><div class="stat__value"><?= h((string)$totalAll) ?></div><div class="stat__label">Notizie ultim<?= $days === 1 ? 'e 24h' : 'i ' . $days . ' giorni' ?></div></div>
      <div class="stat"><div class="stat__value">20</div><div class="stat__label">Squadre seguite</div></div>
      <div class="stat"><div class="stat__value">6+</div><div class="stat__label">Fonti italiane</div></div>
    </div>
  </div>
</section>

<main class="container">

  <form class="filters" method="get" id="filtersForm">
    <input type="hidden" name="team" value="<?= h($_GET['team'] ?? '') ?>">
    <input type="hidden" name="cat"  value="<?= h($category) ?>">
    <div class="search-wrap">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input type="search" class="search" name="q" placeholder="Cerca per giocatore, allenatore, parola chiave..." value="<?= h($query) ?>">
      <?php if ($query !== ''): ?>
        <a class="clear-search" href="<?= h(build_url(['q' => null])) ?>" aria-label="Pulisci">&times;</a>
      <?php endif; ?>
    </div>
    <div class="cat-filter" role="tablist">
      <?php foreach ($cats as [$value, $label]): ?>
        <a role="tab" aria-selected="<?= $category === $value ? 'true' : 'false' ?>"
           class="cat-tab <?= $category === $value ? 'is-active' : '' ?>"
           href="<?= h(build_url(['cat' => $value === 'all' ? null : $value])) ?>"><?= h($label) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="freshness" role="group" aria-label="Finestra temporale">
      <span class="freshness__label">Periodo:</span>
      <?php foreach ([[1,'Oggi'],[3,'3 giorni'],[7,'Settimana'],[14,'2 settimane']] as [$value,$label]): ?>
        <a class="freshness__opt <?= $days === $value ? 'is-active' : '' ?>"
           href="<?= h(build_url(['days' => $value === 7 ? null : $value])) ?>"><?= h($label) ?></a>
      <?php endforeach; ?>
    </div>
  </form>

  <div class="team-filter">
    <a class="team-chip team-chip--all <?= $selectedTeamId === null ? 'is-active' : '' ?>"
       href="<?= h(build_url(['team' => null])) ?>">
      <span class="team-chip__name">Tutte le squadre</span>
    </a>
    <?php foreach (teams_all() as $team): $cnt = $counts[$team['id']] ?? 0; $active = $selectedTeamId === $team['id']; ?>
      <a class="team-chip <?= $active ? 'is-active' : '' ?>"
         style="--team-primary: <?= h($team['primary']) ?>; --team-secondary: <?= h($team['secondary']) ?>;"
         href="<?= h(build_url(['team' => $team['slug']])) ?>">
        <span class="team-chip__dot" aria-hidden="true"></span>
        <span class="team-chip__name"><?= h($team['name']) ?></span>
        <span class="team-chip__count"><?= h((string)$cnt) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($totalDb === 0): ?>
    <div class="banner banner--warn">
      Nessuna notizia ancora. Premi <strong>Aggiorna</strong> per raccogliere le ultime notizie dai feed italiani.
    </div>
  <?php elseif ($totalAll === 0): ?>
    <div class="banner banner--warn">
      Nessuna notizia negli ultimi <?= h((string)$days) ?> giorni con questi filtri. Allarga la finestra temporale o premi <strong>Aggiorna</strong>.
    </div>
  <?php elseif (!empty($failed)): ?>
    <div class="banner banner--warn">
      Alcune fonti non hanno risposto all'ultimo refresh: <?= h(implode(', ', array_slice($failed, 0, 4))) ?><?= count($failed) > 4 ? ', +' . (count($failed) - 4) : '' ?>.
    </div>
  <?php endif; ?>

  <?php if (count($articles) === 0): ?>
    <div class="empty">
      <h3>Nessuna notizia trovata</h3>
      <p>Prova a cambiare squadra, categoria o testo di ricerca.</p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($articles as $a):
        $team = team_by_id((int)$a['team_id']);
        [$dateStr, $timeStr] = fmt_datetime($a['published_at']);
      ?>
        <a class="card" href="<?= h($a['link']) ?>" target="_blank" rel="noopener noreferrer"
           style="--team-primary: <?= h($team['primary'] ?? '#888') ?>;">
          <?php if (!empty($a['image_url'])): ?>
            <div class="card__img">
              <img src="<?= h($a['image_url']) ?>" alt="" loading="lazy" onerror="this.style.display='none'; this.parentElement.classList.add('card__img--placeholder');">
              <span class="badge badge--<?= h($a['category']) ?>"><?= h(cat_label($a['category'])) ?></span>
            </div>
          <?php else: ?>
            <div class="card__img card__img--placeholder">
              <span class="badge badge--<?= h($a['category']) ?>"><?= h(cat_label($a['category'])) ?></span>
            </div>
          <?php endif; ?>
          <div class="card__body">
            <?php if ($team): ?>
              <div class="card__team" style="--team-primary: <?= h($team['primary']) ?>;">
                <span class="card__team-dot"></span>
                <span><?= h($team['name']) ?></span>
              </div>
            <?php endif; ?>
            <h3 class="card__title"><?= h($a['title']) ?></h3>
            <?php if (!empty($a['summary'])): ?>
              <p class="card__summary"><?= h($a['summary']) ?></p>
            <?php endif; ?>
            <div class="card__meta">
              <span class="card__source"><?= h($a['source']) ?></span>
              <span class="card__sep">&bull;</span>
              <span><?= h(fmt_relative($a['published_at'])) ?></span>
            </div>
            <div class="card__time">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline>
              </svg>
              <?= h($dateStr) ?> &middot; <?= h($timeStr) ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<footer class="footer">
  <div class="container footer__inner">
    <div><strong>Serie A News</strong> &mdash; aggregatore di notizie sulle 20 squadre del campionato.</div>
    <div class="footer__sources">Fonti: Gazzetta, Sky Sport, Tuttosport, Corriere dello Sport, TMW, Calciomercato.com.</div>
    <div class="footer__note">Ogni articolo rimanda alla pubblicazione originale tramite i link forniti dai feed RSS pubblici.</div>
  </div>
</footer>

</div>

<script>
(function () {
  var btn = document.getElementById('refreshBtn');
  var icon = document.getElementById('refreshIcon');
  var label = document.getElementById('refreshLabel');
  var progress = document.getElementById('progress');
  if (!btn) return;
  btn.addEventListener('click', function () {
    btn.disabled = true;
    icon.classList.add('icon--spin');
    label.textContent = 'Aggiornamento...';
    if (progress) progress.hidden = false;
    fetch('fetch.php', { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.ok) {
          window.location.reload();
        } else {
          throw new Error(data && data.error ? data.error : 'Errore sconosciuto');
        }
      })
      .catch(function (err) {
        alert('Aggiornamento fallito: ' + err.message);
        btn.disabled = false;
        icon.classList.remove('icon--spin');
        label.textContent = 'Aggiorna';
        if (progress) progress.hidden = true;
      });
  });
})();
</script>

</body>
</html>
