<?php
if (!hasPermission($pdo, 'view_dashboard')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }

$period = in_array($_GET['period'] ?? '', ['day','week','month','year','all']) ? $_GET['period'] : 'week';
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

// Дни рождения сегодня: birthdays + persons
$todayMd = date('m-d');
$todayY  = (int)date('Y');
$birthdays = $db->select("SELECT full_name FROM birthdays WHERE strftime('%m-%d', birth_date) = ?", [$todayMd]);
// Добавляем физлиц от которых нет в birthdays
$personsBd = $db->select(
    "SELECT (last_name||' '||first_name||COALESCE(' '||middle_name,'')) AS full_name
     FROM persons
     WHERE birth_date IS NOT NULL AND strftime('%m-%d', birth_date) = ?
       AND NOT EXISTS (
           SELECT 1 FROM birthdays b
           WHERE LOWER(TRIM(b.full_name)) = LOWER(TRIM(last_name||' '||first_name||COALESCE(' '||middle_name,'')))
       )", [$todayMd]
);
$birthdays = array_merge($birthdays, $personsBd);

// Отпуска
$today       = date('Y-m-d');
$soon        = date('Y-m-d', strtotime('+7 days'));
$curVac      = $db->select("SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id=u.id WHERE start_date<=? AND end_date>=? ORDER BY start_date", [$today, $today]);
$upcomingVac = $db->select("SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id=u.id WHERE start_date BETWEEN ? AND ? ORDER BY start_date", [$today, $soon]);

// ================================================================
// ЛЕНТА АКТИВНоСТИ — UNION ALL из 5 источников
// Типы: request_new, request_comment, request_log, equipment_comment, equipment_log, news, birthday
// ================================================================
$feedLimit = 40;
$feedSql = "
    -- Новые заявки
    SELECT
        'request_new' AS type,
        r.id          AS entity_id,
        r.title       AS title,
        NULL          AS body,
        u.full_name   AS actor,
        r.created_at  AS ts
    FROM requests r
    LEFT JOIN users u ON u.id = r.author_id

    UNION ALL

    -- Комментарии к заявкам
    SELECT
        CASE WHEN rc.is_log = 1 THEN 'request_log' ELSE 'request_comment' END AS type,
        r.id         AS entity_id,
        r.title      AS title,
        rc.body      AS body,
        u.full_name  AS actor,
        rc.created_at AS ts
    FROM request_comments rc
    JOIN requests r  ON r.id  = rc.request_id
    LEFT JOIN users u ON u.id = rc.user_id

    UNION ALL

    -- Комментарии к оборудованию
    SELECT
        CASE WHEN ec.is_log = 1 THEN 'equipment_log' ELSE 'equipment_comment' END AS type,
        eq.id        AS entity_id,
        eq.name      AS title,
        ec.body      AS body,
        u.full_name  AS actor,
        ec.created_at AS ts
    FROM equipment_comments ec
    JOIN equipment eq ON eq.id = ec.equipment_id
    LEFT JOIN users u  ON u.id = ec.user_id

    UNION ALL

    -- Новости
    SELECT
        'news'       AS type,
        n.id         AS entity_id,
        n.title      AS title,
        n.body       AS body,
        u.full_name  AS actor,
        n.created_at AS ts
    FROM news n
    LEFT JOIN users u ON u.id = n.author_id

    ORDER BY ts DESC
    LIMIT {$feedLimit}
";
$feedItems = $db->select($feedSql);

// Дни рождения ближайшие 7 дней (для ленты)
$upcomingBd = [];
for ($i = 0; $i <= 7; $i++) {
    $md = date('m-d', strtotime("+{$i} days"));
    if ($i === 0) continue; // сегодня уже в блоке именинников
    $label = date('d.m', strtotime("+{$i} days"));
    $rows = $db->select("SELECT full_name FROM birthdays WHERE strftime('%m-%d', birth_date) = ?", [$md]);
    $rows2 = $db->select(
        "SELECT (last_name||' '||first_name||COALESCE(' '||middle_name,'')) AS full_name
         FROM persons WHERE birth_date IS NOT NULL AND strftime('%m-%d', birth_date) = ?
           AND NOT EXISTS (SELECT 1 FROM birthdays b WHERE LOWER(TRIM(b.full_name))=LOWER(TRIM(last_name||' '||first_name||COALESCE(' '||middle_name,''))))",
        [$md]
    );
    foreach (array_merge($rows, $rows2) as $bp) {
        $upcomingBd[] = ['name' => $bp['full_name'], 'date' => $label, 'days' => $i];
    }
}
?>

