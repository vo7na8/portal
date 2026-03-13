<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_vacation')) die('Недостаточно прав');
$user_id = $_POST['user_id'] ?? 0;
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
if (!$user_id || !$start_date || !$end_date) die('Заполните все поля');
$stmt = $pdo->prepare("INSERT INTO vacations (user_id, start_date, end_date) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $start_date, $end_date]);
header('Location: ../main.php?page=vacations');
exit;