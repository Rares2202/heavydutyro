<?php
// =====================================================
// HeavyDutyRO — API Nutriție (dezactivat)
// Pagina de nutriție este statică și nu mai salvează jurnal în DB.
// =====================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

requireAuth();
jsonResponse([
    'success' => false,
    'message' => 'Jurnalul de nutriție este dezactivat. Pagina de nutriție folosește doar calculator local.',
], 410);
