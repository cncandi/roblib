<?php
require_once __DIR__ . '/functions.php';
session_start();

$error   = '';
$success = '';

// ── Logout ────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    rl_session_logout();
    header('Location: manage.php');
    exit;
}

// ── Login POST ────────────────────────────────────────────────
if (!rl_session_auth() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (rl_session_login($_POST['user'] ?? '', $_POST['pass'] ?? '')) {
        header('Location: manage.php');
        exit;
    }
    $error = 'Ungültige Zugangsdaten.';
}

// ── Delete POST ───────────────────────────────────────────────
if (rl_session_auth() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    $confirmPass = $_POST['confirm_pass'] ?? '';
    $currentUser = $_SESSION['rl_user'] ?? 'admin';
    if (!rl_check_auth($currentUser, $confirmPass)) {
        $error = 'Falsches Passwort – Löschen abgebrochen.';
    } elseif ($id && rl_delete_robot($id)) {
        $success = 'Objekt gelöscht.';
    } else {
        $error = 'Löschen fehlgeschlagen.';
    }
}

// ── Upload POST ───────────────────────────────────────────────
if (rl_session_auth() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $required = ['name', 'marke', 'modell', 'achsen', 'reichweite_mm', 'nutzlast_kg', 'gewicht_kg', 'wiederholgenauigkeit_mm'];
    $missing  = array_filter($required, fn($k) => trim($_POST[$k] ?? '') === '');

    if ($missing) {
        $error = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (empty($_FILES['zip']['tmp_name']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) {
        $error = 'ZIP-Datei fehlt oder Uploadfehler.';
    } elseif (strtolower(pathinfo($_FILES['zip']['name'], PATHINFO_EXTENSION)) !== 'zip') {
        $error = 'Nur .zip Dateien erlaubt.';
    } else {
        $thumb_tmp = (!empty($_FILES['thumb']['tmp_name']) && $_FILES['thumb']['error'] === UPLOAD_ERR_OK)
                     ? $_FILES['thumb']['tmp_name']
                     : null;

        $robot = rl_add_robot($_POST, $_FILES['zip']['tmp_name'], $thumb_tmp);
        if ($robot) {
            $success = 'Roboter "' . htmlspecialchars($robot['name']) . '" erfolgreich hochgeladen.';
        } else {
            $error = 'Fehler beim Speichern. Prüfe Schreibrechte auf robots/.';
        }
    }
}

$robots   = array_values(rl_load_robots());
$loggedIn = rl_session_auth();
usort($robots, fn($a, $b) => strcmp($a['name'], $b['name']));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ROBLIB / Verwaltung</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg:       #09131c;
  --bg2:      #0d1a26;
  --bg3:      #0f2030;
  --card:     #0d1e2e;
  --border:   #1a3348;
  --border-hi:#ff6000;
  --orange:   #ff6000;
  --red:      #cc2200;
  --text:     #d8e8f0;
  --text-dim: #6a8fa8;
  --mono:     'Share Tech Mono', monospace;
  --sans:     'Exo 2', sans-serif;
  --r:        4px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); color: var(--text); font-family: var(--sans); min-height: 100vh; }

header {
  background: #060e14;
  border-bottom: 2px solid var(--orange);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 24px; height: 48px;
}
.logo { font-family: var(--mono); font-size: 13px; color: var(--orange); letter-spacing: 0.08em; }
.logo span { color: var(--text-dim); font-size: 11px; margin-left: 8px; }
header a { font-family: var(--mono); font-size: 11px; color: var(--text-dim); text-decoration: none;
  padding: 4px 10px; border: 1px solid var(--border); border-radius: var(--r); transition: all .15s; }
header a:hover { color: var(--orange); border-color: var(--orange); }

.wrap { max-width: 900px; margin: 0 auto; padding: 32px 24px; }

h1 { font-family: var(--mono); font-size: 18px; color: var(--orange); letter-spacing: 0.06em;
  text-transform: uppercase; margin-bottom: 24px; }

/* ── LOGIN ─────────────────────────────────────────────── */
.login-box {
  background: var(--card); border: 1px solid var(--border); border-radius: 6px;
  padding: 32px; max-width: 380px; margin: 0 auto;
}
.login-box h2 { font-family: var(--mono); font-size: 14px; color: var(--orange);
  letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 20px; }

