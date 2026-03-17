<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_templates')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$category_code = trim($_POST['category_code'] ?? '');
$category_name = trim($_POST['category_name'] ?? '');
$title_template = trim($_POST['title_template'] ?? '');
$icon = trim($_POST['icon'] ?? 'fa-ticket-alt');
$sort_order = (int)($_POST['sort_order'] ?? 0);

if (empty($category_code) || empty($category_name)) {
    jsonError('Код и название категории обязательны');
}

if (!preg_match('/^[A-Z0-9]{2,10}$/i', $category_code)) {
    jsonError('Код категории: только буквы и цифры, 2-10 символов');
}

try {
    $exists = $pdo->prepare('SELECT id FROM request_templates WHERE category_code = ?');
    $exists->execute([strtoupper($category_code)]);
    if ($exists->fetch()) {
        jsonError('Шаблон с таким кодом уже существует');
    }

    $stmt = $pdo->prepare('
        INSERT INTO request_templates (category_code, category_name, title_template, icon, sort_order)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        strtoupper($category_code),
        $category_name,
        $title_template,
        $icon,
        $sort_order
    ]);
    $templateId = $pdo->lastInsertId();

    logAction($pdo, $_SESSION['user_id'], 'create_template', "Создан шаблон: {$category_name} ({$category_code})");

    jsonSuccess(['id' => $templateId, 'message' => 'Шаблон создан']);
} catch (PDOException $e) {
    logError('add_template: ' . $e->getMessage());
    jsonError('Ошибка при создании шаблона');
}
