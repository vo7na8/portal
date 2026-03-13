<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_request')) die('Недостаточно прав');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
if ($title === '') die('Введите тему заявки');
$stmt = $pdo->prepare("INSERT INTO requests (title, description, created_by) VALUES (?, ?, ?)");
$stmt->execute([$title, $description, $_SESSION['user_id']]);
header('Location: ../main.php?page=requests');
exit;