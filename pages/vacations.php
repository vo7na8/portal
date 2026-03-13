<?php
if (!hasPermission($pdo, 'view_vacations')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db       = Database::getInstance();
$vacations = $db->select('SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id=u.id ORDER BY v.start_date DESC');
$users    = $db->select('SELECT id, full_name FROM users ORDER BY full_name');
$canAdd   = hasPermission($pdo, 'add_vacation');
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
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($vacations)): ?>
    <div class="empty-state"><i class="fas fa-umbrella-beach"></i><p>Отпусков нет</p></div>
<?php else:
$today = date('Y-m-d');
foreach ($vacations as $v):
    $active = $v['start_date'] <= $today && $v['end_date'] >= $today;
?>
    <div class="item-row">
        <div>
            <span style="font-weight:500"><?= e($v['full_name']) ?></span>
            <?php if ($active): ?><span class="badge badge-progress" style="margin-left:.5rem">Сейчас</span><?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.85rem">
                <?= format_date($v['start_date']) ?> &mdash; <?= format_date($v['end_date']) ?>
            </div>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
