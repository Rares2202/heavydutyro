<?php
// =====================================================
// HeavyDutyRO — API Jurnal Nutriție
// GET  /api/nutrition.php           → lista (ultimele 30 zile)
// POST /api/nutrition.php           → adaugă / actualizează ziua
// DELETE /api/nutrition.php?id=X    → șterge
// =====================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

$userId = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

match ($method) {
    'GET'    => getNutrition($userId),
    'POST'   => saveNutrition($userId),
    'DELETE' => deleteNutrition($userId),
    default  => jsonResponse(['success' => false, 'message' => 'Metodă nepermisă'], 405),
};

function getNutrition(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare('
        SELECT id, date, calories, protein, carbs, fat, notes
        FROM nutrition_logs
        WHERE user_id = ?
        ORDER BY date DESC
        LIMIT 90
    ');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $result = array_map(fn($r) => [
        'id'       => (int)$r['id'],
        'date'     => $r['date'],
        'calories' => (int)$r['calories'],
        'protein'  => (float)$r['protein'],
        'carbs'    => (float)$r['carbs'],
        'fat'      => (float)$r['fat'],
        'notes'    => $r['notes'] ?? '',
    ], $rows);

    jsonResponse(['success' => true, 'data' => $result]);
}

function saveNutrition(int $userId): void {
    $data     = getJsonBody();
    $date     = $data['date']     ?? date('Y-m-d');
    $calories = (int)($data['calories'] ?? 0);
    $protein  = (float)($data['protein'] ?? 0);
    $carbs    = (float)($data['carbs']   ?? 0);
    $fat      = (float)($data['fat']     ?? 0);
    $notes    = $data['notes']    ?? '';

    $db   = getDB();
    $stmt = $db->prepare('
        INSERT INTO nutrition_logs (user_id, date, calories, protein, carbs, fat, notes)
        VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            calories = VALUES(calories),
            protein  = VALUES(protein),
            carbs    = VALUES(carbs),
            fat      = VALUES(fat),
            notes    = VALUES(notes)
    ');
    $stmt->execute([$userId, $date, $calories, $protein, $carbs, $fat, $notes]);

    jsonResponse(['success' => true, 'message' => 'Nutriție salvată']);
}

function deleteNutrition(int $userId): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID lipsă'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM nutrition_logs WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    jsonResponse(['success' => true, 'message' => 'Înregistrare ștearsă']);
}
