<?php
if (!hasPermission($pdo, 'view_requests')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db = Database::getInstance();
$canAdd      = hasPermission($pdo, 'add_request');
$canTake     = hasPermission($pdo, 'take_request');
$canComplete = hasPermission($pdo, 'complete_request');
$canReassign = hasPermission($pdo, 'reassign_request');

$statusFilter = $_GET['status'] ?? '';
$where  = $statusFilter ? 'WHERE r.status = ?' : '';
$params = $statusFilter ? [$statusFilter] : [];
$requests = $db->select("SELECT r.*, u.full_name as author_name, a.full_name as assigned_name
    FROM requests r
    LEFT JOIN users u ON r.author_id=u.id
    LEFT JOIN users a ON r.assigned_to=a.id
    {$where}
    ORDER BY r.created_at DESC", $params);
$statusMap = ['новая'=>'badge-new', 'в работе'=>'badge-progress', 'выполнена'=>'badge-done'];
?>
<h2 class="section-title">Заявки</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Новая заявка</div>
    <form method="post" action="handlers/add_request.php">
        <?= csrf_field() ?>
        <div class="form-group"><label>Тема</label><input type="text" name="title" required maxlength="255"></div>
        <div class="form-group"><label>Описание</label><textarea name="description" required rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Создать</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($requests)): ?>
    <div class="empty-state"><i class="fas fa-clipboard-list"></i><p>Заявок нет</p></div>
<?php else: foreach ($requests as $r): ?>
    <div class="item-row">
        <div style="flex:1;min-width:0">
            <div class="flex" style="gap:.6rem;flex-wrap:wrap">
                <span style="font-weight:500"><?= e($r['title']) ?></span>
                <span class="badge <?= $statusMap[$r['status']] ?? 'badge-closed' ?>"><?= e($r['status']) ?></span>
            </div>
            <div class="text-muted mt-1" style="font-size:.85rem"><?= e($r['description']) ?></div>
            <div class="text-muted mt-1" style="font-size:.78rem">
                <i class="fas fa-user"></i> <?= e($r['author_name'] ?? '—') ?>
                <?php if ($r['assigned_name']): ?> &nbsp;<i class="fas fa-user-check"></i> <?= e($r['assigned_name']) ?><?php endif; ?>
                &nbsp;<i class="fas fa-clock"></i> <?= format_datetime($r['created_at']) ?>
            </div>
        </div>
        <div class="item-actions">
        <?php if ($canTake && $r['status'] === 'новая'): ?>
            <form method="post" action="handlers/take_request.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm">Взять</button>
            </form>
        <?php endif; ?>
        <?php if ($canComplete && $r['status'] === 'в работе'): ?>
            <form method="post" action="handlers/complete_request.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Закрыть</button>
            </form>
        <?php endif; ?>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
