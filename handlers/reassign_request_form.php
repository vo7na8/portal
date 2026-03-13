<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'reassign_request')) {
    flash('error', 'Недостаточно прав');
    redirect('main.php?page=requests');
}
$id      = (int)($_GET['id'] ?? 0);
$db      = Database::getInstance();
$request = $db->selectOne('SELECT r.*, u.full_name as author_name FROM requests r LEFT JOIN users u ON r.author_id=u.id WHERE r.id=?', [$id]);
if (!$request) { flash('error', 'Заявка не найдена.'); redirect('main.php?page=requests'); }
$users   = $db->select('SELECT id, full_name FROM users ORDER BY full_name');
$statusMap = ['новая'=>'badge-new', 'в работе'=>'badge-progress', 'выполнена'=>'badge-done'];
$config  = Config::getInstance();
$appName = $config->getAppName();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Переназначить заявку — <?= e($appName) ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <script src="../script.js" defer></script>
</head>
<body style="align-items:flex-start;padding:2rem">
<div style="max-width:540px;margin:0 auto;width:100%">

    <div class="flex-between mb-2">
        <h2 style="font-weight:300;font-size:1.4rem;color:var(--accent-pink)">Переназначить заявку</h2>
        <a href="../main.php?page=requests" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Назад
        </a>
    </div>

    <!-- Инфо о заявке -->
    <div class="card mb-2">
        <div class="card-header">
            <span class="card-title"><?= e($request['title']) ?></span>
            <span class="badge <?= $statusMap[$request['status']] ?? 'badge-closed' ?>"><?= e($request['status']) ?></span>
        </div>
        <?php if ($request['description']): ?>
        <p style="color:var(--text-secondary);font-size:.9rem;margin-top:.5rem"><?= e($request['description']) ?></p>
        <?php endif; ?>
        <div class="text-muted mt-1" style="font-size:.8rem">
            <i class="fas fa-user"></i> <?= e($request['author_name'] ?? '—') ?>
            &nbsp;<i class="fas fa-clock"></i> <?= format_datetime($request['created_at'] ?? '') ?>
        </div>
    </div>

    <!-- Форма -->
    <div class="form-container">
        <div class="card-title mb-2">Выберите нового исполнителя</div>
        <form method="post" action="reassign_request.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label>Исполнитель</label>
                <select name="user_id" required>
                    <option value="">— выберите —</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)($request['assigned_to'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= e($u['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-check"></i> Переназначить
            </button>
        </form>
    </div>

</div>
</body>
</html>
