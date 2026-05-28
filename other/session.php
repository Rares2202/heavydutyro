<?php
// =====================================================
// HeavyDutyRO — Helpers Sesiune & Răspuns JSON
// Inclus la începutul fiecărui fișier API
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 30,   // 30 zile
        'path'     => '/',
        'secure'   => false,         // pune true dacă folosești HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// CORS pentru dezvoltare locală (WAMP)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Verifică autentificarea și returnează user_id.
 * Oprește execuția cu 401 dacă sesiunea nu e validă.
 */
function requireAuth(): int {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Neautentificat'], 401);
    }
    return (int)$_SESSION['user_id'];
}

/**
 * Trimite răspuns JSON și oprește execuția.
 */
function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Citește body-ul JSON al request-ului.
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
