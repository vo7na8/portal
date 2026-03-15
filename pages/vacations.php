<?php
if (!hasPermission($pdo, 'view_vacations')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db        = Database::getInstance();
$vacations = $db->select(
    'SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id=u.id ORDER BY v.start_date DESC'
);
$users     = $db->select('SELECT id, full_name FROM users ORDER BY full_name');
$canAdd    = hasPermission($pdo, 'add_vacation');
$canEdit   = hasPermission($pdo, 'edit_vacation');
$canDelete = hasPermission($pdo, 'delete_vacation');
$today     = date('Y-m-d');
?>
<h2 class="section-title">График отпусков</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Добавить отпуск</div>
    <form method="post" action="handlers/add_vacation.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label>Сотрудник</label>
                <select name="user_id" required>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Начало</label><input type="date" name="start_date" required></div>
            <div class="form-group"><label>Окончание</label><input type="date" name="end_date" required></div>
        </div>
        <div class="form-group"><label>Примечание</label><input type="text" name="note" maxlength="255" placeholder="Необязательно"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($vacations)): ?>
    <div class="empty-state"><i class="fas fa-umbrella-beach"></i><p>Отпусков нет</p></div>
<?php else: foreach ($vacations as $v):
    $vid    = (int)$v['id'];
    $active = $v['start_date'] <= $today && $v['end_date'] >= $today;
    $future = $v['start_date'] > $today;
?>
<div class="card mb-1">
    <div class="item-row">
        <div>
            <div class="flex" style="gap:.5rem;align-items:center;flex-wrap:wrap">
                <span style="font-weight:500"><?= e($v['full_name']) ?></span>
                <?php if ($active): ?>
                    <span class="badge badge-progress">Сейчас</span>
                <?php elseif ($future): ?>
                    <span class="badge badge-new">Плановый</span>
                <?php else: ?>
                    <span class="badge badge-closed">Завершён</span>
                <?php endif; ?>
            </div>
            <div class="text-muted mt-1" style="font-size:.85rem">
                <i class="fas fa-calendar"></i>
                <?= format_date($v['start_date']) ?> &mdash; <?= format_date($v['end_date']) ?>
                <?php if (!empty($v['note'])): ?>
                &nbsp;<i class="fas fa-comment"></i> <?= e($v['note']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="item-actions">
            <?php if ($canEdit): ?>
            <button class="btn btn-secondary btn-sm" data-toggle-comments="vedit-<?= $vid ?>">
                <i class="fas fa-pen"></i>
            </button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
            <form method="post" action="handlers/delete_vacation.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $vid ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить отпуск?">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canEdit): ?>
    <div id="vedit-<?= $vid ?>" style="display:none;padding:.8rem 1.2rem;border-top:1px solid var(--border);background:var(--bg-content)">
        <div class="card-title mb-1" style="font-size:.9rem">Редактировать</div>
        <form method="post" action="handlers/edit_vacation.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $vid ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Сотрудник</label>
                    <select name="user_id" required>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)$v['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e($u['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Начало</label><input type="date" name="start_date" value="<?= e($v['start_date']) ?>" required></div>
                <div class="form-group"><label>Окончание</label><input type="date" name="end_date" value="<?= e($v['end_date']) ?>" required></div>
            </div>
            <div class="form-group"><label>Примечание</label><input type="text" name="note" value="<?= e($v['note'] ?? '') ?>" maxlength="255"></div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Сохранить</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>
