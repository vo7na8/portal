<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_templates')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$id = (int)($_POST['id'] ?? 0);
$category_code = trim($_POST['category_code'] ?? '');
$category_name = trim($_POST['category_name'] ?? '');
$title_template = trim($_POST['title_template'] ?? '');
$icon = trim($_POST['icon'] ?? 'fa-ticket-alt');
$sort_order = (int)($_POST['sort_order'] ?? 0);
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (!$id || empty($category_code) || empty($category_name)) {
    jsonError('Неверные данные');
}

if (!preg_match('/^[A-Z0-9]{2,10}$/i', $category_code)) {
    jsonError('Код категории: только буквы и цифры, 2-10 символов');
}

try {
    $template = $pdo->prepare('SELECT id FROM request_templates WHERE id = ?');
    $template->execute([$id]);
    if (!$template->fetch()) { jsonError('Шаблон не найден', 404); }

    $exists = $pdo->prepare('SELECT id FROM request_templates WHERE category_code = ? AND id != ?');
    $exists->execute([strtoupper($category_code), $id]);
    if ($exists->fetch()) { jsonError('Шаблон с таким кодом уже существует'); }

    $stmt = $pdo->prepare('
        UPDATE request_templates
        SET category_code = ?, category_name = ?, title_template = ?,
            icon = ?, sort_order = ?, is_active = ?
        WHERE id = ?
    ');
    $stmt->execute([
        strtoupper($category_code),
        $category_name,
        $title_template,
        $icon,
        $sort_order,
        $is_active,
        $id
    ]);

    logAction($pdo, $_SESSION['user_id'], 'edit_template', "Изменён шаблон ID:{$id} ({$category_name})");
    jsonSuccess(['message' => 'Шаблон обновлён']);
} catch (PDOException $e) {
    logError('edit_template: ' . $e->getMessage());
    jsonError('Ошибка при обновлении шаблона');
}
