<?php
// =====================================================
// HeavyDutyRO — Helpers Sesiune & Răspuns JSON
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 30,
        'path'     => '/',
        'secure'   => false,
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
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');





if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Citește datele din request (JSON sau form data).
 */
/**
 * Citește datele din request (JSON sau form data).
 */
function getRequestData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Verificăm și HTTP_CONTENT_TYPE (unele servere folosesc asta)
    if (empty($contentType)) {
        $contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    }
    
    // Dacă e JSON (din fetch/AJAX)
    if (strpos(strtolower($contentType), 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    // Dacă e form normal (POST) sau GET
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST;
    }
    
    return $_GET;
}
/**
 * Verifică autentificarea și returnează user_id.
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
 * Alias pentru getRequestData() - pentru backward compatibility
 */
function getJsonBody(): array {
    return getRequestData();
}