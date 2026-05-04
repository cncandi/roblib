<?php
// =============================================================
//  ROBLIB – Konfiguration
//  Benutzerdaten und Pfade hier anpassen
// =============================================================

define('ROBLIB_USERS', [
    'admin'  => 'geheim',   // Benutzername => Passwort (ändern!)
    // weitere Benutzer hier eintragen:
    // 'user2' => 'passwort2',
]);

// Absoluter Pfad dieses Verzeichnisses (normalerweise nicht ändern)
define('ROBLIB_BASE',   __DIR__);
define('ROBOTS_DIR',    __DIR__ . '/robots/');
define('THUMBS_DIR',    __DIR__ . '/thumbs/');
define('DATA_FILE',     __DIR__ . '/data/robots.json');

// Öffentliche Basis-URL
define('BASE_URL', 'https://cnc-technik.de/robsimul/roblib/');
