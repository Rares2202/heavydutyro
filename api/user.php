<?php
// =====================================================
// HeavyDutyRO — API Profil Utilizator & Statistici
// GET  /api/user.php?action=profile  → profil + stats
// POST /api/user.php?action=profile  → actualizează profil
// POST /api/user.php?action=stats    → actualizează statistici
// POST /api/user.php?action=password → schimbă parola
// POST /api/user.php?action=bodyfat  → salvează estimare BF
// GET  /api/user.php?action=bodyfat  → ultima estimare BF
// =====================================================
 
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
 
$userId = requireAuth();
$action = $_GET['action'] ?? getJsonBody()['action'] ?? 'profile';
$method = $_SERVER['REQUEST_METHOD'];
 
match ([$method, $action]) {
    ['GET',  'profile'] => getProfile($userId),
    ['POST', 'profile'] => updateProfile($userId),
    ['POST', 'stats']   => updateStats($userId),
    ['POST', 'password']=> changePassword($userId),
    ['POST', 'bodyfat'] => saveBodyFat($userId),
    ['GET',  'bodyfat'] => getLastBodyFat($userId),
    ['GET',  'bodyfat_list'] => getBodyFatList($userId),
    default             => jsonResponse(['success' => false, 'message' => 'Acțiune invalidă'], 400),
};
 
