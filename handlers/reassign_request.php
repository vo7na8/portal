<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'reassign_request')) die('Недостаточно прав');
$request_id = $_POST['request_id'] ?? 0;
$new_assignee = $_POST['new_assignee'] ?? 0;
if (!$request_id || !$new_assignee) die('Не все данные');
$stmt = $pdo->prepare("UPDATE requests SET assigned_to = ? WHERE id = ?");
$stmt->execute([$new_assignee, $request_id]);
header('Location: ../main.php?page=requests');
exit;