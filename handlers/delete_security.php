<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'delete_security')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM security WHERE id = ?");
$stmt->execute([$id]);
header('Location: ../main.php?page=security');
exit;