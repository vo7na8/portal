<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_person')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=persons'); }
$security->requireCsrf();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Неверный ID'); redirect('../main.php?page=persons'); }
$lastName  = trim($_POST['last_name']  ?? '');
$firstName = trim($_POST['first_name'] ?? '');
if ($lastName === '' || $firstName === '') { flash('error', 'Фамилия и Имя обязательны'); redirect('../main.php?page=persons'); }
Database::getInstance()->update('persons', [
    'last_name'       => $lastName,
    'first_name'      => $firstName,
    'middle_name'     => trim($_POST['middle_name'] ?? '') ?: null,
    'birth_date'      => $_POST['birth_date']       ?: null,
    'has_eds'         => isset($_POST['has_eds'])    ? 1 : 0,
    'eds_cert_number' => trim($_POST['eds_cert_number'] ?? '') ?: null,
    'eds_valid_until' => $_POST['eds_valid_until']   ?: null,
    'note'            => trim($_POST['note'] ?? '')  ?: null,
    'updated_at'      => date('Y-m-d H:i:s'),
], 'id = ?', [$id]);
flash('success', 'Данные обновлены.');
redirect('../main.php?page=persons');
