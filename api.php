<?php
// =============================================================
//  ROBLIB – REST API
//
//  Endpunkte:
//    GET  api.php                  → Liste aller Roboter (JSON)
//    GET  api.php?action=list      → Liste aller Roboter (JSON)
//    GET  api.php?action=download&id=XXX  → ZIP-Datei herunterladen
//    POST api.php?action=upload    → Roboter hochladen (auth: user+pass im POST)
//    POST api.php?action=delete    → Roboter löschen  (auth: user+pass im POST)
//    POST api.php?action=update    → Roboter bearbeiten (auth: user+pass im POST)
// =============================================================
require_once __DIR__ . '/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = $_GET['action'] ?? (empty($_GET) && $_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : ($_POST['action'] ?? ''));
if (!$action) $action = 'list';

// ── Authentifizierung für schreibende Operationen ───────────
function api_require_auth(): void {
    // Session auth (same-origin requests from index.php)
    if (!empty($_POST['session']) && rl_session_auth()) return;

    // HTTP Basic Auth
    $u = $_SERVER['PHP_AUTH_USER'] ?? '';
    $p = $_SERVER['PHP_AUTH_PW']   ?? '';

    // Fallback: POST-Body-Felder
    if (!$u) { $u = $_POST['user'] ?? ''; $p = $_POST['pass'] ?? ''; }

    if (!rl_check_auth($u, $p)) {
        header('WWW-Authenticate: Basic realm="ROBLIB"');
        api_error(401, 'Authentifizierung fehlgeschlagen.');
    }
}

function api_json(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(int $code, string $msg): never {
    api_json(['ok' => false, 'error' => $msg], $code);
}

// ────────────────────────────────────────────────────────────
switch ($action) {

// ── LIST ────────────────────────────────────────────────────
case 'list':
    $robots = array_values(rl_load_robots());
    usort($robots, fn($a, $b) => strcmp($a['name'], $b['name']));
    // Optional user auth filter
    $u = $_POST['user'] ?? $_GET['user'] ?? '';
    $p = $_POST['pass'] ?? $_GET['pass'] ?? '';
    if ($u && $p) {
        $auth = rl_check_user_auth($u, $p);
        if (!$auth) api_error(401, 'Authentifizierung fehlgeschlagen.');
        if ($auth['role'] !== 'admin' && is_array($auth['robots'])) {
            $robots = array_values(array_filter($robots, fn($r) => in_array($r['id'], $auth['robots'])));
        }
    }
    api_json(['ok' => true, 'count' => count($robots), 'robots' => $robots]);

// ── DOWNLOAD ────────────────────────────────────────────────
case 'download':
    $id  = preg_replace('/[^a-f0-9]/', '', $_GET['id'] ?? '');
    $zip = ROBOTS_DIR . $id . '.zip';
    if (!$id || !file_exists($zip)) api_error(404, 'Datei nicht gefunden.');

    $robots = rl_load_robots();
    $name   = $robots[$id]['name'] ?? $id;
    $fname  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name) . '.zip';

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($zip));
    header('Cache-Control: no-cache');
    readfile($zip);
    exit;

// ── UPLOAD ──────────────────────────────────────────────────
case 'upload':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405, 'POST erforderlich.');

    // name always required
    if (trim($_POST['name'] ?? '') === '') api_error(400, "Feld 'name' fehlt.");
    $type = trim($_POST['type'] ?? 'robot');
    // Robot requires all kinematic fields
    if ($type === 'robot') {
        $required = ['marke','modell','achsen','reichweite_mm','nutzlast_kg','gewicht_kg','wiederholgenauigkeit_mm'];
        foreach ($required as $k) {
            if (trim($_POST[$k] ?? '') === '') api_error(400, "Feld '$k' fehlt.");
        }
    }

    if (empty($_FILES['zip']['tmp_name']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK)
        api_error(400, 'ZIP-Datei fehlt oder Uploadfehler: ' . ($_FILES['zip']['error'] ?? '?'));

    if (strtolower(pathinfo($_FILES['zip']['name'], PATHINFO_EXTENSION)) !== 'zip')
        api_error(400, 'Nur .zip Dateien erlaubt.');

    $thumb_tmp = (!empty($_FILES['thumb']['tmp_name']) && $_FILES['thumb']['error'] === UPLOAD_ERR_OK)
                 ? $_FILES['thumb']['tmp_name']
                 : null;

    $robot = rl_add_robot($_POST, $_FILES['zip']['tmp_name'], $thumb_tmp);
    if (!$robot) api_error(500, 'Speichern fehlgeschlagen. Prüfe Verzeichnis-Schreibrechte.');

    api_json(['ok' => true, 'robot' => $robot], 201);


// ── UPDATE ──────────────────────────────────────────────────
case 'update':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405, 'POST erforderlich.');
    // api_require_auth(); // auth deaktiviert

    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) api_error(400, 'ID fehlt.');

    $thumb_tmp = (!empty($_FILES['thumb']['tmp_name']) && $_FILES['thumb']['error'] === UPLOAD_ERR_OK)
                 ? $_FILES['thumb']['tmp_name'] : null;

    $robot = rl_update_robot($id, $_POST, $thumb_tmp);
    if (!$robot) api_error(404, 'Roboter nicht gefunden.');

    api_json(['ok' => true, 'robot' => $robot]);

