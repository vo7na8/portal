<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_birthday')) die('Недостаточно прав');
$full_name = trim($_POST['full_name'] ?? '');
$birth_date = $_POST['birth_date'] ?? '';
if ($full_name === '' || $birth_date === '') die('Заполните все поля');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) die('Неверный формат даты');
$stmt = $pdo->prepare("INSERT INTO birthdays (full_name, birth_date) VALUES (?, ?)");
$stmt->execute([$full_name, $birth_date]);
header('Location: ../main.php?page=birthdays');
exit;