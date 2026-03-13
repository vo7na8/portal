<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_security')) die('Недостаточно прав');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
if ($title === '') die('Введите заголовок');
$stmt = $pdo->prepare("INSERT INTO security (title, description) VALUES (?, ?)");
$stmt->execute([$title, $description]);
header('Location: ../main.php?page=security');
exit;