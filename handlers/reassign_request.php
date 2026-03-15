<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'reassign_request')) { flash('error', 'Недостаточно прав'); redirect('../main.php?page=requests'); }
$security->requireCsrf();
$id         = (int)($_POST['request_id'] ?? 0);
$assignedTo = isset($_POST['assigned_to']) && (int)$_POST['assigned_to'] > 0 ? (int)$_POST['assigned_to'] : null;
if ($id <= 0) { flash('error', 'Неверный ID.'); redirect('../main.php?page=requests'); }
$db  = Database::getInstance();
$who = $_SESSION['user_name'] ?? 'Пользователь';
$old = $db->selectOne(
    'SELECT r.assigned_to, u.full_name AS old_name FROM requests r LEFT JOIN users u ON r.assigned_to=u.id WHERE r.id=?',
    [$id]
);
$newStatus = $assignedTo ? 'в работе' : 'новая';
$db->update('requests',
    ['assigned_to' => $assignedTo, 'status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')],
    'id = ?', [$id]
);
$newName = '';
if ($assignedTo) {
    $nu = $db->selectOne('SELECT full_name FROM users WHERE id=?', [$assignedTo]);
    $newName = $nu['full_name'] ?? '';
}
$oldName = $old['old_name'] ?? '—';
$logMsg  = $assignedTo
    ? "Ответственный изменён: «{$oldName}» → «{$newName}» пользователем «{$who}»"
    : "Ответственный снят (был: «{$oldName}»), заявка возвращена в «новая» пользователем «{$who}»";
$db->insert('request_comments', [
    'request_id' => $id,
    'user_id'    => (int)$_SESSION['user_id'],
    'body'       => $logMsg,
    'is_log'     => 1,
]);
flash('success', $assignedTo ? 'Заявка переназначена.' : 'Ответственный снят.');
redirect('../main.php?page=requests');
