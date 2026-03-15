<?php
if (!hasPermission($pdo, 'view_equipment')) {
    echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>';
    return;
}
$db         = Database::getInstance();
$canAdd     = hasPermission($pdo, 'add_equipment');
$canDelete  = hasPermission($pdo, 'delete_equipment');
$canComment = hasPermission($pdo, 'add_equipment'); // используем существующее право

// Список пользователей для выпадающего списка ответственных
$allUsers = $db->select('SELECT id, full_name FROM users ORDER BY full_name');

// Список техники (с кэшем)
$equipments = cacheGet('equipment_list', 600);
if (!$equipments) {
    $equipments = $db->select(
        'SELECT e.*, u.full_name AS responsible_name
         FROM equipment e
         LEFT JOIN users u ON e.responsible_id = u.id
         ORDER BY e.name'
    );
    if ($equipments) cacheSet('equipment_list', $equipments);
}

$statusMap = [
    'рабочее'    => 'badge-done',
    'неисправное' => 'badge-new',
    'на ремонте'  => 'badge-progress',
    'списано'      => 'badge-closed',
];
?>
<h2 class="section-title">Техника</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Добавить оборудование</div>
    <form method="post" action="handlers/add_equipment.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Название *</label><input type="text" name="name" required maxlength="255"></div>
            <div class="form-group"><label>Тип</label><input type="text" name="type" maxlength="100"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Расположение</label><input type="text" name="location" maxlength="255"></div>
            <div class="form-group"><label>Инв. номер</label><input type="text" name="inventory_number" maxlength="100"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Статус</label>
                <select name="status" required>
                    <option value="рабочее">Рабочее</option>
                    <option value="неисправное">Неисправное</option>
                    <option value="на ремонте">На ремонте</option>
                    <option value="списано">Списано</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ответственный</label>
                <select name="responsible_id">
                    <option value="">— не выбран —</option>
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<?php if (empty($equipments)): ?>
<div class="empty-state"><i class="fas fa-screwdriver-wrench"></i><p>Техники пока нет</p></div>
<?php else: ?>
<div class="item-list">
<?php foreach ($equipments as $eq):
    $eqId     = (int)$eq['id'];
    $comments = $db->select(
        'SELECT ec.*, u.full_name FROM equipment_comments ec
         LEFT JOIN users u ON ec.user_id = u.id
         WHERE ec.equipment_id = ? ORDER BY ec.created_at DESC',
        [$eqId]
    );
?>
    <div>
        <div class="item-row">
            <div style="flex:1;min-width:0">
                <div class="flex" style="gap:.6rem;flex-wrap:wrap">
                    <span style="font-weight:500"><?= e($eq['name']) ?></span>
                    <span class="badge <?= $statusMap[$eq['status'] ?? ''] ?? 'badge-closed' ?>"><?= e($eq['status'] ?? '') ?></span>
                </div>
                <div class="text-muted mt-1" style="font-size:.82rem">
                    <?php if (!empty($eq['type'])): ?><i class="fas fa-tag"></i> <?= e($eq['type']) ?>&nbsp;<?php endif; ?>
                    <?php if (!empty($eq['location'])): ?><i class="fas fa-location-dot"></i> <?= e($eq['location']) ?>&nbsp;<?php endif; ?>
                    <?php if (!empty($eq['inventory_number'])): ?><i class="fas fa-barcode"></i> <?= e($eq['inventory_number']) ?>&nbsp;<?php endif; ?>
                    <?php if (!empty($eq['responsible_name'])): ?><i class="fas fa-user-tie"></i> <?= e($eq['responsible_name']) ?><?php endif; ?>
                </div>
            </div>
            <div class="item-actions">
                <?php if ($canComment): ?>
                <button class="btn btn-secondary btn-sm" data-toggle-comments="comments-<?= $eqId ?>">
                    <i class="fas fa-comments"></i> <span class="comment-count"><?= count($comments) ?></span>
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <form method="post" action="handlers/delete_equipment.php" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $eqId ?>">
                    <button class="btn btn-danger btn-sm" data-confirm="Удалить оборудование?">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canComment): ?>
        <div id="comments-<?= $eqId ?>" style="display:none;padding:.8rem 1.2rem;background:var(--bg-content);border-top:1px solid var(--border)">
            <form method="post" action="handlers/add_comment.php" class="flex" style="gap:.6rem;margin-bottom:.8rem">
                <?= csrf_field() ?>
                <input type="hidden" name="equipment_id" value="<?= $eqId ?>">
                <input type="text" name="comment" placeholder="Напишите комментарий…" required
                    style="flex:1;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-xl);padding:.55rem 1rem;color:var(--text-primary);outline:none;font-size:.9rem">
                <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
            </form>
            <?php if (empty($comments)): ?>
            <p class="text-muted" style="font-size:.85rem">Комментариев пока нет</p>
            <?php else: ?>
            <div class="comment-list">
                <?php foreach ($comments as $c): ?>
                <div class="comment-item">
                    <strong><?= e($c['full_name'] ?? 'Удалён') ?></strong>
                    <span class="comment-date"><?= format_datetime($c['created_at']) ?></span><br>
                    <span style="font-size:.88rem"><?= e($c['body']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