// ── GET PROFIL ─────────────────────────────────────
function getProfile(int $userId): void {
    $db = getDB();
 
    $stmt = $db->prepare('SELECT id, email, first_name, last_name, experience, newsletter, created_at, updated_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
 
    $stmt = $db->prepare('SELECT percentage, weight FROM body_fat_estimates WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $latestBodyFat = $stmt->fetch() ?: [];

    $stmt = $db->prepare('SELECT COUNT(*) as total FROM body_fat_estimates WHERE user_id = ?');
    $stmt->execute([$userId]);
    $totalEstimates = (int)$stmt->fetchColumn();

    $stmt = $db->prepare('SELECT created_at FROM workout_journal WHERE user_id = ? ORDER BY date DESC, created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $lastWorkoutAt = $stmt->fetchColumn() ?: null;

    $stmt = $db->prepare('SELECT created_at FROM body_fat_estimates WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $lastEstimateAt = $stmt->fetchColumn() ?: null;
 
    // Număr antrenamente totale
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM workout_journal WHERE user_id = ?');
    $stmt->execute([$userId]);
    $totalWorkouts = (int)$stmt->fetchColumn();
 
    jsonResponse(['success' => true, 'data' => [
        'user'          => [
            'email'      => $user['email'],
            'firstName'  => $user['first_name'],
            'lastName'   => $user['last_name'],
            'experience' => $user['experience'],
            'newsletter' => (bool)$user['newsletter'],
            'createdAt'   => $user['created_at'],
            'updatedAt'   => $user['updated_at'],
        ],
        'stats'         => [
            'weight'       => isset($latestBodyFat['weight']) && $latestBodyFat['weight'] !== null ? (float)$latestBodyFat['weight'] : null,
            'bodyfat'      => isset($latestBodyFat['percentage']) && $latestBodyFat['percentage'] !== null ? (float)$latestBodyFat['percentage'] : null,
            'recoveryDays' => 2,
            'targetWeight' => null,
        ],
        'totalWorkouts' => $totalWorkouts,
        'totalEstimates' => $totalEstimates,
        'lastWorkoutAt' => $lastWorkoutAt,
        'lastEstimateAt' => $lastEstimateAt,
    ]]);
}
 
// ── UPDATE PROFIL ──────────────────────────────────
function updateProfile(int $userId): void {
    $data      = getJsonBody();
    $firstName = trim($data['firstName'] ?? '');
    $lastName  = trim($data['lastName']  ?? '');
    $experience = in_array($data['experience'] ?? '', ['incepator','intermediar','avansat'])
                    ? $data['experience'] : null;
 
    if (!$firstName || !$lastName) {
        jsonResponse(['success' => false, 'message' => 'Prenumele și numele sunt obligatorii'], 400);
    }
 
    $db   = getDB();
    $stmt = $db->prepare('UPDATE users SET first_name=?, last_name=?, experience=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$firstName, $lastName, $experience, $userId]);
 
    jsonResponse(['success' => true, 'message' => 'Profil actualizat']);
}
 
// ── UPDATE STATISTICI ──────────────────────────────
function updateStats(int $userId): void {
    $data         = getJsonBody();
    $weight       = isset($data['weight'])       ? (float)$data['weight']       : null;
    $bodyfat      = isset($data['bodyfat'])      ? (float)$data['bodyfat']      : null;
 
    $db   = getDB();

    if ($weight === null && $bodyfat === null) {
        jsonResponse(['success' => true, 'message' => 'Nicio schimbare de greutate/bodyfat']);
    }

    $stmt = $db->prepare('SELECT category, gender, age, weight, height, percentage FROM body_fat_estimates WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $last = $stmt->fetch() ?: [];

    $percentage = $bodyfat ?? (isset($last['percentage']) ? (float)$last['percentage'] : null);
    $resolvedWeight = $weight ?? (isset($last['weight']) ? (float)$last['weight'] : null);

    $stmt = $db->prepare('INSERT INTO body_fat_estimates (user_id, percentage, category, gender, age, weight, height) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([
        $userId,
        $percentage,
        $last['category'] ?? null,
        $last['gender'] ?? null,
        isset($last['age']) && $last['age'] !== null ? (int)$last['age'] : null,
        $resolvedWeight,
        isset($last['height']) && $last['height'] !== null ? (float)$last['height'] : null,
    ]);

    jsonResponse(['success' => true, 'message' => 'Greutate/bodyfat actualizate']);
}
 
// ── SCHIMBĂ PAROLA ─────────────────────────────────
function changePassword(int $userId): void {
    $data        = getJsonBody();
    $current     = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword']     ?? '';
 
    if (!$current || !$newPassword) {
        jsonResponse(['success' => false, 'message' => 'Câmpuri obligatorii lipsesc'], 400);
    }
    if (strlen($newPassword) < 8) {
        jsonResponse(['success' => false, 'message' => 'Parola nouă trebuie să aibă minim 8 caractere'], 400);
    }
 
    $db   = getDB();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
 
    if (!$user || !password_verify($current, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Parola curentă este incorectă'], 401);
    }
 
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')
       ->execute([$newHash, $userId]);
 
    jsonResponse(['success' => true, 'message' => 'Parola a fost schimbată']);
}
 
// ── SALVEAZĂ ESTIMARE GRĂSIME ──────────────────────
function saveBodyFat(int $userId): void {
    $data       = getJsonBody();
    $percentage = (float)($data['percentage'] ?? 0);
    $category   = $data['category'] ?? '';
    $gender     = $data['gender']   ?? '';
    $age        = isset($data['age'])    ? (int)$data['age']     : null;
    $weight     = isset($data['weight']) ? (float)$data['weight'] : null;
    $height     = isset($data['height']) ? (float)$data['height'] : null;
 
    $db   = getDB();
    $stmt = $db->prepare('INSERT INTO body_fat_estimates (user_id, percentage, category, gender, age, weight, height) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$userId, $percentage, $category, $gender, $age, $weight, $height]);
 
    jsonResponse(['success' => true, 'message' => 'Estimare salvată']);
}
 
// ── ULTIMA ESTIMARE GRĂSIME ────────────────────────
function getLastBodyFat(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT percentage, category, gender, age, weight, height, created_at FROM body_fat_estimates WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $row  = $stmt->fetch();
 
    if (!$row) {
        jsonResponse(['success' => true, 'data' => null]);
    }
 
    jsonResponse(['success' => true, 'data' => [
        'percentage' => (float)$row['percentage'],
        'category'   => $row['category'],
        'gender'     => $row['gender'],
        'age'        => $row['age'] ? (int)$row['age'] : null,
        'weight'     => $row['weight'] ? (float)$row['weight'] : null,
        'height'     => $row['height'] ? (float)$row['height'] : null,
        'date'       => $row['created_at'],
    ]]);
}

// ── LISTĂ ULTIMELE ESTIMĂRI (până la 30) ───────────
function getBodyFatList(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT percentage, weight, height, created_at FROM body_fat_estimates WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $data = array_map(function($r){
        return [
            'percentage' => $r['percentage'] !== null ? (float)$r['percentage'] : null,
            'weight'     => $r['weight'] !== null ? (float)$r['weight'] : null,
            'height'     => $r['height'] !== null ? (float)$r['height'] : null,
            'date'       => $r['created_at'],
        ];
    }, $rows ?: []);

    jsonResponse(['success' => true, 'data' => $data]);
}