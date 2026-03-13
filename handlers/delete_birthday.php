<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_birthday')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM birthdays WHERE id = ?");
$stmt->execute([$id]);
header('Location: ../main.php?page=birthdays');
exit;