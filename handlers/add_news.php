<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_news')) die('Недостаточно прав');
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
if ($title === '' || $content === '') die('Заполните все поля');
$stmt = $pdo->prepare("INSERT INTO news (title, content, updated_by) VALUES (?, ?, ?)");
$stmt->execute([$title, $content, $_SESSION['user_id']]);
header('Location: ../main.php?page=news');
exit;