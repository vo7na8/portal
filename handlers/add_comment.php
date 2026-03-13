<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'add_comment')) die('Недостаточно прав');
$equipment_id = $_POST['equipment_id'] ?? 0;
$comment = trim($_POST['comment'] ?? '');
if (!$equipment_id || $comment === '') die('Некорректные данные');
$stmt = $pdo->prepare("INSERT INTO comments (equipment_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->execute([$equipment_id, $_SESSION['user_id'], $comment]);
header('Location: ../main.php?page=equipment');
exit;