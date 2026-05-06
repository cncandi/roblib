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
    api_require_auth();

    $required = ['name','marke','modell','achsen','reichweite_mm','nutzlast_kg','gewicht_kg','wiederholgenauigkeit_mm'];
    foreach ($required as $k) {
        if (trim($_POST[$k] ?? '') === '') api_error(400, "Feld '$k' fehlt.");
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
    api_require_auth();

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
    api_require_auth();

    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) api_error(400, 'ID fehlt.');

    if (!rl_delete_robot($id)) api_error(404, 'Roboter nicht gefunden.');
    api_json(['ok' => true, 'deleted' => $id]);

// ── UNKNOWN ─────────────────────────────────────────────────
default:
    api_error(400, "Unbekannte Action: $action");
}
