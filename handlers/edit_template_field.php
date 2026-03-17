<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_templates')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$id            = (int)($_POST['id'] ?? 0);
$field_label   = trim($_POST['field_label'] ?? '');
$field_type    = trim($_POST['field_type'] ?? '');
$placeholder   = trim($_POST['placeholder'] ?? '');
$is_required   = isset($_POST['is_required']) ? 1 : 0;
$sort_order    = (int)($_POST['sort_order'] ?? 0);
$options_source    = trim($_POST['options_source'] ?? '') ?: null;
$validation_rules  = trim($_POST['validation_rules'] ?? '') ?: null;

$allowed_types = ['text', 'textarea', 'select', 'checkbox', 'number', 'date', 'file'];

if (!$id || empty($field_label) || empty($field_type)) { jsonError('Неверные данные'); }
if (!in_array($field_type, $allowed_types)) { jsonError('Недопустимый тип поля'); }
if ($validation_rules && !json_decode($validation_rules)) { jsonError('Неверный JSON в правилах валидации'); }

try {
    $field = $pdo->prepare('SELECT id FROM template_fields WHERE id = ?');
    $field->execute([$id]);
    if (!$field->fetch()) { jsonError('Поле не найдено', 404); }

    $stmt = $pdo->prepare('
        UPDATE template_fields
        SET field_label = ?, field_type = ?, placeholder = ?,
            is_required = ?, sort_order = ?, options_source = ?, validation_rules = ?
        WHERE id = ?
    ');
    $stmt->execute([
        $field_label, $field_type, $placeholder,
        $is_required, $sort_order, $options_source, $validation_rules,
        $id
    ]);

    logAction($pdo, $_SESSION['user_id'], 'edit_template_field', "Изменено поле ID:{$id}");
    jsonSuccess(['message' => 'Поле обновлено']);
} catch (PDOException $e) {
    logError('edit_template_field: ' . $e->getMessage());
    jsonError('Ошибка при обновлении поля');
}