<div class="flex-between mb-2">
    <h2 class="section-title" style="margin-bottom:0">Дашборд</h2>
    <form method="get" style="display:flex;align-items:center;gap:.5rem">
        <input type="hidden" name="page" value="dashboard">
        <label for="period" style="color:var(--text-secondary);font-size:.85rem">Период заявок:</label>
        <select name="period" id="period" onchange="this.form.submit()" style="background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border);padding:.35rem .9rem;border-radius:var(--radius-xl);outline:none;font-size:.88rem">
            <?php foreach (['day'=>'Сегодня','week'=>'Неделя','month'=>'Месяц','year'=>'Год','all'=>'Всё время'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $period===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Статкарты заявок -->
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

<!-- Полоса -->
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

<!-- Сетка: отпуска + дни рождения ближайшие -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem" class="mt-2 mb-2">

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
            <span class="card-title"><i class="fas fa-cake-candles" style="margin-right:.5rem;color:var(--accent-pink)"></i>Ближайшие 7 дней</span>
        </div>
        <?php if (empty($upcomingBd)): ?>
        <p class="text-muted">Нет дней рождения</p>
        <?php else: foreach ($upcomingBd as $bd): ?>
        <div class="item-row" style="padding:.4rem 0">
            <span style="font-size:.9rem"><?= e($bd['name']) ?></span>
            <span class="text-muted" style="font-size:.8rem"><?= $bd['date'] ?>
                <span class="badge badge-closed" style="margin-left:.3rem;font-size:.68rem"><?= $bd['days'] ?> д.</span>
            </span>
        </div>
        <?php endforeach; endif; ?>
    </div>

</div>

<!-- ================================================================ -->
<!-- ЛЕНТА АКТИВНОСТИ                                              -->
<!-- ================================================================ -->
<?php
// Конфиг визуализации по типу
$typeConfig = [
    'request_new'     => ['icon'=>'fa-circle-plus',   'color'=>'var(--accent-green)',   'label'=>'Новая заявка',           'link'=>'main.php?page=requests'],
    'request_comment' => ['icon'=>'fa-comment',        'color'=>'var(--accent)',          'label'=>'Комментарий к заявке',     'link'=>'main.php?page=requests'],
    'request_log'     => ['icon'=>'fa-rotate',         'color'=>'var(--text-muted)',      'label'=>'Изменение заявки',        'link'=>'main.php?page=requests'],
    'equipment_comment'=>['icon'=>'fa-screwdriver-wrench','color'=>'var(--accent-green)', 'label'=>'Комментарий к технике',  'link'=>'main.php?page=equipment'],
    'equipment_log'   => ['icon'=>'fa-rotate',         'color'=>'var(--text-muted)',      'label'=>'Изменение техники',       'link'=>'main.php?page=equipment'],
    'news'            => ['icon'=>'fa-newspaper',      'color'=>'var(--accent-pink)',     'label'=>'Новость',                   'link'=>'main.php?page=news'],
];
?>
<div class="card">
    <div class="card-header" style="margin-bottom:.8rem">
        <span class="card-title"><i class="fas fa-bolt" style="margin-right:.5rem;color:var(--accent)"></i>Лента активности</span>
        <span class="text-muted" style="font-size:.8rem">последние <?= $feedLimit ?> событий</span>
    </div>

    <?php if (empty($feedItems)): ?>
    <p class="text-muted">Событий пока нет</p>
    <?php else:
        $prevDay = null;
        foreach ($feedItems as $fi):
            $cfg    = $typeConfig[$fi['type']] ?? ['icon'=>'fa-circle','color'=>'var(--text-muted)','label'=>$fi['type'],'link'=>'#'];
            $day    = date('Y-m-d', strtotime($fi['ts']));
            $today2 = date('Y-m-d');
            $yest   = date('Y-m-d', strtotime('-1 day'));
            if ($day !== $prevDay):
                if ($prevDay !== null) echo '</div>'; // close prev day group
                $dayLabel = match($day) {
                    $today2 => 'Сегодня',
                    $yest   => 'Вчера',
                    default => date('d.m.Y', strtotime($day)),
                };
                $prevDay = $day;
    ?>
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
                color:var(--text-muted);margin:.8rem 0 .3rem;padding-bottom:.3rem;
                border-bottom:1px solid var(--border)">
        <?= $dayLabel ?>
    </div>
    <div>
    <?php endif; ?>

    <div class="item-row" style="padding:.45rem 0;align-items:flex-start">
        <!-- Иконка типа -->
        <div style="width:28px;height:28px;border-radius:50%;background:var(--bg-content);border:1px solid var(--border);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-right:.7rem">
            <i class="fas <?= $cfg['icon'] ?>" style="font-size:.7rem;color:<?= $cfg['color'] ?>"></i>
        </div>
        <!-- Текст -->
        <div style="flex:1;min-width:0">
            <div style="font-size:.82rem;line-height:1.3">
                <span style="color:<?= $cfg['color'] ?>;font-weight:600;font-size:.73rem;text-transform:uppercase;letter-spacing:.04em"><?= $cfg['label'] ?></span>
                <?php if ($fi['actor']): ?>
                <span class="text-muted" style="font-size:.75rem"> · <?= e($fi['actor']) ?></span>
                <?php endif; ?>
            </div>
            <div style="font-size:.88rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <a href="<?= $cfg['link'] ?>" style="color:var(--text-primary);text-decoration:none"><?= e($fi['title']) ?></a>
            </div>
            <?php if (!empty($fi['body'])): ?>
            <div style="font-size:.82rem;color:var(--text-secondary);margin-top:.1rem;
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= e(mb_substr($fi['body'], 0, 120)) ?><?= mb_strlen($fi['body']) > 120 ? '…' : '' ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- Время -->
        <div class="text-muted" style="font-size:.75rem;flex-shrink:0;margin-left:.5rem;white-space:nowrap">
            <?= date('H:i', strtotime($fi['ts'])) ?>
        </div>
    </div>

    <?php endforeach;
    if ($prevDay !== null) echo '</div>';
    endif; ?>
</div>
