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
$robots     = array_values(rl_load_robots());
$robots_r   = array_filter($robots, fn($r) => ($r['type']??'robot') === 'robot');
$robots_eff = array_filter($robots, fn($r) => ($r['type']??'robot') === 'endeffektor');
$robots_umf = array_filter($robots, fn($r) => ($r['type']??'robot') === 'umfeld');
$robots_pos = array_filter($robots, fn($r) => ($r['type']??'robot') === 'positioner');
$robots_obj = array_filter($robots, fn($r) => ($r['type']??'robot') === 'object');
$robots_fix = array_filter($robots, fn($r) => ($r['type']??'robot') === 'fixture');
$robots_sta = array_filter($robots, fn($r) => ($r['type']??'robot') === 'station');
$robots_rail = array_filter($robots, fn($r) => ($r['type']??'robot') === 'rail');
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
  padding: 0 24px; /* aligned via wrapper */
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
.type-tabs{display:flex;gap:6px;flex-wrap:wrap;max-width:1100px;margin:0 0 16px;padding:0 20px}
.type-tab{font-family:var(--mono);font-size:12px;padding:6px 14px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;color:var(--text-dim);cursor:pointer;transition:all .15s;letter-spacing:.04em}
.type-tab.active{background:rgba(255,96,0,.15);border-color:var(--orange);color:var(--orange)}
.type-tab:hover:not(.active){border-color:var(--orange);color:var(--text)}
.type-cnt{display:inline-block;background:rgba(255,255,255,.1);border-radius:10px;padding:0 6px;font-size:10px;margin-left:4px}
.type-tab.active .type-cnt{background:rgba(255,96,0,.2)}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
</head>
<body>

<header>
  <div class="header-left">
    <div class="logo">
      ⚙ ROBLIB<span>© CAD/CAM Systeme Datentechnik Reitz</span>
    </div>
    <nav class="header-nav">
      <a href="index.php" <?= (!isset($_GET['tab'])||$_GET['tab']!=='krl')?'class="active"':'' ?>>BIBLIOTHEK</a>
      <a href="index.php?tab=krl" <?= (($_GET['tab']??'')==='krl')?'class="active"':'' ?>>PROGRAMME</a>
      <a href="https://cnc-technik.de/robsimul/robmodel/" target="_blank">ROBMODEL</a>
      <a href="https://cnc-technik.de/robsimul/" target="_blank">ROBSIMUL</a>
      <a href="https://cnc-technik.de/kuka/" target="_blank">KRL EDITOR</a>
    </nav>
  </div>
  <div class="header-right">
    <button id="rlThemeBtn" onclick="rlToggleTheme()" title="Theme wechseln" style="background:none;border:1px solid var(--orange);color:var(--orange);font-size:16px;cursor:pointer;margin-right:8px;padding:2px 8px;border-radius:4px">◑</button>
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

<?php if (($_GET['tab']??'') === 'krl'): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!--  PROGRAMME TAB                                                         -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="hero">
  <h1>PROGRAMME</h1>
  <p>Programme &amp; Snippets für den <strong style="color:var(--orange)">KRL Editor</strong> · Community-Bibliothek</p>
</div>

<div id="krlApp" style="max-width:1100px;margin:0 auto;padding:0 24px 40px">

  <!-- Stats + User -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:4px;padding:10px 18px;font-size:12px;color:var(--text-dim)">
      <strong id="kStatTotal" style="display:block;font-size:22px;color:var(--text)">–</strong>Programme
    </div>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:4px;padding:10px 18px;font-size:12px;color:var(--text-dim)">
      <strong id="kStatPoints" style="display:block;font-size:22px;color:var(--orange)">–</strong>Deine Punkte
    </div>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:4px;padding:10px 18px;font-size:12px;color:var(--text-dim)">
      <strong id="kStatUploads" style="display:block;font-size:22px;color:var(--text)">–</strong>Deine Uploads
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
      <span id="kUserLabel" style="font-family:var(--mono);font-size:12px;color:var(--text-dim)"></span>
      <?php if ($isAdmin): ?>
      <button onclick="kShowPointsAdmin()" style="background:rgba(255,96,0,.1);border:1px solid rgba(255,96,0,.4);color:var(--orange);font-family:var(--mono);font-size:11px;padding:5px 12px;border-radius:4px;cursor:pointer">⚙ PUNKTE</button>
      <?php endif; ?>
      <button onclick="kShowUpload()" style="background:rgba(255,96,0,.15);border:1px solid var(--orange);color:var(--orange);font-family:var(--mono);font-size:11px;padding:6px 14px;border-radius:4px;cursor:pointer;letter-spacing:.06em">
        ↑ HOCHLADEN (+10 Punkte)
      </button>
    </div>
  </div>

  <!-- Rangliste -->
  <div id="kRanking" style="margin-bottom:20px;background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:14px 18px">
    <div style="font-family:var(--mono);font-size:11px;color:var(--text-dim);margin-bottom:10px;letter-spacing:.06em">🏆 RANGLISTE</div>
    <div id="kRankList" style="display:flex;gap:8px;flex-wrap:wrap">
      <span style="color:var(--text-dim);font-size:12px">Lädt…</span>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
    <input type="text" id="kSearch" placeholder="Suchen…" oninput="kApplyFilter()"
      style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:7px 12px;color:var(--text);font-size:13px;outline:none;min-width:200px">
    <?php foreach([
      ['','Alle'],['program','Programm'],['snippet','Snippet'],
      ['function','Funktion'],['submit','Submit'],['interrupt','Interrupt'],['cell','Cell']
    ] as [$v,$l]): ?>
      <button class="kfl-btn" data-cat="<?=$v?>" onclick="kSetFilter('<?=$v?>')"
        style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:5px 12px;color:var(--text-dim);font-size:12px;cursor:pointer;font-family:var(--mono)">
        <?=$l?>
      </button>
    <?php endforeach; ?>
  </div>

  <div id="kGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px"></div>
  <div id="kEmpty" style="display:none;text-align:center;padding:60px;color:var(--text-dim);font-size:14px">
    Noch keine Programme — lade das erste hoch!
  </div>
  <div id="kLoading" style="text-align:center;padding:40px;color:var(--text-dim)">Lädt…</div>
</div>

