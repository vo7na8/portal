<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_person')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();

$db         = Database::getInstance();
$lastName   = trim($_POST['last_name']   ?? '');
$firstName  = trim($_POST['first_name']  ?? '');
$middleName = trim($_POST['middle_name'] ?? '');

if ($lastName === '' || $firstName === '') {
    flash('error', 'Фамилия и имя обязательны.');
    redirect('../main.php?page=persons');
}

// Проверка дубликата по ФИО
$exists = $db->selectValue(
    'SELECT COUNT(*) FROM persons WHERE last_name=? AND first_name=? AND COALESCE(middle_name,"")=?',
    [$lastName, $firstName, $middleName]
);
if ($exists) {
    flash('error', 'Физическое лицо с таким ФИО уже существует.');
    redirect('../main.php?page=persons');
}

// Валидация дат
$birthDate    = $_POST['birth_date']    ?: null;
$edsValidUntil = $_POST['eds_valid_until'] ?: null;
if ($birthDate && strtotime($birthDate) > time()) {
    flash('error', 'Дата рождения не может быть в будущем.');
    redirect('../main.php?page=persons');
}

$db->insert('persons', [
    'last_name'       => $lastName,
    'first_name'      => $firstName,
    'middle_name'     => $middleName ?: null,
    'birth_date'      => $birthDate,
    'has_eds'         => isset($_POST['has_eds']) ? 1 : 0,
    'eds_cert_number' => trim($_POST['eds_cert_number'] ?? '') ?: null,
    'eds_valid_until' => $edsValidUntil,
    'note'            => trim($_POST['note'] ?? '') ?: null,
]);
flash('success', 'Физическое лицо добавлено.');
redirect('../main.php?page=persons');
