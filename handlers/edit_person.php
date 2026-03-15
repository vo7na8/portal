<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_person')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=persons'); }

$lastName   = trim($_POST['last_name']   ?? '');
$firstName  = trim($_POST['first_name']  ?? '');
$middleName = trim($_POST['middle_name'] ?? '');

if ($lastName === '' || $firstName === '') {
    flash('error', 'Фамилия и Имя обязательны.');
    redirect('../main.php?page=persons');
}

// Проверка: дубликат ФИО среди других записей
$db = Database::getInstance();
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM persons WHERE last_name=? AND first_name=? AND COALESCE(middle_name,"")=? AND id!=?',
    [$lastName, $firstName, $middleName, $id]
);
if ($exists) {
    flash('error', 'Физическое лицо с таким ФИО уже существует.');
    redirect('../main.php?page=persons');
}

// Валидация дат
$birthDate     = $_POST['birth_date']     ?: null;
$edsValidUntil = $_POST['eds_valid_until'] ?: null;
if ($birthDate && strtotime($birthDate) > time()) {
    flash('error', 'Дата рождения не может быть в будущем.');
    redirect('../main.php?page=persons');
}

$db->update('persons', [
    'last_name'       => $lastName,
    'first_name'      => $firstName,
    'middle_name'     => $middleName ?: null,
    'birth_date'      => $birthDate,
    'has_eds'         => isset($_POST['has_eds']) ? 1 : 0,
    'eds_cert_number' => trim($_POST['eds_cert_number'] ?? '') ?: null,
    'eds_valid_until' => $edsValidUntil,
    'note'            => trim($_POST['note'] ?? '') ?: null,
    'updated_at'      => date('Y-m-d H:i:s'),
], 'id = ?', [$id]);
flash('success', 'Данные обновлены.');
redirect('../main.php?page=persons');