<!-- Upload Modal -->
<div id="kUploadModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);align-items:center;justify-content:center;z-index:300" onclick="if(event.target===this)this.style.display='none'">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:28px;width:520px;max-width:95vw;max-height:90vh;overflow-y:auto">
    <h2 style="font-family:var(--mono);color:var(--orange);margin-bottom:20px;font-size:15px">KRL HOCHLADEN</h2>

    <label style="display:block;font-size:11px;color:var(--text-dim);margin-bottom:4px;font-family:var(--mono)">TITEL *</label>
    <input id="kUpName" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:8px 10px;color:var(--text);font-size:13px;outline:none;margin-bottom:12px" placeholder="z.B. Schweißen Start-Sequenz">

    <label style="display:block;font-size:11px;color:var(--text-dim);margin-bottom:4px;font-family:var(--mono)">KATEGORIE</label>
    <select id="kUpCat" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:8px 10px;color:var(--text);font-size:13px;outline:none;margin-bottom:12px">
      <option value="program">Programm (SRC+DAT)</option>
      <option value="snippet">Snippet / Codefragment</option>
      <option value="function">Funktion (DEFFCT)</option>
      <option value="submit">Submit-Programm</option>
      <option value="interrupt">Interrupt-Handler</option>
      <option value="cell">Cell-Programm</option>
    </select>

    <label style="display:block;font-size:11px;color:var(--text-dim);margin-bottom:4px;font-family:var(--mono)">BESCHREIBUNG</label>
    <input id="kUpDesc" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:8px 10px;color:var(--text);font-size:13px;outline:none;margin-bottom:12px" placeholder="Was macht dieses Programm?">

    <label style="display:block;font-size:11px;color:var(--text-dim);margin-bottom:4px;font-family:var(--mono)">TAGS (kommagetrennt)</label>
    <input id="kUpTags" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:8px 10px;color:var(--text);font-size:13px;outline:none;margin-bottom:12px" placeholder="schweissen, krc4, lin">

    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:16px;padding:10px;background:var(--bg3);border:1px solid var(--border);border-radius:4px">
      <input type="checkbox" id="kUpPrivate" style="margin-top:2px;flex-shrink:0">
      <div>
        <div style="font-size:12px;color:var(--text);font-weight:600">🔒 Privat speichern</div>
        <div style="font-size:11px;color:var(--text-dim);margin-top:2px">Nur für dich sichtbar. Kostet <strong style="color:#F88">5 Punkte</strong> statt +10.</div>
      </div>
    </label>

    <label style="display:block;font-size:11px;color:var(--text-dim);margin-bottom:4px;font-family:var(--mono)">DATEINAME *</label>
    <input id="kUpFilename" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:8px 10px;color:var(--text);font-size:13px;outline:none;margin-bottom:12px" placeholder="program.src">

    <label style="display:block;font-size:11px;color:var(--text-dim);margin-bottom:4px;font-family:var(--mono)">KRL-CODE *</label>
    <textarea id="kUpContent" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:8px 10px;color:var(--text);font-size:12px;outline:none;min-height:150px;resize:vertical;font-family:monospace;margin-bottom:12px" placeholder="&ACCESS RVP&#10;&REL 1&#10;DEF MeinProgramm()&#10;..."></textarea>

    <div id="kUpMsg"></div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
      <button onclick="document.getElementById('kUploadModal').style.display='none'"
        style="background:none;border:1px solid var(--border);color:var(--text-dim);padding:7px 16px;border-radius:4px;cursor:pointer;font-size:13px">Abbrechen</button>
      <button onclick="kDoUpload()"
        style="background:var(--orange);color:#000;border:none;padding:7px 16px;border-radius:4px;cursor:pointer;font-size:13px;font-weight:700;font-family:var(--mono)">
        ↑ HOCHLADEN
      </button>
    </div>
  </div>
</div>

<div id="kToast" style="display:none;position:fixed;bottom:24px;right:24px;background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:12px 18px;font-size:13px;z-index:400;color:var(--text)"></div>

