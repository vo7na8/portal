<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_templates')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$template_id  = (int)($_POST['template_id'] ?? 0);
$field_name   = trim($_POST['field_name'] ?? '');
$field_type   = trim($_POST['field_type'] ?? '');
$field_label  = trim($_POST['field_label'] ?? '');
$placeholder  = trim($_POST['placeholder'] ?? '');
$is_required  = isset($_POST['is_required']) ? 1 : 0;
$sort_order   = (int)($_POST['sort_order'] ?? 0);
$options_source    = trim($_POST['options_source'] ?? '') ?: null;
$validation_rules  = trim($_POST['validation_rules'] ?? '') ?: null;

$allowed_types = ['text', 'textarea', 'select', 'checkbox', 'number', 'date', 'file'];

if (!$template_id || empty($field_name) || empty($field_type) || empty($field_label)) {
    jsonError('Обязательные поля не заполнены');
}
if (!in_array($field_type, $allowed_types)) {
    jsonError('Недопустимый тип поля');
}
if (!preg_match('/^[a-z_][a-z0-9_]{1,50}$/i', $field_name)) {
    jsonError('Имя поля: только буквы, цифры и _, от 2 символов');
}
if ($validation_rules && !json_decode($validation_rules)) {
    jsonError('Правила валидации должны быть корректным JSON');
}

try {
    $tmpl = $pdo->prepare('SELECT id FROM request_templates WHERE id = ?');
    $tmpl->execute([$template_id]);
    if (!$tmpl->fetch()) { jsonError('Шаблон не найден', 404); }

    $dup = $pdo->prepare('SELECT id FROM template_fields WHERE template_id = ? AND field_name = ?');
    $dup->execute([$template_id, $field_name]);
    if ($dup->fetch()) { jsonError('Поле с таким именем уже есть в шаблоне'); }

    $stmt = $pdo->prepare('
        INSERT INTO template_fields
            (template_id, field_name, field_type, field_label, placeholder,
             is_required, sort_order, options_source, validation_rules)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $template_id, $field_name, $field_type, $field_label,
        $placeholder, $is_required, $sort_order, $options_source, $validation_rules
    ]);
    $fieldId = $pdo->lastInsertId();

    logAction($pdo, $_SESSION['user_id'], 'add_template_field',
        "Добавлено поле '{$field_label}' в шаблон ID:{$template_id}");

    jsonSuccess(['id' => $fieldId, 'message' => 'Поле добавлено']);
} catch (PDOException $e) {
    logError('add_template_field: ' . $e->getMessage());
    jsonError('Ошибка при добавлении поля');
}
