<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_templates')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { jsonError('Неверный ID'); }

try {
    // Проверить, есть ли заявки по этому шаблону
    $used = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE template_id = ?');
    $used->execute([$id]);
    if ((int)$used->fetchColumn() > 0) {
        // Мягкое удаление: деактивировать вместо удаления
        $pdo->prepare('UPDATE request_templates SET is_active = 0 WHERE id = ?')->execute([$id]);
        logAction($pdo, $_SESSION['user_id'], 'deactivate_template', "Деактивирован шаблон ID:{$id}");
        jsonSuccess(['message' => 'Шаблон деактивирован (есть связанные заявки)']);
    } else {
        $pdo->prepare('DELETE FROM request_templates WHERE id = ?')->execute([$id]);
        logAction($pdo, $_SESSION['user_id'], 'delete_template', "Удалён шаблон ID:{$id}");
        jsonSuccess(['message' => 'Шаблон удалён']);
    }
} catch (PDOException $e) {
    logError('delete_template: ' . $e->getMessage());
    jsonError('Ошибка при удалении шаблона');
}