<style>
.kfl-btn.active { border-color:var(--orange)!important;color:var(--orange)!important; }
.kcard { background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:16px;display:flex;flex-direction:column;gap:10px;transition:border-color .15s;position:relative; }
.kcard:hover { border-color:#334; }
.kpreview { display:none;position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:50;background:#0A1A0A;border:1px solid #2A5A2A;border-radius:6px;padding:12px;box-shadow:0 8px 24px rgba(0,0,0,.6); }
.kcard:hover .kpreview { display:block; }
.kcat { font-size:10px;font-weight:700;border-radius:3px;padding:2px 7px;text-transform:uppercase;flex-shrink:0 }
.kcat-program   { background:#F0A03022;color:#F0A030;border:1px solid #F0A03044 }
.kcat-snippet   { background:#569CD622;color:#569CD6;border:1px solid #569CD644 }
.kcat-function  { background:#C586C022;color:#C586C0;border:1px solid #C586C044 }
.kcat-submit    { background:#4EC9B022;color:#4EC9B0;border:1px solid #4EC9B044 }
.kcat-interrupt { background:#F8888822;color:#F88;border:1px solid #F8888844 }
.kcat-cell      { background:#888;color:#000 }
</style>

<script>
const KAPI = 'api.php';
let kUser = '', kFilter = '', kPrograms = [];

(function init() {
  // User aus localStorage (vom KRL-Editor gesetzt) oder URL-Parameter
  kUser = new URLSearchParams(location.search).get('krl_user')
       || localStorage.getItem('krl_community_user') || '';
  if (kUser) {
    localStorage.setItem('krl_community_user', kUser);
    document.getElementById('kUserLabel').textContent = '👤 ' + kUser;
    kLoadPoints();
  }
  kLoadList();
  kLoadLeaderboard();
})();

async function kLoadPoints() {
  if (!kUser) return;
  try {
    const d = await (await fetch(`${KAPI}?action=krl_points&user=${encodeURIComponent(kUser)}`)).json();
    if (d.ok) {
      document.getElementById('kStatPoints').textContent  = d.points;
      document.getElementById('kStatUploads').textContent = d.uploads ?? 0;
    }
  } catch(e){}
}

async function kLoadLeaderboard() {
  try {
    const d = await (await fetch(`${KAPI}?action=krl_leaderboard`)).json();
    if (!d.ok) return;
    const medals = ['🥇','🥈','🥉'];
    document.getElementById('kRankList').innerHTML = d.leaderboard.map((u,i) => {
      const isMe = u.user === kUser;
      return `<div style="display:flex;align-items:center;gap:6px;background:${isMe?'rgba(255,96,0,.12)':'var(--bg3)'};border:1px solid ${isMe?'rgba(255,96,0,.4)':'var(--border)'};border-radius:4px;padding:5px 10px;font-size:12px">
        <span style="font-size:14px">${medals[i]||'#'+(i+1)}</span>
        <span style="color:${isMe?'var(--orange)':'var(--text)'};font-weight:${isMe?700:400}">${u.user}</span>
        <span style="color:var(--orange);font-family:var(--mono);font-size:11px">★ ${u.points}</span>
      </div>`;
    }).join('') || '<span style="color:var(--text-dim);font-size:12px">Noch keine Einträge</span>';
  } catch(e) {}
}

async function kLoadList() {
  document.getElementById('kLoading').style.display = '';
  document.getElementById('kGrid').innerHTML = '';
  try {
    const q = encodeURIComponent(document.getElementById('kSearch').value);
    const r = await fetch(`${KAPI}?action=krl_list${kFilter?'&cat='+kFilter:''}${q?'&q='+q:''}`);
    const d = await r.json();
    kPrograms = d.programs ?? [];
    document.getElementById('kStatTotal').textContent = d.total ?? kPrograms.length;
    kRender(kPrograms);
  } catch(e) {
    document.getElementById('kEmpty').textContent = 'Fehler beim Laden';
    document.getElementById('kEmpty').style.display = '';
  }
  document.getElementById('kLoading').style.display = 'none';
}

function kRender(progs) {
  const grid = document.getElementById('kGrid');
  grid.innerHTML = '';
  document.getElementById('kEmpty').style.display = progs.length ? 'none' : '';
  progs.forEach(p => grid.appendChild(kMakeCard(p)));
}

const kCatLabel = {program:'Programm',snippet:'Snippet',function:'Funktion',submit:'Submit',interrupt:'Interrupt',cell:'Cell'};

function kMakeCard(p) {
  const div = document.createElement('div');
  div.className = 'kcard';
  const tags = (p.tags||'').split(',').map(t=>t.trim()).filter(Boolean)
    .map(t=>`<span style="background:var(--bg3);border:1px solid var(--border);border-radius:3px;padding:1px 7px;font-size:10px;color:var(--text-dim)">${t}</span>`).join('');
  const cost  = kUser && kUser!==p.author ? '<span style="color:var(--text-dim);font-size:11px">−20 Pkt</span>' : '';
  const own   = kUser===p.author ? '<span style="color:#4EC9B0;font-size:10px">✓ Eigenes Upload</span>' : '';
  const priv  = p.private ? '<span style="color:#F0A030;font-size:10px">🔒 Privat</span>' : '';
  const delBt = (kUser===p.author || kIsAdmin)
    ? `<button onclick="kDelete('${p.id}')" style="margin-left:auto;background:none;border:1px solid var(--border);color:var(--text-dim);padding:3px 8px;border-radius:3px;cursor:pointer;font-size:11px">🗑</button>` : '';
  const dt = p.date ? new Date(p.date*1000).toLocaleDateString('de-DE') : '';
  const preview = (p.preview||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  div.style.position = 'relative';
  div.innerHTML = `
    ${preview ? `<div class="kpreview"><pre style="margin:0;font-size:10px;line-height:1.4;white-space:pre-wrap;color:#4EC9B0;max-height:200px;overflow:hidden">${preview}</pre></div>` : ''}
    <div style="display:flex;align-items:flex-start;gap:8px">
      <span class="kcat kcat-${p.category}">${kCatLabel[p.category]||p.category}</span>
      <span style="font-weight:600;font-size:14px;color:var(--text-bright);flex:1">${p.name}</span>
    </div>
    ${p.description?`<div style="font-size:12px;color:var(--text-dim);line-height:1.5">${p.description}</div>`:''}
    ${tags?`<div style="display:flex;gap:4px;flex-wrap:wrap">${tags}</div>`:''}
    <div style="display:flex;gap:10px;font-size:11px;color:var(--text-dim);flex-wrap:wrap;align-items:center">
      <span>📄 ${p.filename}</span>
      <span style="color:var(--blue)">👤 ${p.author}</span>
      <span>📥 ${p.downloads}</span>
      <span>${dt}</span>
      ${own}${priv}
    </div>
    <div style="display:flex;gap:8px;margin-top:4px;align-items:center">
      <button onclick="kLike('${p.id}',this)" style="background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:12px;padding:4px 6px;border-radius:3px">♥ ${p.likes||0}</button>
      ${cost}
      <button onclick="kDownload('${p.id}','${p.name}','${p.filename}')"
        style="margin-left:auto;background:var(--orange);color:#000;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:700;font-family:var(--mono)">
        ↓ IN EDITOR LADEN
      </button>
      ${delBt}
    </div>`;
  return div;
}

function kSetFilter(cat) {
  kFilter = cat;
  document.querySelectorAll('.kfl-btn').forEach(b => b.classList.toggle('active', b.dataset.cat===cat));
  kLoadList();
}

function kApplyFilter() {
  clearTimeout(window._kt);
  window._kt = setTimeout(kLoadList, 300);
}

async function kDownload(id, name, filename) {
  if (!kUser) { alert('Bitte zuerst im KRL Editor anmelden, dann über den Community-Button hierher kommen.'); return; }
  try {
    const r = await fetch(`${KAPI}?action=krl_download&id=${id}&user=${encodeURIComponent(kUser)}`);
    const d = await r.json();
    if (!d.ok) { kToast('❌ ' + (d.error||'Fehler')); return; }
    localStorage.setItem('krl_community_load', JSON.stringify({ name: d.filename, content: d.content, from: d.name }));
    if (d.points!==null && d.points!==undefined) document.getElementById('kStatPoints').textContent = d.points;
    kToast(`✓ "${name}" bereit — wechsle zum KRL Editor`);
    if (confirm(`"${name}" wurde geladen.\n\nJetzt zum KRL Editor wechseln?`))
      window.location.href = 'https://cnc-technik.de/kuka/';
  } catch(e) { kToast('❌ Netzwerkfehler'); }
}

function kShowUpload() {
  if (!kUser) { alert('Bitte zuerst im KRL Editor anmelden.'); return; }
  document.getElementById('kUploadModal').style.display = 'flex';
}

async function kDoUpload() {
  const name     = document.getElementById('kUpName').value.trim();
  const category = document.getElementById('kUpCat').value;
  const desc     = document.getElementById('kUpDesc').value.trim();
  const tags     = document.getElementById('kUpTags').value.trim();
  const filename = document.getElementById('kUpFilename').value.trim();
  const content  = document.getElementById('kUpContent').value.trim();
  const msg      = document.getElementById('kUpMsg');
  if (!name||!filename||!content) { msg.innerHTML='<div style="background:#2A1A1A;border:1px solid #5A2A2A;padding:8px 12px;border-radius:4px;color:#F88;font-size:12px">Pflichtfelder ausfüllen</div>'; return; }
  try {
    const r = await fetch(`${KAPI}?action=krl_upload`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({user:kUser, name, description:desc, category, filename, content, tags,
        private: document.getElementById('kUpPrivate').checked})
    });
    const d = await r.json();
    if (d.ok) {
      const ptsTxt = d.private ? `Privat gespeichert. Du hast jetzt ${d.points} Punkte.` : `Hochgeladen! Du hast jetzt ${d.points} Punkte.`;
      msg.innerHTML = `<div style="background:#1A2A1A;border:1px solid #2A5A2A;padding:8px 12px;border-radius:4px;color:#4EC9B0;font-size:12px">✓ ${ptsTxt}</div>`;
      document.getElementById('kStatPoints').textContent = d.points;
      setTimeout(() => { document.getElementById('kUploadModal').style.display='none'; kLoadList(); }, 1500);
    } else {
      msg.innerHTML = `<div style="background:#2A1A1A;border:1px solid #5A2A2A;padding:8px 12px;border-radius:4px;color:#F88;font-size:12px">❌ ${d.error}</div>`;
    }
  } catch(e) { msg.innerHTML = '<div style="color:#F88;font-size:12px">❌ Netzwerkfehler</div>'; }
}

async function kLike(id, btn) {
  await fetch(`${KAPI}?action=krl_like&id=${id}`);
  btn.textContent = '♥ ' + (parseInt(btn.textContent.replace('♥ ',''))+1);
}

async function kDelete(id) {
  if (!confirm('Programm wirklich löschen?')) return;
  const adminParam = kIsAdmin ? `&admin_pass=${encodeURIComponent(KAPI_ADMIN_PASS)}` : '';
  const r = await fetch(`${KAPI}?action=krl_delete&id=${id}&user=${encodeURIComponent(kUser)}${adminParam}`);
  const d = await r.json();
  d.ok ? (kToast('✓ Gelöscht'), kLoadList()) : kToast('❌ ' + (d.error||'Fehler'));
}

function kToast(msg, dur=3500) {
  const t = document.getElementById('kToast');
  t.textContent = msg; t.style.display = '';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.display='none', dur);
}
</script>

<!-- Theme + Login: immer verfügbar (auch im KRL-Tab) -->
<style id="rl-theme-style"></style>
<script>
(function(){
  var themes=[
    {vars:{'--bg':'#09131c','--bg2':'#0d1a26','--bg3':'#0f2030','--card':'#0d1e2e','--card-hover':'#112435','--border':'#1a3348','--text':'#d8e8f0','--text-dim':'#6a8fa8','--text-bright':'#ffffff'}},
    {vars:{'--bg':'#1e1e1e','--bg2':'#252526','--bg3':'#2d2d30','--card':'#252526','--card-hover':'#2a2a2c','--border':'#3e3e42','--text':'#d4d4d4','--text-dim':'#808080','--text-bright':'#ffffff'}},
    {vars:{'--bg':'#f5f5f0','--bg2':'#eaeae5','--bg3':'#ddddd8','--card':'#eaeae5','--card-hover':'#e0e0da','--border':'#b8b8b2','--text':'#1a2a3a','--text-dim':'#4a6a8a','--text-bright':'#000000'}},
    {vars:{'--bg':'#000408','--bg2':'#040c14','--bg3':'#081420','--card':'#040c14','--card-hover':'#081420','--border':'#0a2030','--text':'#a0c8e0','--text-dim':'#3a6080','--text-bright':'#ffffff'}},
  ];
  var idx=parseInt(localStorage.getItem('rl-theme')||'0');
  function applyTheme(i){
    var t=themes[i]||themes[0];
    document.getElementById('rl-theme-style').textContent='body{'+Object.entries(t.vars).map(function(e){return e[0]+':'+e[1]}).join(';')+'}';
    var btn=document.getElementById('rlThemeBtn');
    if(btn)btn.textContent='◑';
    localStorage.setItem('rl-theme',i); idx=i;
  }
  window.rlToggleTheme=function(){applyTheme((idx+1)%themes.length);};
  document.addEventListener('DOMContentLoaded',function(){applyTheme(idx);});
})();
</script>
<div id="loginModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#0d1a26;border:1px solid var(--orange);border-radius:8px;width:min(320px,92vw);padding:24px">
    <div style="font-family:var(--mono);font-size:13px;color:var(--orange);margin-bottom:18px;letter-spacing:.06em">&#x25A0; ADMIN LOGIN</div>
    <form method="post" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="action" value="login">
      <input type="text" name="user" placeholder="Benutzer" autocomplete="username" style="background:#0f2030;border:1px solid rgba(255,96,0,.4);border-radius:4px;padding:8px 10px;color:#d8e8f0;font-family:var(--mono);font-size:13px;outline:none">
      <input type="password" name="pass" placeholder="Passwort" autocomplete="current-password" style="background:#0f2030;border:1px solid rgba(255,96,0,.4);border-radius:4px;padding:8px 10px;color:#d8e8f0;font-family:var(--mono);font-size:13px;outline:none">
      <div style="display:flex;gap:8px;margin-top:4px">
        <button type="submit" style="flex:1;padding:9px;background:var(--orange);color:#000;border:none;border-radius:4px;font-family:var(--mono);font-size:12px;font-weight:700;cursor:pointer">ANMELDEN</button>
        <button type="button" onclick="document.getElementById('loginModal').style.display='none'" style="padding:9px 14px;background:none;border:1px solid rgba(255,255,255,.2);color:#6a8fa8;border-radius:4px;font-family:var(--mono);font-size:12px;cursor:pointer">✕</button>
      </div>
    </form>
  </div>
</div>

<!-- Admin Punkte-Verwaltung Modal -->
<div id="kPtsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);align-items:center;justify-content:center;z-index:300" onclick="if(event.target===this)this.style.display='none'">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:24px;width:480px;max-width:95vw;max-height:85vh;display:flex;flex-direction:column">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <span style="font-family:var(--mono);font-size:14px;color:var(--orange);font-weight:700">⚙ PUNKTE VERWALTEN</span>
      <button onclick="document.getElementById('kPtsModal').style.display='none'" style="background:none;border:none;color:var(--text-dim);font-size:20px;cursor:pointer">×</button>
    </div>
    <div style="overflow-y:auto;flex:1">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="color:var(--text-dim);font-size:11px;font-family:var(--mono)">
          <th style="text-align:left;padding:6px 8px;border-bottom:1px solid var(--border)">BENUTZER</th>
          <th style="text-align:center;padding:6px 8px;border-bottom:1px solid var(--border)">PUNKTE</th>
          <th style="text-align:center;padding:6px 8px;border-bottom:1px solid var(--border)">UPLOADS</th>
          <th style="text-align:center;padding:6px 8px;border-bottom:1px solid var(--border)">DOWNLOADS</th>
          <th style="padding:6px 8px;border-bottom:1px solid var(--border)"></th>
        </tr></thead>
        <tbody id="kPtsBody"><tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-dim)">Lädt…</td></tr></tbody>
      </table>
    </div>
    <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:center">
      <input id="kPtsUser" placeholder="Benutzername" style="flex:1;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px 10px;color:var(--text);font-size:13px;outline:none">
      <input id="kPtsVal" type="number" min="0" placeholder="Punkte" style="width:90px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px 10px;color:var(--text);font-size:13px;outline:none">
      <button onclick="kSetPoints()" style="background:var(--orange);color:#000;border:none;padding:7px 14px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:700;font-family:var(--mono)">SETZEN</button>
    </div>
    <div id="kPtsMsg" style="font-size:11px;margin-top:6px"></div>
  </div>
</div>

<script>
const KAPI_ADMIN_PASS = '<?= addslashes(ROBLIB_USERS['admin'] ?? '') ?>';
const kIsAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

async function kShowPointsAdmin() {
  document.getElementById('kPtsModal').style.display = 'flex';
  const r = await fetch(`${KAPI}?action=krl_users&admin_user=admin&admin_pass=${encodeURIComponent(KAPI_ADMIN_PASS)}`);
  const d = await r.json();
  if (!d.ok) { document.getElementById('kPtsBody').innerHTML=`<tr><td colspan="5" style="color:#F88;padding:12px">${d.error}</td></tr>`; return; }
  document.getElementById('kPtsBody').innerHTML = d.users.map(u => `
    <tr style="border-bottom:1px solid var(--border)">
      <td style="padding:8px;color:var(--text)">${u.user}</td>
      <td style="padding:8px;text-align:center;color:var(--orange);font-weight:700">${u.points}</td>
      <td style="padding:8px;text-align:center;color:var(--text-dim)">${u.uploads}</td>
      <td style="padding:8px;text-align:center;color:var(--text-dim)">${u.downloads}</td>
      <td style="padding:8px"><button onclick="document.getElementById('kPtsUser').value='${u.user}';document.getElementById('kPtsVal').value=${u.points}" style="background:none;border:1px solid var(--border);color:var(--text-dim);padding:2px 8px;border-radius:3px;cursor:pointer;font-size:11px">✎</button></td>
    </tr>`).join('');
}
async function kSetPoints() {
  const user = document.getElementById('kPtsUser').value.trim();
  const pts  = parseInt(document.getElementById('kPtsVal').value);
  const msg  = document.getElementById('kPtsMsg');
  if (!user || isNaN(pts)) { msg.style.color='#F88'; msg.textContent='Benutzer und Punkte eingeben'; return; }
  const r = await fetch(`${KAPI}?action=krl_set_points`, {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`user=${encodeURIComponent(user)}&points=${pts}&admin_user=admin&admin_pass=${encodeURIComponent(KAPI_ADMIN_PASS)}`,
  });
  const d = await r.json();
  if (d.ok) { msg.style.color='#4EC9B0'; msg.textContent=`✓ ${user}: ${d.points} Punkte gesetzt`; kShowPointsAdmin(); }
  else       { msg.style.color='#F88';    msg.textContent=`❌ ${d.error}`; }
}
</script>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!--  EXISTING ROBOT LIBRARY (unchanged)                                        -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="hero">
  <h1>ROBOTERBIBLIOTHEK</h1>
  <p>
    <span class="hero-count"><?= count($robots) ?></span> Modelle verfügbar · 
    In RobModel erstellt · Per RobSimul ladbar
  </p>
</div>


<div style="max-width:1400px;margin:0 auto;padding:0 24px">
<div id="typeTabs" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px">
  <button class="type-tab active" data-type="robot">🦾 Roboter <span class="type-cnt"><?= count($robots_r) ?></span></button>
  <button class="type-tab" data-type="endeffektor">🔧 Endeffektoren <span class="type-cnt"><?= count($robots_eff) ?></span></button>
  <button class="type-tab" data-type="umfeld">🏭 Umfeld <span class="type-cnt"><?= count($robots_umf) ?></span></button>
  <button class="type-tab" data-type="positioner">🔄 Positionierer <span class="type-cnt"><?= count($robots_pos) ?></span></button>
  <button class="type-tab" data-type="object">📦 Bewegl. Obj. <span class="type-cnt"><?= count($robots_obj) ?></span></button>
  <button class="type-tab" data-type="fixture">🧱 Feste Obj. <span class="type-cnt"><?= count($robots_fix) ?></span></button>
  <button class="type-tab" data-type="station">🏗️ Stationen <span class="type-cnt"><?= count($robots_sta) ?></span></button>
  <button class="type-tab" data-type="rail">🛤️ Schienen <span class="type-cnt"><?= count($robots_rail) ?></span></button>
</div>
</div>
<div class="filter-bar">
  <button id="btnNeu" onclick="rlOpenUploadModal()"
    style="background:rgba(255,96,0,.15);border:1px solid var(--orange);color:var(--orange);font-family:var(--mono);font-size:11px;padding:5px 12px;border-radius:4px;cursor:pointer;letter-spacing:.06em;white-space:nowrap">
    + NEU
  </button>
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
<div id="emptyState" class="empty-state" style="display:none;grid-column:1/-1">
    <div class="icon">⬡</div>
    Keine Einträge in dieser Kategorie.
  </div>
<?php if (empty($robots)): ?>
  <div class="empty-state">
    <div class="icon">⬡</div>
    Noch keine Einträge in der Bibliothek.<br>
    <a href="manage.php" style="color:var(--orange)">Ersten Eintrag hochladen →</a>
  </div>
<?php else: ?>
  <?php foreach ($robots as $r): ?>
  <?php
    $rtype = $r['type'] ?? 'robot';
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
    $beschr = htmlspecialchars($r['beschreibung'] ?? '');
  ?>
  <div class="card"
       data-type="<?= $rtype ?>"
       data-name="<?= strtolower($name . ' ' . $marke . ' ' . $mod) ?>"
       data-marke="<?= strtolower(htmlspecialchars($r['marke'] ?? '')) ?>"
       data-achsen="<?= $achsen ?>">

    <div class="card-thumb">
      <?php if ($thumb): ?>
        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= $name ?>">
      <?php else: ?>
        <div class="no-thumb">
          <div style="font-size:40px;opacity:.3"><?= $rtype==='endeffektor'?'🔧':($rtype==='umfeld'?'🏭':'⬡') ?></div>
          KEIN BILD
        </div>
      <?php endif; ?>
    </div>

    <div class="card-body">
      <div class="card-name">
        <span style="font-size:.7em;opacity:.6;margin-right:4px"><?php
          $icon = match($rtype) { 'robot'=>'🦾','endeffektor'=>'🔧','umfeld'=>'🏭','positioner'=>'🔄','rail'=>'🛤️','fixture'=>'🧱','station'=>'🏗️',default=>'📦' };
          echo $icon; ?></span>
        <?= $name ?>
      </div>
      <table class="card-specs">
        <?php if ($marke): ?><tr><td>Marke</td><td><?= $marke ?></td></tr><?php endif; ?>
        <?php if ($mod):   ?><tr><td>Modell</td><td><?= $mod ?></td></tr><?php endif; ?>
        <?php if ($rtype === 'robot'): ?>
        <tr><td>Achsen</td>      <td><?= $achsen ?></td></tr>
        <tr><td>Reichweite</td>  <td><?= $rw ?> mm</td></tr>
        <tr><td>Nutzlast</td>    <td><?= $nl ?> kg</td></tr>
        <tr><td>Gewicht</td>     <td><?= $gw ?> kg</td></tr>
        <tr><td>Wiederholgen.</td><td><?= $wg ?> mm</td></tr>
        <?php endif; ?>
        <?php if ($beschr): ?><tr><td colspan="2" style="color:#6a8fa8;font-size:11px;padding-top:4px"><?= $beschr ?></td></tr><?php endif; ?>
      </table>
    </div>

    <div class="card-footer">

      <?php if($isAdmin):?>
        <button class="btn-edit"
          data-id="<?php echo htmlspecialchars($r['id'],ENT_QUOTES);?>"
          data-type="<?php echo htmlspecialchars($rtype,ENT_QUOTES);?>"
          data-name="<?php echo htmlspecialchars($r['name'],ENT_QUOTES);?>"
          data-marke="<?php echo htmlspecialchars($r['marke'],ENT_QUOTES);?>"
          data-modell="<?php echo htmlspecialchars($r['modell'],ENT_QUOTES);?>"
          data-achsen="<?php echo intval($r['achsen']);?>"
          data-reichweite="<?php echo intval($r['reichweite_mm']);?>"
          data-nutzlast="<?php echo floatval($r['nutzlast_kg']);?>"
          data-gewicht="<?php echo floatval($r['gewicht_kg']);?>"
          data-wdh="<?php echo floatval($r['wiederholgenauigkeit_mm']);?>"
          data-beschreibung="<?php echo htmlspecialchars($r['beschreibung']??'',ENT_QUOTES);?>"
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
    const matchType = (c.dataset.type||'robot') === _currentType;
    const matchQ = !matchType ? false : (!q || c.dataset.name.includes(q));
    const matchM = !m || c.dataset.marke === m;
    const matchA = !a || c.dataset.achsen === a;
    const show   = matchQ && matchM && matchA;
    c.style.display = show ? '' : 'none';
    if (show) vis++;
  });
  count.textContent = vis + ' / ' + cards.length + ' angezeigt';
  var empty = document.getElementById('emptyState');
  if (empty) empty.style.display = vis === 0 ? '' : 'none';
}

search.addEventListener('input',  filterCards);

// Tab-System — direkte Listener pro Button
var _currentType = 'robot';
document.querySelectorAll('#typeTabs .type-tab').forEach(function(btn) {
  btn.style.cursor = 'pointer';
  btn.onclick = function() {
    _currentType = btn.dataset.type;
    document.querySelectorAll('#typeTabs .type-tab').forEach(function(b) {
      b.classList.toggle('active', b === btn);
    });
    filterCards();
  };
});
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
  var rtype = btn.getAttribute('data-type') || 'robot';
  document.getElementById('rl-name').value      = btn.getAttribute('data-name');
  document.getElementById('rl-marke').value     = btn.getAttribute('data-marke');
  document.getElementById('rl-modell').value    = btn.getAttribute('data-modell');
  document.getElementById('rl-achsen').value    = btn.getAttribute('data-achsen');
  document.getElementById('rl-reichweite').value= btn.getAttribute('data-reichweite');
  document.getElementById('rl-nutzlast').value  = btn.getAttribute('data-nutzlast');
  document.getElementById('rl-gewicht').value   = btn.getAttribute('data-gewicht');
  document.getElementById('rl-wdh').value       = btn.getAttribute('data-wdh');
  document.getElementById('rl-beschreibung').value = btn.getAttribute('data-beschreibung') || '';
  var robotFields = document.getElementById('rl-robot-fields');
  if (robotFields) robotFields.style.display = rtype === 'robot' ? 'contents' : 'none';
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
  fd.append('beschreibung', document.getElementById('rl-beschreibung').value);
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
      <span>&#x270E; Bearbeiten</span>
      <button class="rl-close" onclick="document.getElementById('rlModal').style.display='none'">&#x2715;</button>
    </div>
    <div class="rl-modal-body">
      <div id="rlMsg" class="rl-msg" style="display:none"></div>
      <div class="rl-grid">
        <label class="rl-span2">Name<input id="rl-name" type="text"></label>
        <label>Marke<input id="rl-marke" type="text"></label>
        <label>Modell<input id="rl-modell" type="text"></label>
        <div id="rl-robot-fields" style="display:contents">
          <label>Achsen<input id="rl-achsen" type="number" min="1" max="9"></label>
          <label>Reichweite (mm)<input id="rl-reichweite" type="number"></label>
          <label>Nutzlast (kg)<input id="rl-nutzlast" type="number" step="0.1"></label>
          <label>Gewicht (kg)<input id="rl-gewicht" type="number" step="0.1"></label>
          <label>Wiederholgenaui. (mm)<input id="rl-wdh" type="number" step="0.001"></label>
        </div>
        <label class="rl-span2">Beschreibung
          <textarea id="rl-beschreibung" rows="2" style="background:#0f2030;border:1px solid rgba(255,255,255,.15);border-radius:4px;padding:6px 8px;color:#d8e8f0;font-family:monospace;font-size:12px;outline:none;width:100%;margin-top:4px;resize:vertical"></textarea>
        </label>
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

<style id="rl-theme-style"></style>
<script>
(function(){
  var themes = [
    { name:'dark',       icon:'◑', vars:{'--bg':'#09131c','--bg2':'#0d1a26','--bg3':'#0f2030','--card':'#0d1e2e','--card-hover':'#112435','--border':'#1a3348','--text':'#d8e8f0','--text-dim':'#6a8fa8','--text-bright':'#ffffff'}},
    { name:'bg-pro',     icon:'◑', vars:{'--bg':'#1e1e1e','--bg2':'#252526','--bg3':'#2d2d30','--card':'#252526','--card-hover':'#2a2a2c','--border':'#3e3e42','--text':'#d4d4d4','--text-dim':'#808080','--text-bright':'#ffffff'}},
    { name:'bg-white',   icon:'◑', vars:{'--bg':'#f5f5f0','--bg2':'#eaeae5','--bg3':'#ddddd8','--card':'#eaeae5','--card-hover':'#e0e0da','--border':'#b8b8b2','--text':'#1a2a3a','--text-dim':'#4a6a8a','--text-bright':'#000000'}},
    { name:'bg-minimal', icon:'◑', vars:{'--bg':'#f4f4f4','--bg2':'#ebebeb','--bg3':'#e0e0e0','--card':'#ebebeb','--card-hover':'#e4e4e4','--border':'#b0b0b0','--text':'#111111','--text-dim':'#666666','--text-bright':'#000000'}},
    { name:'bg-win11',   icon:'◑', vars:{'--bg':'#f3f6fc','--bg2':'#e8eef8','--bg3':'#dde5f4','--card':'#e8eef8','--card-hover':'#dce5f2','--border':'#c8d8e8','--text':'#1a2a3a','--text-dim':'#4a6a8a','--text-bright':'#000000'}},
    { name:'bg-deep',    icon:'◑', vars:{'--bg':'#000408','--bg2':'#040c14','--bg3':'#081420','--card':'#040c14','--card-hover':'#081420','--border':'#0a2030','--text':'#a0c8e0','--text-dim':'#3a6080','--text-bright':'#ffffff'}},
    { name:'bg-vivid',   icon:'◑', vars:{'--bg':'#1a0a2e','--bg2':'#22103c','--bg3':'#2a1848','--card':'#22103c','--card-hover':'#2a1848','--border':'#3a2060','--text':'#e0c8f8','--text-dim':'#8060a0','--text-bright':'#ffffff'}},
    { name:'bg-matrix',  icon:'◑', vars:{'--bg':'#000800','--bg2':'#001400','--bg3':'#001c00','--card':'#001400','--card-hover':'#001c00','--border':'#003000','--text':'#00dd44','--text-dim':'#006622','--text-bright':'#00ff66'}},
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

<!-- ── Upload Modal ─────────────────────────────────────────────── -->
<div id="uploadModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:10px;width:min(500px,96vw);padding:24px;font-family:var(--mono)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <span style="font-size:14px;font-weight:700;color:var(--orange);letter-spacing:.1em">+ NEU HOCHLADEN</span>
      <button onclick="document.getElementById('uploadModal').style.display='none'" style="background:none;border:none;color:var(--text-dim);font-size:18px;cursor:pointer">✕</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
      <label style="grid-column:1/-1;font-size:10px;color:var(--text-dim);letter-spacing:.08em">TYP
        <select id="um-type" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:12px">
          <option value="robot">🦾 Roboter (nur via RobModel)</option>
          <option value="endeffektor">🔧 Endeffektor</option>
          <option value="umfeld">🏭 Umfeld / Umgebung</option>
          <option value="positioner">🔄 Rotationstisch / Positionierer</option>
          <option value="object">📦 Bewegliches Objekt</option>
          <option value="fixture">🧱 Festes Objekt</option>
          <option value="station">🏗️ Station</option>
          <option value="rail">🛤️ Schiene / Rail</option>
        </select>
      </label>
      <label style="font-size:10px;color:var(--text-dim);letter-spacing:.08em">NAME *
        <input id="um-name" type="text" placeholder="Greifer 2F-85" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:12px">
      </label>
      <label style="font-size:10px;color:var(--text-dim);letter-spacing:.08em">MARKE
        <input id="um-marke" type="text" placeholder="Robotiq" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:12px">
      </label>
      <label style="font-size:10px;color:var(--text-dim);letter-spacing:.08em">MODELL
        <input id="um-modell" type="text" placeholder="2F-85" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:12px">
      </label>
      <label style="grid-column:1/-1;font-size:10px;color:var(--text-dim);letter-spacing:.08em">STL-DATEI(EN)
        <input id="um-stl" type="file" accept=".stl" multiple style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:11px">
      </label>
      <label style="grid-column:1/-1;font-size:10px;color:var(--text-dim);letter-spacing:.08em">THUMBNAIL (optional)
        <input id="um-thumb" type="file" accept="image/*" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:11px">
      </label>
      <label style="font-size:10px;color:var(--text-dim);letter-spacing:.08em">BENUTZER *
        <input id="um-user" type="text" value="admin" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:12px">
      </label>
      <label style="font-size:10px;color:var(--text-dim);letter-spacing:.08em">PASSWORT *
        <input id="um-pass" type="password" style="display:block;width:100%;margin-top:3px;background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:6px;color:var(--text);font-family:var(--mono);font-size:12px">
      </label>
    </div>
    <div id="um-progress" style="display:none;margin-bottom:10px">
      <div style="background:rgba(255,255,255,.08);border-radius:4px;height:6px;overflow:hidden">
        <div id="um-bar" style="height:100%;width:0%;background:#ff6000;border-radius:4px;transition:width .2s"></div>
      </div>
    </div>
    <div id="um-msg" style="display:none;padding:6px 10px;border-radius:4px;font-size:11px;margin-bottom:10px"></div>
    <button id="um-submit" onclick="umDoUpload()"
      style="width:100%;padding:10px;background:#ff6000;color:#fff;border:none;border-radius:5px;font-family:var(--mono);font-size:13px;font-weight:700;cursor:pointer;letter-spacing:.06em">
      HOCHLADEN
    </button>
  </div>
</div>

<script>
function rlOpenUploadModal() {
  var sel = document.getElementById('um-type');
  if (sel) sel.value = _currentType === 'robot' ? 'endeffektor' : _currentType;
  document.getElementById('um-msg').style.display = 'none';
  document.getElementById('um-progress').style.display = 'none';
  document.getElementById('uploadModal').style.display = 'flex';
}

async function umDoUpload() {
  var btn  = document.getElementById('um-submit');
  var msg  = document.getElementById('um-msg');
  var prog = document.getElementById('um-progress');
  var bar  = document.getElementById('um-bar');

  var showMsg = function(txt, ok) {
    msg.textContent = txt;
    msg.style.cssText = 'display:block;padding:6px 10px;border-radius:4px;font-size:11px;margin-bottom:10px;' +
      (ok ? 'background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)'
          : 'background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)');
  };

  var type   = document.getElementById('um-type').value;
  var name   = document.getElementById('um-name').value.trim();
  var marke  = document.getElementById('um-marke').value.trim();
  var modell = document.getElementById('um-modell').value.trim();
  var user   = document.getElementById('um-user').value.trim();
  var pass   = document.getElementById('um-pass').value;
  var stlFiles = document.getElementById('um-stl').files;

  if (!name)  { showMsg('Name fehlt.', false); return; }
  if (!user || !pass) { showMsg('Zugangsdaten fehlen.', false); return; }
  if (!stlFiles.length) { showMsg('Mindestens eine STL-Datei erforderlich.', false); return; }

  btn.disabled = true; btn.textContent = 'Lade...';
  prog.style.display = 'block'; bar.style.width = '10%';

  try {
    // Create ZIP from STL files
    var JSZip = window.JSZip;
    if (!JSZip) throw new Error('JSZip nicht geladen');
    var zip = new JSZip();
    var json = { name: name, type: type, marke: marke, modell: modell };
    if (type === 'endeffektor') {
      for (var i = 0; i < stlFiles.length; i++) {
        var fname = i === 0 ? 'endeffektor.stl' : 'endeffektor_' + (i+1) + '.stl';
        zip.file(fname, await stlFiles[i].arrayBuffer());
      }
      json.endeffektor = { stl: 'endeffektor.stl' };
    } else {
      json.umfeld = [];
      for (var i = 0; i < stlFiles.length; i++) {
        var fname = 'umfeld_' + (i+1) + '.stl';
        zip.file(fname, await stlFiles[i].arrayBuffer());
        json.umfeld.push({ name: stlFiles[i].name, stl: fname, px:0, py:0, pz:0, rx:0, ry:0, rz:0 });
      }
    }
    zip.file(name.replace(/\s+/g,'_').toLowerCase() + '.json', JSON.stringify(json, null, 2));
    bar.style.width = '40%';

    var zipBlob = await zip.generateAsync({ type: 'blob' });
    bar.style.width = '60%';

    var fd = new FormData();
    fd.append('type', type); fd.append('name', name);
    fd.append('marke', marke || '—'); fd.append('modell', modell || '—');
    fd.append('achsen', '0'); fd.append('reichweite_mm', '0');
    fd.append('nutzlast_kg', '0'); fd.append('gewicht_kg', '0');
    fd.append('wiederholgenauigkeit_mm', '0');
    fd.append('user', user); fd.append('pass', pass);
    fd.append('zip', zipBlob, name + '.zip');
    var thumb = document.getElementById('um-thumb').files[0];
    if (thumb) fd.append('thumb', thumb, thumb.name);

    var data = await new Promise(function(res, rej) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'api.php?action=upload');
      xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) bar.style.width = (60 + e.loaded/e.total*35) + '%';
      };
      xhr.onload = function() { try { res(JSON.parse(xhr.responseText)); } catch(e) { rej(e); } };
      xhr.onerror = function() { rej(new Error('Verbindungsfehler')); };
      xhr.send(fd);
    });

    if (data.ok) {
      bar.style.width = '100%'; bar.style.background = '#22c55e';
      showMsg('✓ Hochgeladen: ' + data.robot.name, true);
      setTimeout(function() { location.reload(); }, 1200);
    } else {
      showMsg('Fehler: ' + data.error, false);
    }
  } catch(e) {
    showMsg('Fehler: ' + e.message, false);
  } finally {
    btn.disabled = false; btn.textContent = 'HOCHLADEN';
  }
}
</script>
<?php endif; ?>

<!-- Benutzer-Manager (global — beide Tabs) -->
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

<script>

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
</body>
</html>
