<?php
if (!hasPermission($pdo, 'view_equipment')) {
    echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>';
    return;
}
$db        = Database::getInstance();
$canAdd    = hasPermission($pdo, 'add_equipment');
$canEdit   = hasPermission($pdo, 'edit_equipment');
$canDelete = hasPermission($pdo, 'delete_equipment');
$allUsers  = $db->select('SELECT id, full_name FROM users ORDER BY full_name');

$equipments = cacheGet('equipment_list', 600);
if (!$equipments) {
    $equipments = $db->select(
        'SELECT e.*, u.full_name AS responsible_name
         FROM equipment e LEFT JOIN users u ON e.responsible_id = u.id
         ORDER BY e.name'
    );
    if ($equipments) cacheSet('equipment_list', $equipments);
}
$statuses  = ['рабочее', 'неисправное', 'на ремонте', 'списано'];
$statusMap = [
    'рабочее'     => 'badge-done',
    'неисправное' => 'badge-new',
    'на ремонте'  => 'badge-progress',
    'списано'     => 'badge-closed',
];
// Хелпер: первая буква заглавная
$ucfirst = fn(string $s): string =>
    mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
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
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>"><?= $ucfirst($s) ?></option>
                    <?php endforeach; ?>
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
         WHERE ec.equipment_id = ? ORDER BY ec.created_at ASC',
        [$eqId]
    );
?>
<div class="card mb-1">
    <!-- Шапка -->
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
            <?php if ($canEdit): ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="edit-<?= $eqId ?>">
                <i class="fas fa-pen"></i> Изменить
            </button>
            <?php endif; ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="log-<?= $eqId ?>">
                <i class="fas fa-comments"></i> <span><?= count($comments) ?></span>
            </button>
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

    <?php if ($canEdit): ?>
    <!-- Форма редактирования -->
    <div id="edit-<?= $eqId ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <div class="card-title mb-1" style="font-size:.9rem">Редактировать</div>
        <form method="post" action="handlers/edit_equipment.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $eqId ?>">
            <div class="form-row">
                <div class="form-group"><label>Название</label><input type="text" name="name" value="<?= e($eq['name']) ?>" required maxlength="255"></div>
                <div class="form-group"><label>Тип</label><input type="text" name="type" value="<?= e($eq['type'] ?? '') ?>" maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Расположение</label><input type="text" name="location" value="<?= e($eq['location'] ?? '') ?>" maxlength="255"></div>
                <div class="form-group"><label>Инв. номер</label><input type="text" name="inventory_number" value="<?= e($eq['inventory_number'] ?? '') ?>" maxlength="100"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" required>
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($eq['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= $ucfirst($s) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ответственный</label>
                    <select name="responsible_id">
                        <option value="">— не выбран —</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)($eq['responsible_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Лог + комментарии -->
    <div id="log-<?= $eqId ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <form method="post" action="handlers/add_comment.php" class="flex" style="gap:.6rem;margin-bottom:.8rem">
            <?= csrf_field() ?>
            <input type="hidden" name="equipment_id" value="<?= $eqId ?>">
            <input type="text" name="comment" placeholder="Комментарий…" required
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
                <?php if ($c['is_log']): ?>
                <span class="badge badge-progress" style="font-size:.7rem;margin-left:.3rem">изменение</span>
                <?php endif; ?>
                <span class="comment-date"><?= format_datetime($c['created_at']) ?></span><br>
                <span style="font-size:.88rem;white-space:pre-wrap"><?= e($c['body']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
