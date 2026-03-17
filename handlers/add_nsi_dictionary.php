<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/security.php';

if (!isLoggedIn()) { jsonError('Не авторизован', 401); }
if (!hasPermission($pdo, 'manage_nsi')) { jsonError('Нет прав', 403); }
if (!verifyCsrf($_POST['csrf_token'] ?? '')) { jsonError('Неверный токен', 403); }

$code        = trim($_POST['code'] ?? '');
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '') ?: null;
$sort_order  = (int)($_POST['sort_order'] ?? 0);

if (empty($code) || empty($name)) { jsonError('Код и наименование обязательны'); }
if (!preg_match('/^[a-z_][a-z0-9_]{1,48}$/i', $code)) {
    jsonError('Код справочника: буквы/цифры/_, начинается с буквы, до 50 символов');
}

try {
    $dup = $pdo->prepare('SELECT id FROM nsi_dictionaries WHERE code = ?');
    $dup->execute([$code]);
    if ($dup->fetch()) { jsonError('Справочник с таким кодом уже существует'); }

    $pdo->prepare('
        INSERT INTO nsi_dictionaries (code, name, description, sort_order)
        VALUES (?, ?, ?, ?)
    ')->execute([$code, $name, $description, $sort_order]);
    $newId = $pdo->lastInsertId();

    logAction($pdo, $_SESSION['user_id'], 'add_nsi_dictionary', "Создан справочник '{$name}' ({$code})");
    jsonSuccess(['id' => $newId, 'message' => 'Справочник создан']);
} catch (PDOException $e) {
    logError('add_nsi_dictionary: ' . $e->getMessage());
    jsonError('Ошибка при создании справочника');
}
