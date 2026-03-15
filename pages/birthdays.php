<?php
if (!hasPermission($pdo, 'view_birthdays')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db        = Database::getInstance();
$canAdd    = hasPermission($pdo, 'add_birthday');
$canDelete = hasPermission($pdo, 'delete_birthday');
$canUpload = hasPermission($pdo, 'upload_birthdays');

// Загружаем дни рождения из таблицы birthdays
$birthdays = $db->select("SELECT 'manual' AS src, id, full_name, birth_date, strftime('%m', birth_date) AS birth_month FROM birthdays");

// Добавляем физлиц у которых есть birth_date и которых нет в birthdays (по ФИО)
$personsWithBd = $db->select(
    "SELECT p.id, (p.last_name || ' ' || p.first_name || COALESCE(' ' || p.middle_name, '')) AS full_name, p.birth_date,
            strftime('%m', p.birth_date) AS birth_month
     FROM persons p
     WHERE p.birth_date IS NOT NULL AND p.birth_date != ''
       AND NOT EXISTS (
           SELECT 1 FROM birthdays b
           WHERE LOWER(TRIM(b.full_name)) = LOWER(TRIM(p.last_name || ' ' || p.first_name || COALESCE(' ' || p.middle_name, '')))
       )"
);
foreach ($personsWithBd as $pw) {
    $birthdays[] = ['src' => 'person', 'id' => $pw['id'], 'full_name' => $pw['full_name'], 'birth_date' => $pw['birth_date'], 'birth_month' => $pw['birth_month']];
}

// Сортируем по месяц-день
usort($birthdays, function($a, $b) {
    $ma = date('m-d', strtotime($a['birth_date']));
    $mb = date('m-d', strtotime($b['birth_date']));
    return strcmp($ma, $mb);
});

$todayMd = date('m-d');
$todayM  = date('m');

// Группируем по месяцу
$grouped = [];
foreach ($birthdays as $b) {
    $m = $b['birth_month'] ?? date('m', strtotime($b['birth_date']));
    $grouped[(int)$m][] = $b;
}

$monthNames = [
    1=>'Январь', 2=>'Февраль', 3=>'Март', 4=>'Апрель',
    5=>'Май', 6=>'Июнь', 7=>'Июль', 8=>'Август',
    9=>'Сентябрь', 10=>'Октябрь', 11=>'Ноябрь', 12=>'Декабрь'
];
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
    <div class="card-title mb-2">Добавить запись вручную</div>
    <form method="post" action="handlers/add_birthday.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>ФИО</label><input type="text" name="full_name" required maxlength="255"></div>
            <div class="form-group"><label>Дата рождения</label><input type="date" name="birth_date" required></div>
        </div>
        <p class="text-muted" style="font-size:.8rem;margin-top:-.4rem"><i class="fas fa-info-circle"></i> Физлица с датой рождения отображаются автоматически. Вручную добавляйте тех, кого нет в разделе «Люди».</p>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<?php if (empty($birthdays)): ?>
<div class="empty-state"><i class="fas fa-cake-candles"></i><p>Список пуст</p></div>
<?php else:
    // Показываем текущий месяц первым, если в нём есть записи
    $sortedMonths = array_keys($grouped);
    if (in_array((int)$todayM, $sortedMonths)) {
        $sortedMonths = array_merge([(int)$todayM], array_diff($sortedMonths, [(int)$todayM]));
    }
    foreach ($sortedMonths as $month):
        $isCurrentMonth = ((int)$month === (int)$todayM);
?>
<div style="margin-bottom:1.5rem">
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
                color:<?= $isCurrentMonth ? 'var(--accent-pink)' : 'var(--text-muted)' ?>;
                margin-bottom:.5rem;padding:.3rem 0;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.5rem">
        <i class="fas fa-calendar-alt"></i>
        <?= $monthNames[$month] ?>
        <?php if ($isCurrentMonth): ?><span class="badge badge-progress" style="font-size:.7rem">текущий</span><?php endif; ?>
        <span class="text-muted" style="font-weight:400">(<?= count($grouped[$month]) ?>)</span>
    </div>
    <div class="item-list" style="margin-bottom:0">
    <?php foreach ($grouped[$month] as $b):
        $bMd = date('m-d', strtotime($b['birth_date']));
        $isToday = ($bMd === $todayMd);
        $isManual = ($b['src'] === 'manual');
    ?>
    <div class="item-row" <?= $isToday ? 'style="border-left:3px solid var(--accent-pink)"' : '' ?>>
        <div style="flex:1">
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                <span style="font-weight:500"><?= e($b['full_name']) ?></span>
                <?php if ($isToday): ?>
                <span class="badge badge-progress">🎉 Сегодня!</span>
                <?php endif; ?>
                <?php if (!$isManual): ?>
                <span class="badge badge-done" title="Из раздела Люди" style="font-size:.7rem"><i class="fas fa-id-card"></i></span>
                <?php endif; ?>
            </div>
            <div class="text-muted mt-1" style="font-size:.85rem">
                <i class="fas fa-cake-candles"></i>
                <?= date('d', strtotime($b['birth_date'])) ?> <?= $monthNames[(int)date('m', strtotime($b['birth_date']))] ?>
                <?php
                    $year = (int)date('Y', strtotime($b['birth_date']));
                    if ($year > 1900) {
                        $age = (int)date('Y') - $year;
                        if (date('m-d') >= date('m-d', strtotime($b['birth_date']))) {
                            echo '<span class="text-muted" style="margin-left:.5rem">' . $age . ' лет</span>';
                        } else {
                            echo '<span class="text-muted" style="margin-left:.5rem">будет ' . $age . ' лет</span>';
                        }
                    }
                ?>
            </div>
        </div>
        <?php if ($canDelete && $isManual): ?>
        <div class="item-actions">
            <form method="post" action="handlers/delete_birthday.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить?"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; endif; ?>
