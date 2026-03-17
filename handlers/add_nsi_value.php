<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_nsi')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$dictionary_id = (int)($_POST['dictionary_id'] ?? 0);
$value         = trim($_POST['value'] ?? '');
$display_text  = trim($_POST['display_text'] ?? '');
$parent_id     = (int)($_POST['parent_id'] ?? 0) ?: null;
$sort_order    = (int)($_POST['sort_order'] ?? 0);
$metadata      = trim($_POST['metadata'] ?? '') ?: null;

if (!$dictionary_id || empty($value) || empty($display_text)) {
    jsonError('Справочник, код и наименование обязательны');
}
if (!preg_match('/^[a-z0-9_\-]{1,100}$/i', $value)) {
    jsonError('Код значения: только буквы, цифры, _ и -, до 100 символов');
}
if ($metadata && !json_decode($metadata)) {
    jsonError('Метаданные должны быть корректным JSON');
}

try {
    $dict = $pdo->prepare('SELECT id FROM nsi_dictionaries WHERE id = ? AND is_active = 1');
    $dict->execute([$dictionary_id]);
    if (!$dict->fetch()) { jsonError('Справочник не найден', 404); }

    $dup = $pdo->prepare('SELECT id FROM nsi_values WHERE dictionary_id = ? AND value = ?');
    $dup->execute([$dictionary_id, $value]);
    if ($dup->fetch()) { jsonError('Значение с таким кодом уже существует в справочнике'); }

    $stmt = $pdo->prepare('
        INSERT INTO nsi_values (dictionary_id, value, display_text, parent_id, sort_order, metadata)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$dictionary_id, $value, $display_text, $parent_id, $sort_order, $metadata]);
    $newId = $pdo->lastInsertId();

    logAction($pdo, $_SESSION['user_id'], 'add_nsi_value', "Добавлено значение '{$display_text}' в справочник ID:{$dictionary_id}");
    jsonSuccess(['id' => $newId, 'message' => 'Значение добавлено']);
} catch (PDOException $e) {
    logError('add_nsi_value: ' . $e->getMessage());
    jsonError('Ошибка при добавлении значения');
}
