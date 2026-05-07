<?php
require_once __DIR__ . '/functions.php';
// Login / Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    rl_session_login($_POST['user'] ?? '', $_POST['pass'] ?? '');
    header('Location: index.php'); exit;
}
if (isset($_GET['logout'])) {
    rl_session_logout();
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
  height: 60px;
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
  font-size: 18px;
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
  font-size: 22px;
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
.btn-edit {
  display:block;width:100%;margin-top:6px;padding:9px;
  background:rgba(37,99,235,.2);color:#60a5fa;
  border:1px solid rgba(37,99,235,.4);border-radius:4px;
  font-family:var(--mono);font-size:12px;letter-spacing:.06em;
  text-transform:uppercase;cursor:pointer;transition:background .15s;
}
.btn-edit:hover{background:rgba(37,99,235,.4);}
.rl-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);display:flex;
  align-items:center;justify-content:center;z-index:1000;}
.rl-modal{background:#0d1e2e;border:1px solid #2563eb;border-radius:8px;
  width:min(520px,95vw);max-height:90vh;overflow-y:auto;}
.rl-modal-head{display:flex;align-items:center;justify-content:space-between;
  padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.1);
  font-family:var(--mono);font-size:13px;color:#60a5fa;}
.rl-close{background:none;border:none;color:#6a8fa8;font-size:18px;cursor:pointer;padding:2px 8px;}
.rl-close:hover{color:#fff;}
.rl-modal-body{padding:16px;}
.rl-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;}
.rl-grid label{display:flex;flex-direction:column;font-family:var(--mono);
  font-size:11px;color:#6a8fa8;gap:4px;text-transform:uppercase;letter-spacing:.05em;}
.rl-grid input,.rl-grid textarea{background:#0f2030;border:1px solid rgba(255,255,255,.15);
  border-radius:4px;padding:6px 8px;color:#d8e8f0;font-family:var(--mono);font-size:12px;outline:none;}
.rl-grid input:focus{border-color:#2563eb;}
.rl-span2{grid-column:1/-1;}
.rl-thumb-preview{width:100%;max-height:120px;object-fit:contain;margin-top:6px;
  border-radius:4px;border:1px solid rgba(255,255,255,.1);}
.rl-save{width:100%;padding:9px;background:#2563eb;color:#fff;border:none;
  border-radius:5px;font-family:var(--mono);font-size:13px;cursor:pointer;margin-top:4px;}
.rl-save:hover{background:#1d4ed8;}
.rl-save:disabled{opacity:.5;cursor:default;}
.rl-msg{padding:8px 12px;border-radius:4px;font-family:var(--mono);font-size:12px;margin-bottom:10px;}
.rl-ok{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3);}
.rl-err{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);}
.btn-delete {
  display:block;width:100%;margin-top:6px;padding:9px;
  background:rgba(204,34,0,.2);color:#ff6040;
  border:1px solid rgba(204,34,0,.4);border-radius:4px;
  font-family:var(--mono);font-size:12px;letter-spacing:.06em;
  text-transform:uppercase;cursor:pointer;transition:background .15s;
}
.btn-delete:hover{background:rgba(204,34,0,.5);}
.tab-bar{display:flex;gap:0;border-bottom:1px solid rgba(255,96,0,.3);margin-bottom:24px}
.tab-btn{font-family:var(--mono);font-size:12px;padding:8px 18px;background:none;border:none;
  color:#6a8fa8;cursor:pointer;letter-spacing:.06em;border-bottom:2px solid transparent;margin-bottom:-1px}
.tab-btn.active{color:var(--orange);border-bottom-color:var(--orange)}
.tab-content{display:none}.tab-content.active{display:block}
.usr-table{width:100%;border-collapse:collapse;font-family:var(--mono);font-size:12px}
.usr-table th{color:#6a8fa8;padding:6px 10px;text-align:left;border-bottom:1px solid rgba(255,255,255,.08);letter-spacing:.06em}
.usr-table td{padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.05);color:#d8e8f0;vertical-align:middle}
.usr-table tr:hover td{background:rgba(255,255,255,.03)}
.btn-sm{font-family:var(--mono);font-size:11px;padding:3px 8px;border-radius:3px;cursor:pointer;border:1px solid}
.btn-sm-edit{background:rgba(37,99,235,.2);color:#60a5fa;border-color:rgba(37,99,235,.4)}
.btn-sm-del{background:rgba(204,34,0,.2);color:#ff6040;border-color:rgba(204,34,0,.4)}
.btn-sm-add{background:rgba(255,96,0,.15);color:var(--orange);border-color:rgba(255,96,0,.4);padding:5px 14px}
.robot-check-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:6px;max-height:200px;overflow-y:auto;padding:4px}
.robot-check-item{display:flex;align-items:center;gap:6px;font-family:var(--mono);font-size:11px;color:#d8e8f0;
  background:#0f2030;padding:5px 8px;border-radius:4px;cursor:pointer}
.robot-check-item input{accent-color:var(--orange)}
/* ── Thumb Zoom ──────────────────────────────────────────── */
#thumbZoom {
  display:none;position:fixed;z-index:9000;pointer-events:none;
  border:1px solid rgba(255,96,0,.5);border-radius:8px;overflow:hidden;
  box-shadow:0 8px 40px rgba(0,0,0,.8);
  transition:opacity .15s;
}
#thumbZoom img {
  display:block;width:420px;height:420px;object-fit:contain;background:#060e14;
}
</style>
</head>
<body>

<header>
  <div class="header-left">
    <div class="logo">
      ⚙ ROBLIB<span>© CAD/CAM Systeme Datentechnik Reitz</span>
    </div>
    <nav class="header-nav">
      <a href="index.php" class="active">BIBLIOTHEK</a>
      <a href="https://cnc-technik.de/robsimul/robmodel/" target="_blank">ROBMODEL</a>
      <a href="https://cnc-technik.de/robsimul/" target="_blank">ROBSIMUL</a>
    </nav>
  </div>
  <div class="header-right">
    <button id="rlThemeBtn" onclick="rlToggleTheme()" title="Theme wechseln" style="background:none;border:none;font-size:18px;cursor:pointer;margin-right:8px;padding:2px 6px">🌙</button>
    <?php if ($isAdmin): ?>
      <span style="font-family:var(--mono);font-size:11px;color:var(--orange);margin-right:8px">&#x25CF; ADMIN</span>
      <button onclick="openUserManager()"
        style="background:none;border:1px solid rgba(255,96,0,.4);color:var(--orange);font-family:var(--mono);font-size:11px;padding:3px 10px;border-radius:3px;cursor:pointer;margin-right:8px;letter-spacing:.06em">
        BENUTZER
      </button>
      <a href="?logout=1" style="color:var(--orange)">Abmelden</a>
    <?php else: ?>
      <button onclick="document.getElementById('loginModal').style.display='flex'"
        style="background:none;border:1px solid var(--orange);color:var(--orange);font-family:var(--mono);font-size:11px;padding:4px 10px;border-radius:4px;cursor:pointer;letter-spacing:.06em">
        &#x25B2; ANMELDEN
      </button>
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

      <?php if($isAdmin):?>
        <button class="btn-edit"
          data-id="<?php echo htmlspecialchars($r['id'],ENT_QUOTES);?>"
          data-name="<?php echo htmlspecialchars($r['name'],ENT_QUOTES);?>"
          data-marke="<?php echo htmlspecialchars($r['marke'],ENT_QUOTES);?>"
          data-modell="<?php echo htmlspecialchars($r['modell'],ENT_QUOTES);?>"
          data-achsen="<?php echo intval($r['achsen']);?>"
          data-reichweite="<?php echo intval($r['reichweite_mm']);?>"
          data-nutzlast="<?php echo floatval($r['nutzlast_kg']);?>"
          data-gewicht="<?php echo floatval($r['gewicht_kg']);?>"
          data-wdh="<?php echo floatval($r['wiederholgenauigkeit_mm']);?>"
          data-thumb="<?php echo htmlspecialchars($r['thumb_url']??'',ENT_QUOTES);?>"
          onclick="rlEdit(this)">&#x270E; Bearbeiten</button>
        <button class="btn-delete"
          data-id="<?php echo htmlspecialchars($r['id'],ENT_QUOTES);?>"
          data-name="<?php echo htmlspecialchars($r['name'],ENT_QUOTES);?>"
          onclick="rlDelete(this)">&#x2715; L&ouml;schen</button>
      <?php endif;?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>
</div><!-- tab-robots -->

<?php if ($isAdmin): ?>
<?php endif; ?>

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

function rlDelete(btn){
  var name=btn.getAttribute('data-name');
  var id=btn.getAttribute('data-id');
  if(!confirm('Roboter '+name+' wirklich löschen?'))return;
  var pass=prompt('Admin-Passwort:');
  if(pass===null)return;
  var fd=new FormData();
  fd.append('action','delete');fd.append('id',id);
  fd.append('user','admin');fd.append('pass',pass);
  fetch('api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){if(d.ok)location.reload();else alert('Fehler: '+d.error);})
    .catch(function(e){alert('Fehler: '+e.message);});
}

var _rlId = '';
function rlEdit(btn) {
  _rlId = btn.getAttribute('data-id');
  document.getElementById('rl-name').value      = btn.getAttribute('data-name');
  document.getElementById('rl-marke').value     = btn.getAttribute('data-marke');
  document.getElementById('rl-modell').value    = btn.getAttribute('data-modell');
  document.getElementById('rl-achsen').value    = btn.getAttribute('data-achsen');
  document.getElementById('rl-reichweite').value= btn.getAttribute('data-reichweite');
  document.getElementById('rl-nutzlast').value  = btn.getAttribute('data-nutzlast');
  document.getElementById('rl-gewicht').value   = btn.getAttribute('data-gewicht');
  document.getElementById('rl-wdh').value       = btn.getAttribute('data-wdh');
  var thumb = btn.getAttribute('data-thumb');
  var img = document.getElementById('rl-thumb-img');
  if (thumb) { img.src=thumb; img.style.display='block'; } else { img.style.display='none'; }
  document.getElementById('rl-thumb-file').value = '';
  document.getElementById('rlMsg').style.display = 'none';
  document.getElementById('rlModal').style.display = 'flex';
}
function rlSave() {
  var pass = prompt('Admin-Passwort:');
  if (pass === null) return;
  var btn = document.getElementById('rlSave');
  btn.disabled = true; btn.textContent = 'Speichern...';
  var fd = new FormData();
  fd.append('action','update'); fd.append('id',_rlId);
  fd.append('session','1');
  fd.append('name',   document.getElementById('rl-name').value);
  fd.append('marke',  document.getElementById('rl-marke').value);
  fd.append('modell', document.getElementById('rl-modell').value);
  fd.append('achsen', document.getElementById('rl-achsen').value);
  fd.append('reichweite_mm', document.getElementById('rl-reichweite').value);
  fd.append('nutzlast_kg',   document.getElementById('rl-nutzlast').value);
  fd.append('gewicht_kg',    document.getElementById('rl-gewicht').value);
  fd.append('wiederholgenauigkeit_mm', document.getElementById('rl-wdh').value);
  var thumbFile = document.getElementById('rl-thumb-file').files[0];
  if (thumbFile) fd.append('thumb', thumbFile, thumbFile.name);
  fetch('api.php', {method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ location.reload(); }
      else {
        var msg=document.getElementById('rlMsg');
        msg.textContent='Fehler: '+d.error; msg.className='rl-msg rl-err';
        msg.style.display=''; btn.disabled=false; btn.textContent='Speichern';
      }
    })
    .catch(function(e){
      var msg=document.getElementById('rlMsg');
      msg.textContent='Fehler: '+e.message; msg.className='rl-msg rl-err';
      msg.style.display=''; btn.disabled=false; btn.textContent='Speichern';
    });
}
</script>

<!-- Edit Modal -->
<div id="rlModal" class="rl-overlay" style="display:none">
  <div class="rl-modal">
    <div class="rl-modal-head">
      <span>&#x270E; Roboter bearbeiten</span>
      <button class="rl-close" onclick="document.getElementById('rlModal').style.display='none'">&#x2715;</button>
    </div>
    <div class="rl-modal-body">
      <div id="rlMsg" class="rl-msg" style="display:none"></div>
      <div class="rl-grid">
        <label class="rl-span2">Name<input id="rl-name" type="text"></label>
        <label>Marke<input id="rl-marke" type="text"></label>
        <label>Modell<input id="rl-modell" type="text"></label>
        <label>Achsen<input id="rl-achsen" type="number" min="1" max="9"></label>
        <label>Reichweite (mm)<input id="rl-reichweite" type="number"></label>
        <label>Nutzlast (kg)<input id="rl-nutzlast" type="number" step="0.1"></label>
        <label>Gewicht (kg)<input id="rl-gewicht" type="number" step="0.1"></label>
        <label>Wiederholgenaui. (mm)<input id="rl-wdh" type="number" step="0.001"></label>
        <label class="rl-span2">Thumbnail
          <input id="rl-thumb-file" type="file" accept="image/*"
            onchange="var r=new FileReader();r.onload=function(e){var img=document.getElementById('rl-thumb-img');img.src=e.target.result;img.style.display='block';};r.readAsDataURL(this.files[0]);">
          <img id="rl-thumb-img" class="rl-thumb-preview" style="display:none">
        </label>
      </div>
      <button class="rl-save" id="rlSave" onclick="rlSave()">Speichern</button>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div id="loginModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#0d1a26;border:1px solid var(--orange);border-radius:8px;width:min(320px,92vw);padding:24px">
    <div style="font-family:var(--mono);font-size:13px;color:var(--orange);margin-bottom:18px;letter-spacing:.06em">&#x25A0; ADMIN LOGIN</div>
    <form method="post" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="action" value="login">
      <input type="text" name="user" placeholder="Benutzer" autocomplete="username"
        style="background:#0f2030;border:1px solid rgba(255,96,0,.4);border-radius:4px;padding:8px 10px;color:#d8e8f0;font-family:var(--mono);font-size:13px;outline:none">
      <input type="password" name="pass" placeholder="Passwort" autocomplete="current-password"
        style="background:#0f2030;border:1px solid rgba(255,96,0,.4);border-radius:4px;padding:8px 10px;color:#d8e8f0;font-family:var(--mono);font-size:13px;outline:none">
      <div style="display:flex;gap:8px;margin-top:4px">
        <button type="submit"
          style="flex:1;padding:9px;background:var(--orange);color:#000;border:none;border-radius:4px;font-family:var(--mono);font-size:12px;font-weight:700;cursor:pointer;letter-spacing:.06em">
          ANMELDEN
        </button>
        <button type="button" onclick="document.getElementById('loginModal').style.display='none'"
          style="padding:9px 14px;background:none;border:1px solid rgba(255,255,255,.2);color:#6a8fa8;border-radius:4px;font-family:var(--mono);font-size:12px;cursor:pointer">
          &#x2715;
        </button>
      </div>
    </form>
  </div>
</div>

<!-- User Modal -->
<div id="userModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#0d1a26;border:1px solid var(--orange);border-radius:8px;width:min(560px,95vw);max-height:88vh;display:flex;flex-direction:column">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid rgba(255,96,0,.3)">
      <span id="userModalTitle" style="font-family:var(--mono);font-size:13px;color:var(--orange)">BENUTZER</span>
      <button onclick="document.getElementById('userModal').style.display='none'" style="background:none;border:none;color:#888;font-size:18px;cursor:pointer">&#x2715;</button>
    </div>
    <div style="padding:16px;overflow-y:auto;flex:1">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
        <label style="font-family:var(--mono);font-size:11px;color:#6a8fa8;display:flex;flex-direction:column;gap:4px">
          BENUTZER *<input id="um-user" type="text" style="background:#0f2030;border:1px solid rgba(255,255,255,.15);border-radius:4px;padding:6px 8px;color:#d8e8f0;font-family:var(--mono);font-size:12px;outline:none">
        </label>
        <label style="font-family:var(--mono);font-size:11px;color:#6a8fa8;display:flex;flex-direction:column;gap:4px">
          PASSWORT<input id="um-pass" type="password" placeholder="leer = unverändert" style="background:#0f2030;border:1px solid rgba(255,255,255,.15);border-radius:4px;padding:6px 8px;color:#d8e8f0;font-family:var(--mono);font-size:12px;outline:none">
        </label>
      </div>
      <div style="font-family:var(--mono);font-size:11px;color:#6a8fa8;margin-bottom:8px;letter-spacing:.06em">ROBOTER-ZUGANG</div>
      <div id="um-robots" class="robot-check-grid"></div>
      <div id="um-msg" style="display:none;margin-top:12px;padding:8px;border-radius:4px;font-family:var(--mono);font-size:12px"></div>
    </div>
    <div style="padding:12px 16px;border-top:1px solid rgba(255,255,255,.08)">
      <button id="um-save" onclick="saveUser()" style="width:100%;padding:9px;background:var(--orange);color:#000;border:none;border-radius:4px;font-family:var(--mono);font-size:12px;font-weight:700;cursor:pointer;letter-spacing:.06em">SPEICHERN</button>
    </div>
  </div>
</div>

<script>
var _adminUser = '';
var _adminPass = '';

function doLogin() {
  var u = document.getElementById('li-user').value.trim();
  var p = document.getElementById('li-pass').value;
  if (!u || !p) return;
  var btn = document.getElementById('li-btn');
  btn.disabled = true; btn.textContent = '…';
  var fd = new FormData();
  fd.append('action','list'); fd.append('user',u); fd.append('pass',p);
  fetch('api.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
    btn.disabled=false; btn.textContent='ANMELDEN';
    if(d.ok){
      _adminUser=u; _adminPass=p;
      document.getElementById('loginModal').style.display='none';
      document.getElementById('loginErr').style.display='none';
      location.reload();
    } else {
      document.getElementById('loginErr').style.display='block';
    }
  }).catch(function(){
    btn.disabled=false; btn.textContent='ANMELDEN';
    document.getElementById('loginErr').style.display='block';
  });
}

function openUserManager() {
  document.getElementById('userMgrOverlay').style.display = 'flex';
  loadUsers();
}

var _editUserId = null;
var _allRobots = [];

function switchTab(tab) {
  ['robots','users'].forEach(function(t) {
    document.getElementById('tab-'+t).classList.toggle('active', t===tab);
    document.querySelectorAll('.tab-btn').forEach(function(b,i) {
      b.classList.toggle('active', (i===0&&tab==='robots')||(i===1&&tab==='users'));
    });
  });
  if (tab === 'users') loadUsers();
}

function getAdminPass() {
  if (_adminPass) return _adminPass;
  _adminPass = prompt('Admin-Passwort:');
  return _adminPass;
}

function loadUsers() {
  var fd = new FormData();
  fd.append('action','list_users'); fd.append('session','1');
  fetch('api.php', {method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()).then(function(d) {
    if (!d.ok) { document.getElementById('usrBody').innerHTML='<tr><td colspan="3" style="color:#f87171">'+d.error+'</td></tr>'; return; }
    fetch('api.php?action=list').then(r=>r.json()).then(function(rd) { _allRobots = rd.robots || []; });
    renderUsers(d.users);
  }).catch(function(e){
    document.getElementById('usrBody').innerHTML='<tr><td colspan="3" style="color:#f87171">'+e.message+'</td></tr>';
  });
}

function renderUsers(users) {
  var tbody = document.getElementById('usrBody');
  if (!users.length) { tbody.innerHTML='<tr><td colspan="3" style="color:#6a8fa8">Keine Benutzer.</td></tr>'; return; }
  tbody.innerHTML = users.map(function(u) {
    var rCount = (u.robots||[]).length;
    var safeId   = (u.id||'').replace(/"/g,'');
    var safeName = (u.username||'').replace(/"/g,'');
    return '<tr><td>'+safeName+'</td><td style="color:#6a8fa8">'+rCount+' Roboter</td><td style="white-space:nowrap">' +
      '<button class="btn-sm btn-sm-edit" data-u="'+encodeURIComponent(JSON.stringify(u))+'" onclick="openEditUser(JSON.parse(decodeURIComponent(this.dataset.u)))">&#x270E;</button> ' +
      '<button class="btn-sm btn-sm-del" data-id="'+safeId+'" data-name="'+safeName+'" onclick="deleteUser(this.dataset.id,this.dataset.name)">&#x2715;</button>' +
      '</td></tr>';
  }).join('');
}

function openAddUser() {
  _editUserId = null;
  document.getElementById('userModalTitle').textContent = 'BENUTZER HINZUFÜGEN';
  document.getElementById('um-user').value = '';
  document.getElementById('um-pass').value = '';
  document.getElementById('um-msg').style.display = 'none';
  buildRobotChecks([]);
  document.getElementById('userModal').style.display = 'flex';
}

function openEditUser(u) {
  _editUserId = u.id;
  document.getElementById('userModalTitle').textContent = 'BENUTZER BEARBEITEN';
  document.getElementById('um-user').value = u.username;
  document.getElementById('um-pass').value = '';
  document.getElementById('um-msg').style.display = 'none';
  buildRobotChecks(u.robots || []);
  document.getElementById('userModal').style.display = 'flex';
}

function buildRobotChecks(selected) {
  var grid = document.getElementById('um-robots');
  if (!_allRobots.length) {
    grid.innerHTML = '<span style="color:#6a8fa8;font-family:var(--mono);font-size:11px">Keine Roboter vorhanden.</span>';
    return;
  }
  grid.innerHTML = _allRobots.map(function(r) {
    var checked = selected.indexOf(r.id) >= 0 ? 'checked' : '';
    return '<label class="robot-check-item"><input type="checkbox" value="'+r.id+'" '+checked+'> '+r.name+'</label>';
  }).join('');
}

function saveUser() {
  var btn = document.getElementById('um-save');
  var msg = document.getElementById('um-msg');
  var robots = Array.from(document.querySelectorAll('#um-robots input:checked')).map(function(c){return c.value;});
  var fd = new FormData();
  fd.append('session','1');
  fd.append('username', document.getElementById('um-user').value);
  var pw = document.getElementById('um-pass').value;
  if (pw) fd.append('password', pw);
  fd.append('robots', JSON.stringify(robots));
  if (_editUserId) {
    fd.append('action','update_user'); fd.append('id',_editUserId);
  } else {
    fd.append('action','add_user');
    if (!pw) { msg.textContent='Passwort erforderlich.'; msg.style.cssText='display:block;background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);border-radius:4px;padding:8px;font-family:var(--mono);font-size:12px;margin-top:12px'; return; }
  }
  btn.disabled=true; btn.textContent='Speichern...';
  fetch('api.php',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()).then(function(d) {
    btn.disabled=false; btn.textContent='SPEICHERN';
    if (d.ok) { document.getElementById('userModal').style.display='none'; loadUsers(); }
    else { msg.textContent='Fehler: '+d.error; msg.style.cssText='display:block;background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);border-radius:4px;padding:8px;font-family:var(--mono);font-size:12px;margin-top:12px'; }
  });
}

function deleteUser(id, name) {
  if (!confirm('Benutzer «'+name+'» löschen?')) return;
  var fd = new FormData();
  fd.append('action','delete_user'); fd.append('id',id); fd.append('session','1');
  fetch('api.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(d) {
    if (d.ok) loadUsers();
    else alert('Fehler: '+d.error);
  });
}
</script>

<div id="thumbZoom"><img id="thumbZoomImg" src="" alt=""></div>

<script>
(function(){
  var zoomEl  = document.getElementById('thumbZoom');
  var zoomImg = document.getElementById('thumbZoomImg');
  var margin  = 16;

  document.querySelectorAll('.card-thumb img').forEach(function(img) {
    img.addEventListener('mouseenter', function(e) {
      zoomImg.src = img.src;
      zoomEl.style.display = 'block';
      positionZoom(e);
    });
    img.addEventListener('mousemove', positionZoom);
    img.addEventListener('mouseleave', function() {
      zoomEl.style.display = 'none';
    });
  });

  function positionZoom(e) {
    var w = 420, h = 420;
    var vw = window.innerWidth, vh = window.innerHeight;
    var x = e.clientX + margin;
    var y = e.clientY + margin;
    if (x + w > vw) x = e.clientX - w - margin;
    if (y + h > vh) y = e.clientY - h - margin;
    zoomEl.style.left = x + 'px';
    zoomEl.style.top  = y + 'px';
  }
})();
</script>

<!-- User Manager Overlay -->
<div id="userMgrOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9998;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 20px">
  <div style="background:#0d1a26;border:1px solid rgba(255,96,0,.4);border-radius:8px;width:min(820px,100%);padding:24px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <span style="font-family:var(--mono);font-size:14px;color:var(--orange);letter-spacing:.06em">BENUTZERVERWALTUNG</span>
      <div style="display:flex;gap:10px;align-items:center">
        <button class="btn-sm btn-sm-add" onclick="openAddUser()">+ BENUTZER</button>
        <button onclick="document.getElementById('userMgrOverlay').style.display='none'"
          style="background:none;border:none;color:#6a8fa8;font-size:20px;cursor:pointer;line-height:1">&#x2715;</button>
      </div>
    </div>
    <table class="usr-table" id="usrTable">
      <thead><tr><th>BENUTZER</th><th>ROBOTER</th><th>AKTIONEN</th></tr></thead>
      <tbody id="usrBody"><tr><td colspan="3" style="color:#6a8fa8">Lade…</td></tr></tbody>
    </table>
  </div>
</div>

<style id="rl-theme-style"></style>
<script>
(function(){
  var themes = [
    { name:'dark',       icon:'🌑', vars:{'--bg':'#09131c','--bg2':'#0d1a26','--bg3':'#0f2030','--card':'#0d1e2e','--card-hover':'#112435','--border':'#1a3348','--text':'#d8e8f0','--text-dim':'#6a8fa8','--text-bright':'#ffffff'}},
    { name:'bg-pro',     icon:'💻', vars:{'--bg':'#1e1e1e','--bg2':'#252526','--bg3':'#2d2d30','--card':'#252526','--card-hover':'#2a2a2c','--border':'#3e3e42','--text':'#d4d4d4','--text-dim':'#808080','--text-bright':'#ffffff'}},
    { name:'bg-white',   icon:'☀️', vars:{'--bg':'#f5f5f0','--bg2':'#eaeae5','--bg3':'#ddddd8','--card':'#eaeae5','--card-hover':'#e0e0da','--border':'#b8b8b2','--text':'#1a2a3a','--text-dim':'#4a6a8a','--text-bright':'#000000'}},
    { name:'bg-minimal', icon:'◻️', vars:{'--bg':'#f4f4f4','--bg2':'#ebebeb','--bg3':'#e0e0e0','--card':'#ebebeb','--card-hover':'#e4e4e4','--border':'#b0b0b0','--text':'#111111','--text-dim':'#666666','--text-bright':'#000000'}},
    { name:'bg-win11',   icon:'🪟', vars:{'--bg':'#f3f6fc','--bg2':'#e8eef8','--bg3':'#dde5f4','--card':'#e8eef8','--card-hover':'#dce5f2','--border':'#c8d8e8','--text':'#1a2a3a','--text-dim':'#4a6a8a','--text-bright':'#000000'}},
    { name:'bg-deep',    icon:'🌌', vars:{'--bg':'#000408','--bg2':'#040c14','--bg3':'#081420','--card':'#040c14','--card-hover':'#081420','--border':'#0a2030','--text':'#a0c8e0','--text-dim':'#3a6080','--text-bright':'#ffffff'}},
    { name:'bg-vivid',   icon:'🟣', vars:{'--bg':'#1a0a2e','--bg2':'#22103c','--bg3':'#2a1848','--card':'#22103c','--card-hover':'#2a1848','--border':'#3a2060','--text':'#e0c8f8','--text-dim':'#8060a0','--text-bright':'#ffffff'}},
    { name:'bg-matrix',  icon:'💚', vars:{'--bg':'#000800','--bg2':'#001400','--bg3':'#001c00','--card':'#001400','--card-hover':'#001c00','--border':'#003000','--text':'#00dd44','--text-dim':'#006622','--text-bright':'#00ff66'}},
  ];
  var idx = parseInt(localStorage.getItem('rl-theme')||'0');
  function applyTheme(i) {
    var t = themes[i];
    var s = 'body{' + Object.entries(t.vars).map(function(e){return e[0]+':'+e[1]}).join(';') + '}';
    document.getElementById('rl-theme-style').textContent = s;
    var btn = document.getElementById('rlThemeBtn');
    if (btn) btn.textContent = themes[(i+1)%themes.length].icon;
    localStorage.setItem('rl-theme', i);
    idx = i;
  }
  window.rlToggleTheme = function() { applyTheme((idx+1)%themes.length); };
  document.addEventListener('DOMContentLoaded', function(){ applyTheme(idx); });
})();
</script>
</body>
</html>
