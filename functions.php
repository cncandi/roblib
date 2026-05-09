<?php
// =============================================================
//  ROBLIB – Hilfsfunktionen
// =============================================================
require_once __DIR__ . '/config.php';

// ------ Daten laden / speichern --------------------------------

function rl_load_robots(): array {
    if (!file_exists(DATA_FILE)) return [];
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?? [];
}

function rl_save_robots(array $robots): void {
    file_put_contents(DATA_FILE, json_encode(array_values($robots), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ------ Authentifizierung --------------------------------------

function rl_check_auth(string $user, string $pass): bool {
    $users = ROBLIB_USERS;
    return isset($users[$user]) && hash_equals($users[$user], $pass);
}

function rl_session_auth(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['rl_auth']);
}

function rl_session_login(string $user, string $pass): bool {
    if (rl_check_auth($user, $pass)) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['rl_auth'] = true;
        $_SESSION['rl_user'] = $user;
        return true;
    }
    return false;
}

function rl_session_logout(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_destroy();
}

// ------ Roboter-CRUD -------------------------------------------

function rl_generate_id(): string {
    return bin2hex(random_bytes(6));
}

function rl_add_robot(array $meta, string $zip_tmp, ?string $thumb_tmp): array|false {
    $id      = rl_generate_id();
    $zip_dst = ROBOTS_DIR . $id . '.zip';

    if (!move_uploaded_file($zip_tmp, $zip_dst)) return false;

    $thumb_url = '';
    if ($thumb_tmp) {
        $ext       = 'png'; // we always convert/accept as-is
        $thumb_dst = THUMBS_DIR . $id . '.' . $ext;
        move_uploaded_file($thumb_tmp, $thumb_dst);
        $thumb_url = BASE_URL . 'thumbs/' . $id . '.' . $ext;
    }

    // Allowed types
    $allowed_types = ['robot', 'endeffektor', 'umfeld'];
    $type = trim($meta['type'] ?? 'robot');
    if (!in_array($type, $allowed_types)) $type = 'robot';

    $robot = [
        'id'                 => $id,
        'type'               => $type,
        'name'               => trim($meta['name']               ?? ''),
        'marke'              => trim($meta['marke']              ?? ''),
        'modell'             => trim($meta['modell']             ?? ''),
        'achsen'             => intval($meta['achsen']           ?? 6),
        'reichweite_mm'      => intval($meta['reichweite_mm']    ?? 0),
        'nutzlast_kg'        => floatval($meta['nutzlast_kg']    ?? 0),
        'gewicht_kg'         => floatval($meta['gewicht_kg']     ?? 0),
        'wiederholgenauigkeit_mm' => floatval($meta['wiederholgenauigkeit_mm'] ?? 0),
        'beschreibung'       => trim($meta['beschreibung']       ?? ''),
        'zip_url'            => BASE_URL . 'robots/' . $id . '.zip',
        'thumb_url'          => $thumb_url,
        'created'            => date('c'),
    ];

    $robots       = rl_load_robots();
    $robots[$id]  = $robot;
    rl_save_robots($robots);

    return $robot;
}


function rl_update_robot(string $id, array $meta, ?string $thumb_tmp): array|false {
    $robots = rl_load_robots();
    $idx = null;
    foreach ($robots as $i => $r) { if (($r['id'] ?? '') === $id) { $idx = $i; break; } }
    if ($idx === null) return false;

    $fields = ['name','type','marke','modell','achsen','reichweite_mm','nutzlast_kg','gewicht_kg','wiederholgenauigkeit_mm','beschreibung'];
    foreach ($fields as $k) {
        if (isset($meta[$k]) && trim($meta[$k]) !== '') {
            $robots[$idx][$k] = in_array($k,['achsen']) ? intval($meta[$k])
                : (in_array($k,['nutzlast_kg','gewicht_kg','wiederholgenauigkeit_mm']) ? floatval($meta[$k])
                : trim($meta[$k]));
        }
    }

    if ($thumb_tmp) {
        foreach (['png','jpg','jpeg','gif','webp'] as $ext) { @unlink(THUMBS_DIR . $id . '.' . $ext); }
        $thumb_dst = THUMBS_DIR . $id . '.png';
        move_uploaded_file($thumb_tmp, $thumb_dst);
        $robots[$idx]['thumb_url'] = BASE_URL . 'thumbs/' . $id . '.png';
    }

    rl_save_robots($robots);
    return $robots[$idx];
}
function rl_delete_robot(string $id): bool {
    $robots = rl_load_robots();
    $idx = null;
    foreach ($robots as $i => $r) { if (($r['id'] ?? '') === $id) { $idx = $i; break; } }
    if ($idx === null) return false;
    @unlink(ROBOTS_DIR . $id . '.zip');
    foreach (['png','jpg','jpeg','gif','webp'] as $ext) { @unlink(THUMBS_DIR . $id . '.' . $ext); }
    array_splice($robots, $idx, 1);
    rl_save_robots($robots);
    return true;
}

// ------ Thumbnails ---------------------------------------------

function rl_thumb_path(string $id): ?string {
    foreach (['png','jpg','jpeg','gif','webp'] as $ext) {
        $p = THUMBS_DIR . $id . '.' . $ext;
        if (file_exists($p)) return $p;
    }
    return null;
}

function rl_thumb_url(string $id): string {
    foreach (['png','jpg','jpeg','gif','webp'] as $ext) {
        if (file_exists(THUMBS_DIR . $id . '.' . $ext)) {
            return BASE_URL . 'thumbs/' . $id . '.' . $ext;
        }
    }
    return '';
}

// ── Benutzerverwaltung ─────────────────────────────────────────
define('USERS_FILE', __DIR__ . '/data/users.json');

function rl_load_users(): array {
    if (!file_exists(USERS_FILE)) return [];
    return json_decode(file_get_contents(USERS_FILE), true) ?? [];
}

function rl_save_users(array $users): void {
    file_put_contents(USERS_FILE, json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function rl_check_user_auth(string $user, string $pass): array|false {
    // Admin always has full access
    if (rl_check_auth($user, $pass)) return ['role' => 'admin', 'robots' => null];
    foreach (rl_load_users() as $u) {
        if ($u['username'] === $user && $u['password'] === $pass)
            return ['role' => 'user', 'robots' => $u['robots'] ?? []];
    }
    return false;
}

function rl_add_user(array $data): array|false {
    $users = rl_load_users();
    foreach ($users as $u) { if ($u['username'] === $data['username']) return false; }
    $user = ['id' => bin2hex(random_bytes(6)), 'username' => trim($data['username']),
             'password' => $data['password'], 'robots' => $data['robots'] ?? []];
    $users[] = $user;
    rl_save_users($users);
    return $user;
}

function rl_update_user(string $id, array $data): array|false {
    $users = rl_load_users();
    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            if (isset($data['username'])) $u['username'] = trim($data['username']);
            if (isset($data['password']) && $data['password'] !== '') $u['password'] = $data['password'];
            if (isset($data['robots'])) $u['robots'] = $data['robots'];
            rl_save_users($users);
            return $u;
        }
    }
    return false;
}

function rl_delete_user(string $id): bool {
    $users = rl_load_users();
    $new = array_values(array_filter($users, fn($u) => $u['id'] !== $id));
    if (count($new) === count($users)) return false;
    rl_save_users($new);
    return true;
}
