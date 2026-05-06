<?php
require_once __DIR__ . '/functions.php';

// Inline-Delete (Admin-Session erforderlich)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!rl_session_auth()) { http_response_code(403); exit('Nicht autorisiert.'); }
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    rl_delete_robot($id);
    header('Location: index.php'); exit;
}

$isAdmin = rl_session_auth();
$robots = array_values(rl_load_robots());
// Sort by name
usort($robots, fn($a, $b) => strcmp($a['name'], $b['name']));

// Unique Marken für Filter
$marken = array_unique(array_filter(array_column($robots, 'marke')));
sort($marken);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ROBLIB / Roboterbibliothek</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg:          #09131c;
  --bg2:         #0d1a26;
  --bg3:         #0f2030;
  --card:        #0d1e2e;
  --card-hover:  #112435;
  --border:      #1a3348;
  --border-hi:   #ff6000;
  --orange:      #ff6000;
  --orange-dim:  #b34400;
  --text:        #d8e8f0;
  --text-dim:    #6a8fa8;
  --text-bright: #ffffff;
  --mono:        'Share Tech Mono', monospace;
  --sans:        'Exo 2', sans-serif;
  --radius:      4px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--sans);
  min-height: 100vh;
}

/* ── HEADER ─────────────────────────────────────────────── */
header {
  background: #060e14;
  border-bottom: 2px solid var(--orange);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  height: 48px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.logo {
  font-family: var(--mono);
  font-size: 13px;
  color: var(--orange);
  letter-spacing: 0.08em;
  white-space: nowrap;
}

.logo span {
  color: var(--text-dim);
  font-size: 11px;
  margin-left: 8px;
}

.header-nav {
  display: flex;
  gap: 4px;
}

.header-nav a {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text-dim);
  text-decoration: none;
  padding: 4px 10px;
  border: 1px solid transparent;
  border-radius: var(--radius);
  transition: all 0.15s;
  letter-spacing: 0.05em;
}

.header-nav a:hover,
.header-nav a.active {
  color: var(--orange);
  border-color: var(--orange);
}

.header-right a {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text-dim);
  text-decoration: none;
  padding: 4px 10px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: all 0.15s;
}

.header-right a:hover {
  color: var(--orange);
  border-color: var(--orange);
}

/* ── HERO ────────────────────────────────────────────────── */
.hero {
  padding: 32px 24px 0;
  max-width: 1400px;
  margin: 0 auto;
}

.hero h1 {
  font-family: var(--mono);
  font-size: 22px;
  color: var(--orange);
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.hero p {
  font-size: 13px;
  color: var(--text-dim);
  font-family: var(--mono);
}

.hero-count {
  color: var(--orange);
  font-weight: 600;
}

/* ── FILTER BAR ──────────────────────────────────────────── */
.filter-bar {
  max-width: 1400px;
  margin: 20px auto 0;
  padding: 0 24px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.filter-bar input,
.filter-bar select {
  background: var(--bg3);
  border: 1px solid var(--border);
  color: var(--text);
  font-family: var(--mono);
  font-size: 12px;
  padding: 7px 12px;
  border-radius: var(--radius);
  outline: none;
  transition: border-color 0.15s;
}

.filter-bar input:focus,
.filter-bar select:focus {
  border-color: var(--orange);
}

.filter-bar input {
  min-width: 220px;
}

.filter-bar select option {
  background: var(--bg3);
}

.filter-count {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text-dim);
  margin-left: auto;
}

/* ── GRID ────────────────────────────────────────────────── */
.grid {
  max-width: 1400px;
  margin: 24px auto;
  padding: 0 24px 48px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}

/* ── CARD ────────────────────────────────────────────────── */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 6px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
  cursor: default;
}

.card:hover {
  border-color: var(--orange);
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(255,96,0,0.12);
}

.card-thumb {
  background: #0a1620;
  height: 170px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid var(--border);
  overflow: hidden;
}

.card-thumb img {
  max-height: 155px;
  max-width: 100%;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
}

.card-thumb .no-thumb {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text-dim);
  text-align: center;
  opacity: 0.5;
}

.card-thumb .no-thumb svg {
  display: block;
  margin: 0 auto 6px;
  opacity: 0.3;
}

