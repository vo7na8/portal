<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_nsi')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '') ?: null;
$sort_order  = (int)($_POST['sort_order'] ?? 0);
$is_active   = isset($_POST['is_active']) ? 1 : 0;

if (!$id || empty($name)) { jsonError('Неверные данные'); }

try {
    $row = $pdo->prepare('SELECT id FROM nsi_dictionaries WHERE id = ?');
    $row->execute([$id]);
    if (!$row->fetch()) { jsonError('Справочник не найден', 404); }

    $pdo->prepare('
        UPDATE nsi_dictionaries
        SET name = ?, description = ?, sort_order = ?, is_active = ?
        WHERE id = ?
    ')->execute([$name, $description, $sort_order, $is_active, $id]);

    logAction($pdo, $_SESSION['user_id'], 'edit_nsi_dictionary', "Изменён справочник ID:{$id} '{$name}'");
    jsonSuccess(['message' => 'Справочник обновлён']);
} catch (PDOException $e) {
    logError('edit_nsi_dictionary: ' . $e->getMessage());
    jsonError('Ошибка при обновлении справочника');
}
