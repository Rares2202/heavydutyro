<?php
// =====================================================
// HeavyDutyRO — API Măsurători Progres
// GET    /api/measurements.php        → lista
// POST   /api/measurements.php        → adaugă
// DELETE /api/measurements.php?id=X   → șterge
// =====================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$userId = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

match ($method) {
    'GET'    => getMeasurements($userId),
    'POST'   => addMeasurement($userId),
    'DELETE' => deleteMeasurement($userId),
    default  => jsonResponse(['success' => false, 'message' => 'Metodă nepermisă'], 405),
};

// ── GET: toate măsurătorile utilizatorului ──────────
function getMeasurements(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, date, weight, bodyfat, muscle, created_at FROM measurements WHERE user_id = ? ORDER BY date DESC');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    // Calculează schimbarea față de măsurătoarea anterioară
    $result = [];
    foreach ($rows as $i => $row) {
        $prev   = $rows[$i + 1] ?? null;
        $change = $prev ? round($row['weight'] - $prev['weight'], 1) : null;
        $result[] = [
            'id'      => (int)$row['id'],
            'date'    => $row['date'],
            'weight'  => (float)$row['weight'],
            'bodyfat' => (float)$row['bodyfat'],
            'muscle'  => (float)$row['muscle'],
            'change'  => $change,
        ];
    }

    jsonResponse(['success' => true, 'data' => $result]);
}

// ── POST: adaugă măsurătoare ────────────────────────
function addMeasurement(int $userId): void {
    $data   = getJsonBody();
    $date   = $data['date']    ?? '';
    $weight = $data['weight']  ?? null;
    $bf     = $data['bodyfat'] ?? null;
    $muscle = $data['muscle']  ?? null;

    if (!$date || !$weight) {
        jsonResponse(['success' => false, 'message' => 'Data și greutatea sunt obligatorii'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('INSERT INTO measurements (user_id, date, weight, bodyfat, muscle) VALUES (?,?,?,?,?)');
    $stmt->execute([$userId, $date, $weight, $bf, $muscle]);

    // Actualizează și user_stats cu ultima greutate
    $db->prepare('INSERT INTO user_stats (user_id, weight, bodyfat) VALUES (?,?,?) ON DUPLICATE KEY UPDATE weight=VALUES(weight), bodyfat=VALUES(bodyfat)')
       ->execute([$userId, $weight, $bf]);

    jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId(), 'message' => 'Măsurătoare salvată']);
}

// ── DELETE: șterge măsurătoare ──────────────────────
function deleteMeasurement(int $userId): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID lipsă'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM measurements WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Înregistrare negăsită'], 404);
    }

    jsonResponse(['success' => true, 'message' => 'Măsurătoare ștearsă']);
}
