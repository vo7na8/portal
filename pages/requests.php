<?php
if (!hasPermission($pdo, 'view_requests')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db = Database::getInstance();
$canAdd      = hasPermission($pdo, 'add_request');
$canTake     = hasPermission($pdo, 'take_request');
$canComplete = hasPermission($pdo, 'edit_request');
$canReassign = hasPermission($pdo, 'reassign_request');
$allUsers    = $db->select('SELECT id, full_name FROM users ORDER BY full_name');

$statusFilter = $_GET['status'] ?? '';
$where  = $statusFilter ? 'WHERE r.status = ?' : '';
$params = $statusFilter ? [$statusFilter] : [];
$requests = $db->select(
    "SELECT r.*, u.full_name AS author_name, a.full_name AS assigned_name
     FROM requests r
     LEFT JOIN users u ON r.author_id  = u.id
     LEFT JOIN users a ON r.assigned_to = a.id
     {$where} ORDER BY r.created_at DESC",
    $params
);
$statusMap = ['новая' => 'badge-new', 'в работе' => 'badge-progress', 'выполнена' => 'badge-done'];
?>
<h2 class="section-title">Заявки</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Новая заявка</div>
    <form method="post" action="handlers/add_request.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Тема *</label><input type="text" name="title" required maxlength="255"></div>
            <div class="form-group">
                <label>Ответственный</label>
                <select name="assigned_to">
                    <option value="">— не назначен —</option>
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Описание *</label><textarea name="description" required rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Создать</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($requests)): ?>
    <div class="empty-state"><i class="fas fa-clipboard-list"></i><p>Заявок нет</p></div>
<?php else: foreach ($requests as $r):
    $rid      = (int)$r['id'];
    $comments = $db->select(
        'SELECT rc.*, u.full_name FROM request_comments rc
         LEFT JOIN users u ON rc.user_id = u.id
         WHERE rc.request_id = ? ORDER BY rc.created_at ASC',
        [$rid]
    );
?>
<div class="card mb-1">
    <!-- Шапка заявки -->
    <div class="item-row">
        <div style="flex:1;min-width:0">
            <div class="flex" style="gap:.6rem;flex-wrap:wrap">
                <span style="font-weight:500"><?= e($r['title']) ?></span>
                <span class="badge <?= $statusMap[$r['status']] ?? 'badge-closed' ?>"><?= e($r['status']) ?></span>
            </div>
            <div class="text-muted mt-1" style="font-size:.85rem"><?= e($r['description']) ?></div>
            <div class="text-muted mt-1" style="font-size:.78rem">
                <i class="fas fa-user"></i> <?= e($r['author_name'] ?? '—') ?>
                &nbsp;<i class="fas fa-user-check"></i> <?= $r['assigned_name'] ? e($r['assigned_name']) : '<em>не назначен</em>' ?>
                &nbsp;<i class="fas fa-clock"></i> <?= format_datetime($r['created_at']) ?>
            </div>
        </div>
        <div class="item-actions" style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center">

            <?php if ($canTake && $r['status'] === 'новая'): ?>
            <form method="post" action="handlers/take_request.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $rid ?>">
                <button type="submit" class="btn btn-success btn-sm">Взять</button>
            </form>
            <?php endif; ?>

            <?php if ($canComplete && $r['status'] === 'в работе'): ?>
            <form method="post" action="handlers/complete_request.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $rid ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Закрыть</button>
            </form>
            <?php endif; ?>

            <?php if ($canReassign && $r['status'] !== 'выполнена'): ?>
            <form method="post" action="handlers/reassign_request.php" style="display:inline-flex;gap:.3rem;align-items:center">
                <?= csrf_field() ?>
                <input type="hidden" name="request_id" value="<?= $rid ?>">
                <select name="assigned_to" style="background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border);border-radius:var(--radius-xl);padding:.3rem .7rem;font-size:.82rem;outline:none">
                    <option value="">— снять —</option>
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)$r['assigned_to'] === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" title="Переназначить"><i class="fas fa-user-pen"></i></button>
            </form>
            <?php endif; ?>

            <!-- Кнопка лога/комментариев -->
            <button class="btn btn-secondary btn-sm" data-toggle-comments="rlog-<?= $rid ?>">
                <i class="fas fa-comments"></i> <span><?= count($comments) ?></span>
            </button>
        </div>
    </div>

    <!-- Лог + комментарии к заявке -->
    <div id="rlog-<?= $rid ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <form method="post" action="handlers/add_request_comment.php" class="flex" style="gap:.6rem;margin-bottom:.8rem">
            <?= csrf_field() ?>
            <input type="hidden" name="request_id" value="<?= $rid ?>">
            <input type="text" name="comment" placeholder="Комментарий к заявке…" required
                style="flex:1;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-xl);padding:.5rem 1rem;color:var(--text-primary);outline:none;font-size:.9rem">
            <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
        </form>
        <?php if (empty($comments)): ?>
        <p class="text-muted" style="font-size:.85rem">Комментариев и изменений пока нет</p>
        <?php else: ?>
        <div class="comment-list">
            <?php foreach ($comments as $c): ?>
            <div class="comment-item" style="<?= $c['is_log'] ? 'opacity:.75;font-style:italic' : '' ?>">
                <strong><?= e($c['full_name'] ?? 'Система') ?></strong>
                <?php if ($c['is_log']): ?><span class="badge badge-progress" style="font-size:.7rem;margin-left:.3rem">изменение</span><?php endif; ?>
                <span class="comment-date"><?= format_datetime($c['created_at']) ?></span><br>
                <span style="font-size:.88rem;white-space:pre-wrap"><?= e($c['body']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; endif; ?>
</div>
