<?php
// =====================================================
// HeavyDutyRO — API Autentificare
// GET  /api/auth.php?action=me       → utilizator curent
// POST /api/auth.php?action=login    → autentificare
// POST /api/auth.php?action=register → înregistrare
// POST /api/auth.php?action=logout   → deconectare
// =====================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$action = $_GET['action'] ?? getJsonBody()['action'] ?? '';

match ($action) {
    'login'    => handleLogin(),
    'register' => handleRegister(),
    'logout'   => handleLogout(),
    'me'       => handleMe(),
    default    => jsonResponse(['success' => false, 'message' => 'Acțiune invalidă'], 400),
};

// ── LOGIN ──────────────────────────────────────────
function handleLogin(): void {
    $data     = getJsonBody();
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Email și parola sunt obligatorii'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, email, password_hash, first_name, last_name, experience FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Email sau parolă incorectă'], 401);
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];

    jsonResponse(['success' => true, 'user' => buildUserArray($user)]);
}

// ── REGISTER ───────────────────────────────────────
function handleRegister(): void {
    $data       = getJsonBody();
    $firstName  = trim($data['firstName'] ?? '');
    $lastName   = trim($data['lastName'] ?? '');
    $email      = trim($data['email'] ?? '');
    $password   = $data['password'] ?? '';
    $experience = in_array($data['experience'] ?? '', ['incepator','intermediar','avansat'])
                    ? $data['experience']
                    : 'incepator';
    $newsletter = (int)($data['newsletter'] ?? 0);

    if (!$firstName || !$lastName || !$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'Toate câmpurile sunt obligatorii'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Adresă email invalidă'], 400);
    }
    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'message' => 'Parola trebuie să aibă minim 8 caractere'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Adresa de email este deja înregistrată'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (email, password_hash, first_name, last_name, experience, newsletter) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$email, $hash, $firstName, $lastName, $experience, $newsletter]);

    $userId = (int)$db->lastInsertId();

    // Creare rând gol în user_stats
    $db->prepare('INSERT INTO user_stats (user_id) VALUES (?)')->execute([$userId]);

    $_SESSION['user_id']    = $userId;
    $_SESSION['user_email'] = $email;

    jsonResponse(['success' => true, 'user' => [
        'id'         => $userId,
        'email'      => $email,
        'firstName'  => $firstName,
        'lastName'   => $lastName,
        'experience' => $experience,
    ]]);
}

// ── LOGOUT ─────────────────────────────────────────
function handleLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Deconectat cu succes']);
}

// ── ME (sesiune curentă) ────────────────────────────
function handleMe(): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Neautentificat'], 401);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, email, first_name, last_name, experience FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Utilizator negăsit'], 404);
    }

    jsonResponse(['success' => true, 'user' => buildUserArray($user)]);
}

// ── HELPER ─────────────────────────────────────────
function buildUserArray(array $user): array {
    return [
        'id'         => (int)$user['id'],
        'email'      => $user['email'],
        'firstName'  => $user['first_name'] ?? '',
        'lastName'   => $user['last_name'] ?? '',
        'experience' => $user['experience'] ?? 'incepator',
    ];
}