.card-body {
  padding: 12px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.card-name {
  font-family: var(--mono);
  font-size: 13px;
  color: var(--text-bright);
  letter-spacing: 0.04em;
  line-height: 1.3;
  word-break: break-word;
}

.card-specs {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.card-specs tr {
  border-bottom: 1px solid var(--bg2);
}

.card-specs tr:last-child {
  border-bottom: none;
}

.card-specs td {
  padding: 4px 0;
  color: var(--text-dim);
  font-family: var(--mono);
}

.card-specs td:first-child {
  width: 55%;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.card-specs td:last-child {
  color: var(--text);
  text-align: right;
  font-size: 12px;
}

.card-footer {
  padding: 0 12px 12px;
}

.btn-load {
  display: block;
  width: 100%;
  background: var(--orange);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  font-family: var(--mono);
  font-size: 12px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 8px;
  text-align: center;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-load:hover {
  background: #ff7a20;
}

/* ── EMPTY STATE ─────────────────────────────────────────── */
.empty-state {
  grid-column: 1/-1;
  text-align: center;
  padding: 64px 0;
  font-family: var(--mono);
  color: var(--text-dim);
}

.empty-state .icon {
  font-size: 48px;
  margin-bottom: 12px;
  opacity: 0.3;
}

/* ── FOOTER ──────────────────────────────────────────────── */
footer {
  border-top: 1px solid var(--border);
  padding: 16px 24px;
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text-dim);
  text-align: center;
}

footer a { color: var(--orange); text-decoration: none; }

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media (max-width: 600px) {
  header { padding: 0 12px; }
  .hero, .filter-bar, .grid { padding-left: 12px; padding-right: 12px; }
  .grid { grid-template-columns: 1fr; }
}
.btn-delete {
  display: block; width: 100%; margin-top: 6px;
  padding: 9px; background: rgba(204,34,0,.2);
  color: #ff6040; border: 1px solid rgba(204,34,0,.4);
  border-radius: 4px; font-family: var(--mono); font-size: 12px;
  letter-spacing: .06em; text-transform: uppercase;
  cursor: pointer; transition: background .15s;
}
.btn-delete:hover { background: rgba(204,34,0,.5); }
</style>
</head>
<body>

<header>
  <div class="header-left">
    <div class="logo">
      ● ROBLIB<span>© CAD/CAM Systeme Datentechnik Reitz</span>
    </div>
    <nav class="header-nav">
      <a href="index.php" class="active">BIBLIOTHEK</a>
      <a href="https://cnc-technik.de/robsimul/robmodel/" target="_blank">ROBMODEL</a>
      <a href="https://cnc-technik.de/robsimul/" target="_blank">ROBSIMUL</a>
    </nav>
  </div>
  <div class="header-right">
    <?php if ($isAdmin): ?>
      <span style="font-family:var(--mono);font-size:11px;color:var(--orange);margin-right:8px">● ADMIN</span>
      <a href="manage.php?logout=1">Abmelden</a>
    <?php else: ?>
      <a href="manage.php">▲ UPLOAD</a>
    <?php endif; ?>
  </div>
</header>

<div class="hero">
  <h1>ROBOTERBIBLIOTHEK</h1>
  <p>
    <span class="hero-count"><?= count($robots) ?></span> Modelle verfügbar · 
    In RobModel erstellt · Per RobSimul ladbar
  </p>
</div>

<div class="filter-bar">
  <input type="text" id="search" placeholder="Suchen… (Name, Marke, Modell)">
  
  <select id="filter-marke">
    <option value="">Alle Marken</option>
    <?php foreach ($marken as $m): ?>
      <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
    <?php endforeach; ?>
  </select>

  <select id="filter-achsen">
    <option value="">Alle Achsen</option>
    <option value="4">4-Achsen</option>
    <option value="6">6-Achsen</option>
    <option value="7">7-Achsen</option>
  </select>

  <span class="filter-count" id="filter-count"></span>
</div>

<div class="grid" id="grid">
<?php if (empty($robots)): ?>
  <div class="empty-state">
    <div class="icon">⬡</div>
    Noch keine Robotermodelle in der Bibliothek.<br>
    <a href="manage.php" style="color:var(--orange)">Erstes Modell hochladen →</a>
  </div>
<?php else: ?>
  <?php foreach ($robots as $r): ?>
  <?php
    $thumb = $r['thumb_url'] ?? '';
    $zip   = $r['zip_url']   ?? '';
    $name  = htmlspecialchars($r['name']   ?? '—');
    $marke = htmlspecialchars($r['marke']  ?? '—');
    $mod   = htmlspecialchars($r['modell'] ?? '—');
    $achsen= intval($r['achsen'] ?? 0);
    $rw    = intval($r['reichweite_mm'] ?? 0);
    $nl    = floatval($r['nutzlast_kg'] ?? 0);
    $gw    = floatval($r['gewicht_kg']  ?? 0);
    $wg    = floatval($r['wiederholgenauigkeit_mm'] ?? 0);
  ?>
  <div class="card"
       data-name="<?= strtolower($name . ' ' . $marke . ' ' . $mod) ?>"
       data-marke="<?= strtolower(htmlspecialchars($r['marke'] ?? '')) ?>"
       data-achsen="<?= $achsen ?>">

    <div class="card-thumb">
      <?php if ($thumb): ?>
        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= $name ?>">
      <?php else: ?>
        <div class="no-thumb">
          <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
            <circle cx="20" cy="10" r="5" stroke="#6a8fa8" stroke-width="1.5"/>
            <line x1="20" y1="15" x2="20" y2="25" stroke="#6a8fa8" stroke-width="1.5"/>
            <line x1="20" y1="20" x2="10" y2="30" stroke="#6a8fa8" stroke-width="1.5"/>
            <line x1="20" y1="20" x2="30" y2="30" stroke="#6a8fa8" stroke-width="1.5"/>
          </svg>
          KEIN BILD
        </div>
      <?php endif; ?>
    </div>

    <div class="card-body">
      <div class="card-name"><?= $name ?></div>
      <table class="card-specs">
        <tr><td>Marke</td>              <td><?= $marke ?></td></tr>
        <tr><td>Modell</td>             <td><?= $mod ?></td></tr>
        <tr><td>Achsen</td>             <td><?= $achsen ?></td></tr>
        <tr><td>Reichweite</td>         <td><?= $rw ?> mm</td></tr>
        <tr><td>Nutzlast</td>           <td><?= $nl ?> kg</td></tr>
        <tr><td>Gewicht</td>            <td><?= $gw ?> kg</td></tr>
        <tr><td>Wiederholgen.</td>       <td><?= $wg ?> mm</td></tr>
      </table>
    </div>

    <div class="card-footer">
      <?php if ($zip): ?>
        <a class="btn-load" href="<?= htmlspecialchars($zip) ?>" download>▼ ZIP LADEN</a>
      <?php else: ?>
        <span class="btn-load" style="background:var(--bg3);color:var(--text-dim);cursor:default;">— KEINE DATEI —</span>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
        <form method="post" onsubmit="return confirm('Roboter «<?= $name ?>» löschen?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
          <button type="submit" class="btn-delete">✕ Löschen</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<footer>
  ROBLIB · <a href="manage.php">Upload / Verwaltung</a> · 
  <a href="api.php">API</a> · 
  CAD/CAM Systeme Datentechnik Reitz
</footer>

<script>
const cards  = document.querySelectorAll('.card[data-name]');
const search = document.getElementById('search');
const fMarke = document.getElementById('filter-marke');
const fAchs  = document.getElementById('filter-achsen');
const count  = document.getElementById('filter-count');

function filterCards() {
  const q = search.value.toLowerCase().trim();
  const m = fMarke.value.toLowerCase();
  const a = fAchs.value;
  let vis = 0;
  cards.forEach(c => {
    const matchQ = !q || c.dataset.name.includes(q);
    const matchM = !m || c.dataset.marke === m;
    const matchA = !a || c.dataset.achsen === a;
    const show   = matchQ && matchM && matchA;
    c.style.display = show ? '' : 'none';
    if (show) vis++;
  });
  count.textContent = cards.length ? `${vis} / ${cards.length} angezeigt` : '';
}

search.addEventListener('input',  filterCards);
fMarke.addEventListener('change', filterCards);
fAchs.addEventListener('change',  filterCards);
filterCards();
</script>
</body>
</html>
