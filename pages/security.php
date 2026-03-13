<?php
if (!hasPermission($pdo, 'view_security')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db      = Database::getInstance();
$records = $db->select('SELECT * FROM security ORDER BY created_at DESC');
$canAdd    = hasPermission($pdo, 'add_security');
$canDelete = hasPermission($pdo, 'delete_security');
?>
<h2 class="section-title">Информационная безопасность</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Новая запись</div>
    <form method="post" action="handlers/add_security.php">
        <?= csrf_field() ?>
        <div class="form-group"><label>Заголовок</label><input type="text" name="title" required maxlength="255"></div>
        <div class="form-group"><label>Описание</label><textarea name="description" required rows="3"></textarea></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($records)): ?>
    <div class="empty-state"><i class="fas fa-shield-halved"></i><p>Записей нет</p></div>
<?php else: foreach ($records as $rec): ?>
    <div class="item-row">
        <div style="flex:1;min-width:0">
            <div style="font-weight:500"><?= e($rec['title']) ?></div>
            <div class="text-muted mt-1" style="font-size:.85rem"><?= e($rec['description']) ?></div>
            <div class="text-muted mt-1" style="font-size:.78rem"><i class="fas fa-clock"></i> <?= format_datetime($rec['created_at']) ?></div>
        </div>
        <?php if ($canDelete): ?>
        <div class="item-actions">
            <form method="post" action="handlers/delete_security.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить запись?"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; endif; ?>
</div>
