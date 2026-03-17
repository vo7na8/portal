<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_nsi')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$id           = (int)($_POST['id'] ?? 0);
$display_text = trim($_POST['display_text'] ?? '');
$sort_order   = (int)($_POST['sort_order'] ?? 0);
$is_active    = isset($_POST['is_active']) ? 1 : 0;
$metadata     = trim($_POST['metadata'] ?? '') ?: null;

if (!$id || empty($display_text)) { jsonError('Неверные данные'); }
if ($metadata && !json_decode($metadata)) { jsonError('Метаданные должны быть корректным JSON'); }

try {
    $row = $pdo->prepare('SELECT id FROM nsi_values WHERE id = ?');
    $row->execute([$id]);
    if (!$row->fetch()) { jsonError('Значение не найдено', 404); }

    $pdo->prepare('
        UPDATE nsi_values
        SET display_text = ?, sort_order = ?, is_active = ?, metadata = ?
        WHERE id = ?
    ')->execute([$display_text, $sort_order, $is_active, $metadata, $id]);

    logAction($pdo, $_SESSION['user_id'], 'edit_nsi_value', "Изменено значение НСИ ID:{$id} '{$display_text}'");
    jsonSuccess(['message' => 'Значение обновлено']);
} catch (PDOException $e) {
    logError('edit_nsi_value: ' . $e->getMessage());
    jsonError('Ошибка при обновлении значения');
}