// ── DELETE ──────────────────────────────────────────────────
case 'delete':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405, 'POST erforderlich.');

    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) api_error(400, 'ID fehlt.');

    if (!rl_delete_robot($id)) api_error(404, 'Roboter nicht gefunden.');
    api_json(['ok' => true, 'deleted' => $id]);


// ── LIST_USERS ──────────────────────────────────────────────
case 'list_users':
    api_require_auth();
    $users = rl_load_users();
    // Strip passwords from response
    $safe = array_map(fn($u) => ['id'=>$u['id'],'username'=>$u['username'],'robots'=>$u['robots']??[]], $users);
    api_json(['ok' => true, 'users' => array_values($safe)]);

// ── ADD_USER ─────────────────────────────────────────────────
case 'add_user':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405, 'POST erforderlich.');
    api_require_auth();
    if (!trim($_POST['username'] ?? '')) api_error(400, 'Benutzername fehlt.');
    if (!trim($_POST['password'] ?? '')) api_error(400, 'Passwort fehlt.');
    $robots = json_decode($_POST['robots'] ?? '[]', true) ?: [];
    $user = rl_add_user(['username'=>$_POST['username'],'password'=>$_POST['password'],'robots'=>$robots]);
    if (!$user) api_error(409, 'Benutzername bereits vergeben.');
    api_json(['ok' => true, 'user' => ['id'=>$user['id'],'username'=>$user['username'],'robots'=>$user['robots']]], 201);

// ── UPDATE_USER ──────────────────────────────────────────────
case 'update_user':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405, 'POST erforderlich.');
    api_require_auth();
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) api_error(400, 'ID fehlt.');
    $robots = json_decode($_POST['robots'] ?? '[]', true) ?: [];
    $data = ['robots' => $robots];
    if (!empty($_POST['username'])) $data['username'] = $_POST['username'];
    if (!empty($_POST['password'])) $data['password'] = $_POST['password'];
    $user = rl_update_user($id, $data);
    if (!$user) api_error(404, 'Benutzer nicht gefunden.');
    api_json(['ok' => true, 'user' => ['id'=>$user['id'],'username'=>$user['username'],'robots'=>$user['robots']]]);

// ── DELETE_USER ──────────────────────────────────────────────
case 'delete_user':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405, 'POST erforderlich.');
    api_require_auth();
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) api_error(400, 'ID fehlt.');
    if (!rl_delete_user($id)) api_error(404, 'Benutzer nicht gefunden.');
    api_json(['ok' => true, 'deleted' => $id]);

// ── KRL_LIST ─────────────────────────────────────────────────
case 'krl_list':
    krl_init();
    $progs = krl_load();
    $cat = $_GET['cat'] ?? '';
    $q   = strtolower($_GET['q'] ?? '');
    if ($cat) $progs = array_values(array_filter($progs, fn($p) => ($p['category']??'') === $cat));
    if ($q)   $progs = array_values(array_filter($progs, fn($p) =>
        str_contains(strtolower($p['name']??''), $q) ||
        str_contains(strtolower($p['description']??''), $q) ||
        str_contains(strtolower($p['author']??''), $q) ||
        str_contains(strtolower($p['tags']??''), $q)
    ));
    usort($progs, fn($a,$b) => ($b['date']??0)-($a['date']??0));
    api_json(['ok'=>true,'programs'=>$progs,'total'=>count($progs)]);