/* ── FORMS ─────────────────────────────────────────────── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-grid .span2 { grid-column: 1/-1; }

label { display: block; font-family: var(--mono); font-size: 11px; color: var(--text-dim);
  text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px; }

input[type=text], input[type=password], input[type=number], input[type=file], textarea {
  width: 100%; background: var(--bg3); border: 1px solid var(--border);
  color: var(--text); font-family: var(--mono); font-size: 12px;
  padding: 8px 10px; border-radius: var(--r); outline: none; transition: border-color .15s;
}
input:focus, textarea:focus { border-color: var(--orange); }
textarea { resize: vertical; min-height: 60px; }

.btn {
  font-family: var(--mono); font-size: 12px; letter-spacing: 0.06em;
  text-transform: uppercase; padding: 9px 18px; border-radius: var(--r);
  border: none; cursor: pointer; transition: background .15s;
}
.btn-primary { background: var(--orange); color: #fff; }
.btn-primary:hover { background: #ff7a20; }
.btn-danger  { background: var(--red); color: #fff; }
.btn-danger:hover { background: #e03010; }

/* ── MESSAGES ─────────────────────────────────────────── */
.msg { font-family: var(--mono); font-size: 12px; padding: 10px 14px;
  border-radius: var(--r); margin-bottom: 20px; }
