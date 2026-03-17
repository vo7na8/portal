<?php
/**
 * AJAX: получить значения справочника НСИ по коду или ID
 * GET ?code=equipment_types  ИЛИ  ?dictionary_id=3
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }

$code    = trim($_GET['code'] ?? '');
$dictId  = (int)($_GET['dictionary_id'] ?? 0);

if (empty($code) && !$dictId) { jsonError('Укажите code или dictionary_id'); }

try {
    if ($code) {
        $dict = $pdo->prepare('SELECT id FROM nsi_dictionaries WHERE code = ? AND is_active = 1');
        $dict->execute([$code]);
        $row = $dict->fetch(PDO::FETCH_ASSOC);
        if (!$row) { jsonError('Справочник не найден', 404); }
        $dictId = (int)$row['id'];
    }

    $values = $pdo->prepare('
        SELECT id, value, display_text, parent_id, sort_order
        FROM nsi_values
        WHERE dictionary_id = ? AND is_active = 1
        ORDER BY sort_order ASC, display_text ASC
    ');
    $values->execute([$dictId]);
    $rows = $values->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess(['values' => $rows]);
} catch (PDOException $e) {
    logError('get_nsi_values: ' . $e->getMessage());
    jsonError('Ошибка получения значений');
}
