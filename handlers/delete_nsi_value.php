<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_nsi')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { jsonError('Неверный ID'); }

try {
    $row = $pdo->prepare('SELECT id, display_text FROM nsi_values WHERE id = ?');
    $row->execute([$id]);
    $nsiRow = $row->fetch();
    if (!$nsiRow) { jsonError('Значение не найдено', 404); }

    // Мягкое удаление: деактивация, не физическое удаление (значение могло использоваться в заявках)
    $pdo->prepare('UPDATE nsi_values SET is_active = 0 WHERE id = ?')->execute([$id]);

    logAction($pdo, $_SESSION['user_id'], 'delete_nsi_value', "Деактивировано значение НСИ ID:{$id} '{$nsiRow['display_text']}'");
    jsonSuccess(['message' => 'Значение деактивировано']);
} catch (PDOException $e) {
    logError('delete_nsi_value: ' . $e->getMessage());
    jsonError('Ошибка при удалении значения');
}
