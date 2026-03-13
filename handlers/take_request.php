<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'take_request')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("UPDATE requests SET status = 'в работе', assigned_to = ? WHERE id = ?");
$stmt->execute([$_SESSION['user_id'], $id]);
header('Location: ../main.php?page=requests');
exit;