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
    $field = $pdo->prepare('SELECT id, field_label FROM template_fields WHERE id = ?');
    $field->execute([$id]);
    $row = $field->fetch();
    if (!$row) { jsonError('Поле не найдено', 404); }

    // Удаляем значения этого поля из всех заявок
    $pdo->prepare('DELETE FROM request_field_values WHERE field_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM template_fields WHERE id = ?')->execute([$id]);

    logAction($pdo, $_SESSION['user_id'], 'delete_template_field',
        "Удалено поле '{$row['field_label']}' ID:{$id}");
    jsonSuccess(['message' => 'Поле удалено']);
} catch (PDOException $e) {
    logError('delete_template_field: ' . $e->getMessage());
    jsonError('Ошибка при удалении поля');
}