// ── KRL_POINTS ───────────────────────────────────────────────
case 'krl_points':
    krl_init();
    $u = $_GET['user'] ?? $_POST['user'] ?? '';
    if (!$u) api_error(400,'Kein Benutzer');
    $pts = krl_pts_load();
    api_json([
        'ok'        => true,
        'user'      => $u,
        'points'    => $pts[$u]['points']    ?? 50,
        'uploads'   => $pts[$u]['uploads']   ?? 0,
        'downloads' => $pts[$u]['downloads'] ?? 0,
    ]);

// ── KRL_UPLOAD ───────────────────────────────────────────────
case 'krl_upload':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error(405,'POST erforderlich.');
    krl_init();
    $b        = json_decode(file_get_contents('php://input'), true) ?? [];
    $user     = trim($b['user']        ?? '');
    $name     = trim($b['name']        ?? '');
    $desc     = trim($b['description'] ?? '');
    $content  = $b['content']  ?? '';
    $filename = preg_replace('/[^a-zA-Z0-9._-]/','_', trim($b['filename']??'program.src'));
    $category = in_array($b['category']??'',['snippet','program','function','submit','interrupt','cell'])
                ? $b['category'] : 'program';
    $tags     = trim($b['tags'] ?? '');
    if (!$user||!$name||!$content||!$filename) api_error(400,'Pflichtfelder fehlen');
    if (strlen($content)>500000) api_error(400,'Datei zu groß (max 500 KB)');

    $id  = 'krl_'.bin2hex(random_bytes(6));
    $dir = KRL_DIR.$id;
    mkdir($dir,0755,true);
    file_put_contents($dir.'/'.$filename, $content);

    $progs = krl_load();
    $progs[] = [
        'id'          => $id,
        'name'        => htmlspecialchars($name),
        'description' => htmlspecialchars($desc),
        'category'    => $category,
        'filename'    => $filename,
        'tags'        => htmlspecialchars($tags),
        'author'      => htmlspecialchars($user),
        'date'        => time(),
        'downloads'   => 0,
        'likes'       => 0,
        'size'        => strlen($content),
    ];
    krl_save($progs);
    $newPts = krl_add_points($user, 10);
    api_json(['ok'=>true,'id'=>$id,'points'=>$newPts]);

// ── KRL_DOWNLOAD ─────────────────────────────────────────────
case 'krl_download':
    krl_init();
    $id   = $_GET['id']   ?? '';
    $user = $_GET['user'] ?? '';
    $progs = krl_load();
    $idx = -1;
    foreach ($progs as $i=>$p) { if ($p['id']===$id) { $idx=$i; break; } }
    if ($idx<0) api_error(404,'Nicht gefunden');
    $prog = $progs[$idx];
    $path = KRL_DIR.$id.'/'.$prog['filename'];
    if (!file_exists($path)) api_error(404,'Datei fehlt');
    $content = file_get_contents($path);
    $newPts  = null;
    if ($user && $user !== $prog['author']) {
        if (krl_get_points($user) < 20) api_error(403,'Nicht genug Punkte (benötigt: 20, vorhanden: '.krl_get_points($user).')');
        $newPts = krl_add_points($user, -20);
    }
    $progs[$idx]['downloads']++;
    krl_save($progs);
    api_json(['ok'=>true,'content'=>$content,'filename'=>$prog['filename'],'name'=>$prog['name'],'points'=>$newPts]);

// ── KRL_LIKE ─────────────────────────────────────────────────
case 'krl_like':
    krl_init();
    $id = $_GET['id']??'';
    $progs = krl_load();
    foreach ($progs as &$p) { if ($p['id']===$id) { $p['likes']=($p['likes']??0)+1; break; } }
    krl_save($progs);
    api_json(['ok'=>true]);

// ── KRL_DELETE ───────────────────────────────────────────────
case 'krl_delete':
    krl_init();
    $id   = $_GET['id']   ?? '';
    $user = $_GET['user'] ?? '';
    $progs = krl_load();
    foreach ($progs as $i=>$p) {
        if ($p['id']===$id && $p['author']===$user) {
            array_splice($progs,$i,1); krl_save($progs);
            api_json(['ok'=>true]);
        }
    }
    api_error(403,'Nicht berechtigt');

// ── UNKNOWN ─────────────────────────────────────────────────
default:
    api_error(400, "Unbekannte Action: $action");
}
