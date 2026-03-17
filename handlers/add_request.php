<?php
/**
 * handlers/add_request.php  — Sprint 2
 * Создание заявки с поддержкой шаблонов, динамических полей и нумерации.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/request_number.php';

if (!hasPermission($pdo, 'add_request')) {
    flash('error', 'Недостаточно прав');
    redirect('../main.php?page=requests');
}
$security->requireCsrf();

// --- Базовые поля ---
$v = Validator::make($_POST);
if (!$v->validate(['title' => 'required|max:255'])) {
    flash('error', $v->firstErrorMessage());
    redirect('../main.php?page=requests');
}
$d = $v->validated();

$templateId = (int)($_POST['template_id'] ?? 0);
if (!$templateId) {
    // Fallback на «Общая заявка», если шаблон не выбран
    $templateId = getDefaultTemplateId($pdo);
}

// Проверяем что шаблон существует и активен
$tmplRow = null;
if ($templateId) {
    $tmplStmt = $pdo->prepare('SELECT id, category_code, title_template FROM request_templates WHERE id = ? AND is_active = 1');
    $tmplStmt->execute([$templateId]);
    $tmplRow = $tmplStmt->fetch(PDO::FETCH_ASSOC);
}
if (!$tmplRow) {
    flash('error', 'Выбранный шаблон недоступен');
    redirect('../main.php?page=requests');
}

// --- Назначение ответственного (только Администратор / Менеджер) ---
$assignedTo = null;
if (hasPermission($pdo, 'assign_request') && !empty($_POST['assigned_to'])) {
    $assignedTo = (int)$_POST['assigned_to'] ?: null;
}
$status = $assignedTo ? 'в работе' : 'новая';

// --- Заголовок: автоподстановка из шаблона или прямой ввод ---
$title = trim($d['title']);

// --- Динамические поля шаблона ---
$fields = $pdo->prepare('
    SELECT id, field_name, field_type, is_required, validation_json
    FROM template_fields
    WHERE template_id = ?
    ORDER BY sort_order ASC
');
$fields->execute([$templateId]);
$templateFields = $fields->fetchAll(PDO::FETCH_ASSOC);

$fieldValues = [];
$errors      = [];

foreach ($templateFields as $tf) {
    $rawVal = $_POST['field_' . $tf['field_name']] ?? '';
    $val    = is_array($rawVal) ? implode(', ', array_map('trim', $rawVal)) : trim((string)$rawVal);

    // Проверка обязательности
    if ($tf['is_required'] && $val === '') {
        $errors[] = "Поле «{$tf['field_name']}» обязательно";
        continue;
    }

    // Валидация по JSON-правилам
    if ($val !== '' && $tf['validation_json']) {
        $rules = json_decode($tf['validation_json'], true) ?? [];
        if (!empty($rules['pattern']) && !preg_match('/' . $rules['pattern'] . '/u', $val)) {
            $msg = $rules['pattern_message'] ?? "Неверный формат поля «{$tf['field_name']}»";
            $errors[] = $msg;
            continue;
        }
        if (isset($rules['max_length']) && mb_strlen($val) > (int)$rules['max_length']) {
            $errors[] = "Поле «{$tf['field_name']}» слишком длинное (макс. {$rules['max_length']})";
            continue;
        }
    }

    $fieldValues[$tf['id']] = $val;
}

if (!empty($errors)) {
    flash('error', implode('; ', $errors));
    redirect('../main.php?page=requests');
}

// --- Сохраняем в транзакции ---
try {
    $pdo->beginTransaction();

    // Генерируем номер заявки
    $requestNumber = generateRequestNumber($pdo, $templateId);

    // Вставляем заявку
    $db = Database::getInstance();
    $db->insert('requests', [
        'title'          => $title,
        'description'    => $_POST['description'] ?? '',
        'status'         => $status,
        'author_id'      => (int)$_SESSION['user_id'],
        'assigned_to'    => $assignedTo,
        'template_id'    => $templateId,
        'request_number' => $requestNumber,
        'updated_at'     => date('Y-m-d H:i:s'),
    ]);
    $requestId = $pdo->lastInsertId();

    // Сохраняем значения динамических полей
    if (!empty($fieldValues)) {
        $stmtFv = $pdo->prepare('
            INSERT INTO request_field_values (request_id, field_id, value)
            VALUES (?, ?, ?)
        ');
        foreach ($fieldValues as $fieldId => $value) {
            if ($value !== '') {
                $stmtFv->execute([$requestId, $fieldId, $value]);
            }
        }
    }

    $pdo->commit();

    logAction($pdo, $_SESSION['user_id'], 'create_request',
        "Создана заявка #{$requestNumber} '{$title}'");

    flash('success', "Заявка {$requestNumber} создана.");
    redirect('../main.php?page=requests');

} catch (\Throwable $e) {
    $pdo->rollBack();
    logError('add_request: ' . $e->getMessage());
    flash('error', 'Ошибка при создании заявки');
    redirect('../main.php?page=requests');
}
