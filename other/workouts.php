<?php
// =====================================================
// HeavyDutyRO — API Jurnal Antrenamente
// GET    /api/workouts.php         → lista
// POST   /api/workouts.php         → adaugă
// DELETE /api/workouts.php?id=X    → șterge
// =====================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$userId = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

match ($method) {
    'GET'    => getWorkouts($userId),
    'POST'   => addWorkout($userId),
    'DELETE' => deleteWorkout($userId),
    default  => jsonResponse(['success' => false, 'message' => 'Metodă nepermisă'], 405),
};

// ── GET ────────────────────────────────────────────
function getWorkouts(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare('
        SELECT id, date, type, exercises, duration, intensity, notes, created_at
        FROM workout_journal
        WHERE user_id = ?
        ORDER BY date DESC, created_at DESC
    ');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $result = array_map(fn($r) => [
        'id'        => (int)$r['id'],
        'date'      => $r['date'],
        'type'      => $r['type'],
        'exercises' => $r['exercises'],
        'duration'  => (int)$r['duration'],
        'intensity' => (int)$r['intensity'],
        'notes'     => $r['notes'] ?? '',
    ], $rows);

    jsonResponse(['success' => true, 'data' => $result]);
}

// ── POST ───────────────────────────────────────────
function addWorkout(int $userId): void {
    $data      = getJsonBody();
    $date      = $data['date']      ?? '';
    $type      = $data['type']      ?? '';
    $exercises = $data['exercises'] ?? '';
    $duration  = (int)($data['duration']  ?? 0);
    $intensity = (int)($data['intensity'] ?? 5);
    $notes     = $data['notes']     ?? '';

    if (!$date || !$type || !$exercises || !$duration) {
        jsonResponse(['success' => false, 'message' => 'Câmpuri obligatorii lipsesc'], 400);
    }

    $intensity = max(1, min(10, $intensity));

    $db   = getDB();
    $stmt = $db->prepare('INSERT INTO workout_journal (user_id, date, type, exercises, duration, intensity, notes) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$userId, $date, $type, $exercises, $duration, $intensity, $notes]);

    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId(), 'message' => 'Antrenament salvat']);
}

// ── DELETE ─────────────────────────────────────────
function deleteWorkout(int $userId): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID lipsă'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM workout_journal WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Antrenament negăsit'], 404);
    }

    jsonResponse(['success' => true, 'message' => 'Antrenament șters']);
}
