<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
// Защита от удаления базовых ролей (admin, ib, executor, user)
$stmt = $pdo->prepare("DELETE FROM roles WHERE id = ? AND name NOT IN ('admin', 'ib', 'executor', 'user')");
$stmt->execute([$id]);
header('Location: ../main.php?page=roles');
exit;