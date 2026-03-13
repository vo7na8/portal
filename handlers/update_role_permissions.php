<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'manage_roles')) die('Недостаточно прав');
$role_id = $_POST['role_id'] ?? 0;
$perms = $_POST['perms'] ?? []; // массив id разрешений
// Удаляем старые
$pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);
// Вставляем новые
$stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
foreach ($perms as $perm_id) {
    $stmt->execute([$role_id, $perm_id]);
}
header('Location: ../main.php?page=roles');
exit;