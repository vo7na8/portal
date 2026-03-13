<?php
if (!hasPermission($pdo, 'view_birthdays')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db        = Database::getInstance();
$birthdays = $db->select("SELECT *, strftime('%m-%d', birth_date) md FROM birthdays ORDER BY md");
$todayMd   = date('m-d');
$canAdd    = hasPermission($pdo, 'add_birthday');
$canDelete = hasPermission($pdo, 'delete_birthday');
$canUpload = hasPermission($pdo, 'upload_birthdays');
?>
<h2 class="section-title">Дни рождения</h2>

<?php if ($canUpload): ?>
<div class="form-container">
    <div class="card-title mb-2"><i class="fas fa-file-csv" style="margin-right:.4rem;color:var(--accent-green)"></i>Загрузить из CSV</div>
    <form method="post" action="handlers/upload_birthdays.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="upload-area">
            <i class="fas fa-file-arrow-up"></i>
            <p>Перетащите CSV сюда или <strong>нажмите</strong></p>
            <p class="text-muted mt-1" style="font-size:.8rem">Формат: full_name, birth_date (YYYY-MM-DD)</p>
            <p class="upload-filename text-muted mt-1" style="font-size:.8rem"></p>
            <input type="file" name="csv_file" accept=".csv" style="display:none">
        </div>
        <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-upload"></i> Загрузить</button>
    </form>
</div>
<?php endif; ?>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Добавить запись</div>
    <form method="post" action="handlers/add_birthday.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>ФИО</label><input type="text" name="full_name" required maxlength="255"></div>
            <div class="form-group"><label>Дата рождения</label><input type="date" name="birth_date" required></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($birthdays)): ?>
    <div class="empty-state"><i class="fas fa-cake-candles"></i><p>Список пуст</p></div>
<?php else: foreach ($birthdays as $b): $isToday = $b['md'] === $todayMd; ?>
    <div class="item-row" <?= $isToday ? 'style="border-left:3px solid var(--accent-pink)"' : '' ?>>
        <div>
            <span style="font-weight:500"><?= e($b['full_name']) ?></span>
            <?php if ($isToday): ?><span class="badge badge-progress" style="margin-left:.5rem">🎉 Сегодня!</span><?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.85rem"><i class="fas fa-cake-candles"></i> <?= format_date($b['birth_date'], 'd.m') ?></div>
        </div>
        <?php if ($canDelete): ?>
        <div class="item-actions">
            <form method="post" action="handlers/delete_birthday.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить?"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; endif; ?>
</div>