.msg-error   { background: #2a0800; border: 1px solid var(--red);   color: #ff7060; }
.msg-success { background: #0a2010; border: 1px solid #1a8040;      color: #40c060; }

/* ── SECTION ──────────────────────────────────────────── */
.section { background: var(--card); border: 1px solid var(--border);
  border-radius: 6px; padding: 24px; margin-bottom: 24px; }
.section h2 { font-family: var(--mono); font-size: 13px; color: var(--orange);
  letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 16px;
  padding-bottom: 8px; border-bottom: 1px solid var(--border); }

/* ── ROBOT LIST TABLE ─────────────────────────────────── */
.robot-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.robot-table th {
  font-family: var(--mono); font-size: 10px; text-transform: uppercase;
  letter-spacing: 0.06em; color: var(--text-dim); text-align: left;
  padding: 6px 8px; border-bottom: 1px solid var(--border);
}
.robot-table td { padding: 8px 8px; border-bottom: 1px solid var(--bg2); vertical-align: middle; }
.robot-table tr:hover td { background: var(--bg3); }
.robot-table .name { color: #fff; font-family: var(--mono); }
.robot-table .dim  { color: var(--text-dim); font-size: 11px; }

.thumb-small { width: 40px; height: 40px; object-fit: contain; background: var(--bg2);
  border-radius: 2px; border: 1px solid var(--border); }
.no-thumb-small { width: 40px; height: 40px; background: var(--bg2);
  border: 1px solid var(--border); border-radius: 2px; display: flex;
  align-items: center; justify-content: center; color: var(--text-dim); font-size: 9px; }

.divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }
</style>
</head>
<body>

<header>
  <div class="logo">● ROBLIB<span>Verwaltung</span></div>
  <div>
    <a href="index.php">← BIBLIOTHEK</a>
    <?php if ($loggedIn): ?>
      &nbsp;<a href="manage.php?logout=1">ABMELDEN</a>
    <?php endif; ?>
  </div>
</header>

<div class="wrap">
  <h1>ROBLIB / VERWALTUNG</h1>

  <?php if ($error):   echo "<div class='msg msg-error'>$error</div>";   endif; ?>
  <?php if ($success): echo "<div class='msg msg-success'>$success</div>"; endif; ?>

  <?php if (!$loggedIn): ?>
  <!-- ── LOGIN FORM ─────────────────────────────────────── -->
  <div class="login-box">
    <h2>Anmeldung erforderlich</h2>
    <form method="post">
      <input type="hidden" name="action" value="login">
      <div style="margin-bottom:12px">
        <label>Benutzer</label>
        <input type="text" name="user" autocomplete="username" autofocus>
      </div>
      <div style="margin-bottom:16px">
        <label>Passwort</label>
        <input type="password" name="pass" autocomplete="current-password">
      </div>
      <button class="btn btn-primary" type="submit">ANMELDEN</button>
    </form>
  </div>

  <?php else: ?>
  <!-- ── UPLOAD FORM ────────────────────────────────────── -->
  <div class="section">
    <h2>Neues Robotermodell hochladen</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload">
      <div class="form-grid">
        <div class="span2">
          <label>Name des Roboters *</label>
          <input type="text" name="name" placeholder="z.B. KUKA KR8 R1420 HW">
        </div>
        <div>
          <label>Marke *</label>
          <input type="text" name="marke" placeholder="z.B. KUKA">
        </div>
        <div>
          <label>Modell *</label>
          <input type="text" name="modell" placeholder="z.B. KR8 R1420">
        </div>
        <div>
          <label>Achsen *</label>
          <input type="number" name="achsen" min="1" max="9" value="6">
        </div>
        <div>
          <label>Reichweite [mm] *</label>
          <input type="number" name="reichweite_mm" min="0" placeholder="z.B. 1420">
        </div>
        <div>
          <label>Nutzlast [kg] *</label>
          <input type="number" name="nutzlast_kg" step="0.1" min="0" placeholder="z.B. 8">
        </div>
        <div>
          <label>Gewicht [kg] *</label>
          <input type="number" name="gewicht_kg" step="0.1" min="0" placeholder="z.B. 235">
        </div>
        <div>
          <label>Wiederholgenauigkeit [mm] *</label>
          <input type="number" name="wiederholgenauigkeit_mm" step="0.001" min="0" placeholder="z.B. 0.030">
        </div>
        <div class="span2">
          <label>Beschreibung (optional)</label>
          <textarea name="beschreibung" placeholder="Kurze Beschreibung des Modells…"></textarea>
        </div>
        <div>
          <label>ZIP-Datei * (.zip)</label>
          <input type="file" name="zip" accept=".zip">
        </div>
        <div>
          <label>Vorschaubild (optional, PNG/JPG)</label>
          <input type="file" name="thumb" accept="image/*">
        </div>
      </div>
      <hr class="divider">
      <button class="btn btn-primary" type="submit">▲ MODELL HOCHLADEN</button>
    </form>
  </div>

  <!-- ── ROBOT LIST ─────────────────────────────────────── -->
  <div class="section">
    <h2>Vorhandene Modelle (<?= count($robots) ?>)</h2>
    <?php if (empty($robots)): ?>
      <p style="font-family:var(--mono);font-size:12px;color:var(--text-dim)">Noch keine Modelle vorhanden.</p>
    <?php else: ?>
    <table class="robot-table">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
          <th>Marke</th>
          <th>Achsen</th>
          <th>Reichweite</th>
          <th>Hochgeladen</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($robots as $r): ?>
        <?php $thumb = rl_thumb_url($r['id']); ?>
        <tr>
          <td>
            <?php if ($thumb): ?>
              <img class="thumb-small" src="<?= htmlspecialchars($thumb) ?>" alt="">
            <?php else: ?>
              <div class="no-thumb-small">—</div>
            <?php endif; ?>
          </td>
          <td class="name"><?= htmlspecialchars($r['name']) ?></td>
          <td class="dim"><?= htmlspecialchars($r['marke']) ?></td>
          <td class="dim"><?= $r['achsen'] ?></td>
          <td class="dim"><?= $r['reichweite_mm'] ?> mm</td>
          <td class="dim"><?= substr($r['created'] ?? '', 0, 10) ?></td>
          <td>
            <button class="btn btn-danger" type="button" style="padding:4px 10px;font-size:10px"
              onclick="openDeleteModal('<?= htmlspecialchars($r['id']) ?>', '<?= htmlspecialchars(addslashes($r['name'])) ?>')">LÖSCHEN</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<!-- ── Delete-Bestätigung Modal ───────────────────────────────── -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#1a2535;border:1px solid rgba(255,255,255,.15);border-radius:8px;padding:28px 32px;min-width:320px;max-width:400px">
    <h3 style="margin:0 0 8px;color:#f87171">Objekt löschen</h3>
    <p style="margin:0 0 16px;color:#9ab;font-size:14px">„<span id="del-name"></span>" wird unwiderruflich gelöscht.</p>
    <form method="post" id="deleteForm">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="del-id">
      <label style="display:block;margin-bottom:4px;font-size:13px;color:#9ab">Passwort zur Bestätigung</label>
      <input type="password" name="confirm_pass" id="del-pass" required autocomplete="current-password"
        style="width:100%;box-sizing:border-box;padding:8px;background:#0f1e2e;border:1px solid rgba(255,255,255,.2);border-radius:4px;color:#d8e8f0;font-size:14px;margin-bottom:16px">
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="closeDeleteModal()" style="padding:7px 16px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);border-radius:4px;color:#9ab;cursor:pointer">Abbrechen</button>
        <button type="submit" style="padding:7px 16px;background:#b91c1c;border:none;border-radius:4px;color:#fff;cursor:pointer;font-weight:600">Löschen</button>
      </div>
    </form>
  </div>
</div>
<script>
function openDeleteModal(id, name) {
  document.getElementById('del-id').value = id;
  document.getElementById('del-name').textContent = name;
  document.getElementById('del-pass').value = '';
  const m = document.getElementById('deleteModal');
  m.style.display = 'flex';
  setTimeout(() => document.getElementById('del-pass').focus(), 80);
}
function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('deleteModal')?.addEventListener('click', e => {
  if (e.target === document.getElementById('deleteModal')) closeDeleteModal();
});
</script>

</body>
</html>
