<?php
if (!hasPermission($pdo, 'view_dashboard')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }

$period = in_array($_GET['period'] ?? '', ['day','week','month','year','all']) ? $_GET['period'] : 'month';
$dateCondition = match($period) {
    'day'   => "date(created_at) = date('now')",
    'week'  => "created_at >= datetime('now', '-7 days')",
    'month' => "created_at >= datetime('now', '-1 month')",
    'year'  => "created_at >= datetime('now', '-1 year')",
    default => '1',
};

$db    = Database::getInstance();
$stats = $db->selectOne("SELECT COUNT(*) total,
    SUM(status='новая') new_cnt,
    SUM(status='в работе') progress_cnt,
    SUM(status='выполнена') done_cnt
    FROM requests WHERE {$dateCondition}") ?? ['total'=>0,'new_cnt'=>0,'progress_cnt'=>0,'done_cnt'=>0];

$total    = (int)$stats['total'];
$newCnt   = (int)$stats['new_cnt'];
$progCnt  = (int)$stats['progress_cnt'];
$doneCnt  = (int)$stats['done_cnt'];

// Дни рождения сегодня
$todayMd = date('m-d');
$birthdays = $db->select("SELECT full_name FROM birthdays WHERE strftime('%m-%d', birth_date) = ?", [$todayMd]);

// Отпуска
$today      = date('Y-m-d');
$soon       = date('Y-m-d', strtotime('+7 days'));
$curVac     = $db->select("SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id=u.id WHERE start_date<=? AND end_date>=? ORDER BY start_date", [$today, $today]);
$upcomingVac = $db->select("SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id=u.id WHERE start_date BETWEEN ? AND ? ORDER BY start_date", [$today, $soon]);

// Последние новости
$latestNews = $db->select("SELECT n.title, n.created_at, u.full_name FROM news n LEFT JOIN users u ON n.author_id=u.id ORDER BY n.created_at DESC LIMIT 4");
?>

<div class="flex-between mb-2">
    <h2 class="section-title" style="margin-bottom:0">Дашборд</h2>
    <form method="get" style="display:flex;align-items:center;gap:.5rem">
        <input type="hidden" name="page" value="dashboard">
        <label for="period" style="color:var(--text-secondary);font-size:.85rem">Период:</label>
        <select name="period" id="period" onchange="this.form.submit()" style="background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border);padding:.35rem .9rem;border-radius:var(--radius-xl);outline:none;font-size:.88rem">
            <?php foreach (['day'=>'Сегодня','week'=>'Неделя','month'=>'Месяц','year'=>'Год','all'=>'Всё время'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $period===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Статкарты -->
<div class="stats-grid">
    <?php
    $cards = [
        ['val'=>$total,   'label'=>'всего заявок',  'icon'=>'fa-clipboard-list', 'link'=>'main.php?page=requests'],
        ['val'=>$newCnt,  'label'=>'новых',          'icon'=>'fa-circle-plus',    'link'=>'main.php?page=requests&status=новая'],
        ['val'=>$progCnt, 'label'=>'в работе',       'icon'=>'fa-spinner',        'link'=>'main.php?page=requests&status=в+работе'],
        ['val'=>$doneCnt, 'label'=>'выполнено',      'icon'=>'fa-circle-check',   'link'=>'main.php?page=requests&status=выполнена'],
    ];
    foreach ($cards as $c): ?>
    <a href="<?= $c['link'] ?>" class="stat-card-link">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas <?= $c['icon'] ?>"></i></div>
            <div class="stat-number"><?= $c['val'] ?></div>
            <div class="stat-label"><?= $c['label'] ?></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Полоса выполнения -->
<?php if ($total > 0):
    $bars = [['cnt'=>$newCnt,'cls'=>'badge-new','lbl'=>'Новые'],['cnt'=>$progCnt,'cls'=>'badge-progress','lbl'=>'В работе'],['cnt'=>$doneCnt,'cls'=>'badge-done','lbl'=>'Выполнено']];
?>
<div class="card mb-2">
    <div class="card-header"><span class="card-title"><i class="fas fa-chart-bar" style="margin-right:.5rem;color:var(--accent)"></i>Распределение заявок</span></div>
    <div style="display:flex;height:26px;border-radius:13px;overflow:hidden;gap:2px">
        <?php foreach ($bars as $b): if($b['cnt']<=0) continue;
            $pct = round($b['cnt']/$total*100); ?>
        <div class="<?= $b['cls'] ?>" style="width:<?= $pct ?>%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600;min-width:28px">
            <?= $b['cnt'] ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="flex mt-1" style="gap:1.2rem;font-size:.82rem">
        <?php foreach ($bars as $b): ?>
        <span><span class="badge <?= $b['cls'] ?>" style="margin-right:.3rem"><?= round($b['cnt']/$total*100) ?>%</span><?= $b['lbl'] ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Именинники -->
<?php foreach ($birthdays as $bp): ?>
<div class="birthday-today">
    <i class="fas fa-cake-candles"></i>
    <div>
        <div class="bday-name"><?= e($bp['full_name']) ?></div>
        <div class="bday-desc">Сегодня празднует день рождения 🎉</div>
    </div>
</div>
<?php endforeach; ?>

<!-- Сетка: отпуска + новости -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem" class="mt-2">

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-umbrella-beach" style="margin-right:.5rem;color:var(--accent-green)"></i>Сейчас в отпуске</span>
            <span class="badge badge-new"><?= count($curVac) ?></span>
        </div>
        <?php if (empty($curVac)): ?>
        <p class="text-muted">Никого нет</p>
        <?php else: foreach ($curVac as $v): ?>
        <div class="item-row" style="padding:.5rem 0">
            <span><?= e($v['full_name']) ?></span>
            <span class="text-muted" style="font-size:.8rem">до <?= format_date($v['end_date']) ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-newspaper" style="margin-right:.5rem;color:var(--accent-pink)"></i>Последние новости</span>
        </div>
        <?php if (empty($latestNews)): ?>
        <p class="text-muted">Новостей пока нет</p>
        <?php else: foreach ($latestNews as $n): ?>
        <div class="item-row" style="padding:.5rem 0">
            <span style="font-size:.9rem"><?= e($n['title']) ?></span>
            <span class="text-muted" style="font-size:.78rem"><?= format_date($n['created_at']) ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>

</div>
