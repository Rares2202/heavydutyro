<?php
// =====================================================
// HeavyDutyRO — Conexiune Baza de Date
// Modifică DB_USER / DB_PASS dacă WAMP-ul tău
// are alte credențiale (implicit: root / fără parolă)
// =====================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'heavyduty_db');
define('DB_USER', 'root');
define('DB_PASS', 'boss');          // WAMP default = fără parolă
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'Eroare conexiune baza de date: ' . $e->getMessage()
            ]));
        }
    }

    return $pdo;
}
