<?php
require_once __DIR__ . '/../config.php';

// Статистика заявок с учётом периода
$period = $_GET['period'] ?? 'month';
$dateCondition = match($period) {
    'day' => "date(created_at) = date('now')",
    'week' => "created_at >= datetime('now', '-7 days')",
    'month' => "created_at >= datetime('now', '-1 month')",
    'year' => "created_at >= datetime('now', '-1 year')",
    default => "1"
};

// Получаем общую статистику
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='новая' THEN 1 ELSE 0 END) as new,
        SUM(CASE WHEN status='в работе' THEN 1 ELSE 0 END) as progress,
        SUM(CASE WHEN status='выполнена' THEN 1 ELSE 0 END) as done
    FROM requests WHERE $dateCondition
")->fetch();

// Для диаграммы
$newCount = $stats['new'] ?? 0;
$progressCount = $stats['progress'] ?? 0;
$doneCount = $stats['done'] ?? 0;
$total = $stats['total'] ?? 0;
$newPercent = $total > 0 ? round($newCount / $total * 100) : 0;
$progressPercent = $total > 0 ? round($progressCount / $total * 100) : 0;
$donePercent = $total > 0 ? round($doneCount / $total * 100) : 0;

// Сегодняшний именинник
$today = date('m-d');
$birthday = $pdo->prepare("SELECT full_name FROM birthdays WHERE strftime('%m-%d', birth_date) = ?");
$birthday->execute([$today]);
$birthday_person = $birthday->fetchColumn();

// Информация об отпусках
$todayDate = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

// Кто в отпуске сейчас
$currentVacations = $pdo->prepare("
    SELECT v.*, u.full_name 
    FROM vacations v 
    JOIN users u ON v.user_id = u.id 
    WHERE start_date <= :today AND end_date >= :today
    ORDER BY start_date
");
$currentVacations->execute(['today' => $todayDate]);
$currentVacations = $currentVacations->fetchAll();

// У кого отпуск начинается в ближайшие 7 дней
$upcomingVacations = $pdo->prepare("
    SELECT v.*, u.full_name 
    FROM vacations v 
    JOIN users u ON v.user_id = u.id 
    WHERE start_date BETWEEN :today AND :later
    ORDER BY start_date
");
$upcomingVacations->execute(['today' => $todayDate, 'later' => $sevenDaysLater]);
$upcomingVacations = $upcomingVacations->fetchAll();
?>
<h2 class="section-title">Главная / дашборд</h2>

<!-- Выбор периода -->
<div class="period-selector">
    <form method="get">
        <input type="hidden" name="page" value="dashboard">
        <label for="period">Период:</label>
        <select name="period" id="period" onchange="this.form.submit()">
            <option value="day" <?= $period=='day'?'selected':'' ?>>День</option>
            <option value="week" <?= $period=='week'?'selected':'' ?>>Неделя</option>
            <option value="month" <?= $period=='month'?'selected':'' ?>>Месяц</option>
            <option value="year" <?= $period=='year'?'selected':'' ?>>Год</option>
        </select>
    </form>
</div>

<!-- Интерактивные карточки статистики -->
<div class="stats-grid">
    <a href="?page=requests" class="stat-card-link">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">всего заявок</div>
        </div>
    </a>
    <a href="?page=requests&status=новая" class="stat-card-link">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['new'] ?></div>
            <div class="stat-label">новых</div>
        </div>
    </a>
    <a href="?page=requests&status=в работе" class="stat-card-link">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['progress'] ?></div>
            <div class="stat-label">в работе</div>
        </div>
    </a>
    <a href="?page=requests&status=выполнена" class="stat-card-link">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['done'] ?></div>
            <div class="stat-label">выполнено</div>
        </div>
    </a>
</div>

<!-- Диаграмма распределения заявок -->
<?php if ($total > 0): ?>
<div style="margin: 2rem 0;">
    <h3>Распределение заявок по статусам</h3>
    <div style="display: flex; height: 30px; border-radius: 15px; overflow: hidden; margin-top: 0.5rem;">
        <?php if ($newCount > 0): ?>
        <div style="width: <?= $newPercent ?>%; background-color: #f28b82; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem;">
            <?= $newCount ?> (<?= $newPercent ?>%)
        </div>
        <?php endif; ?>
        <?php if ($progressCount > 0): ?>
        <div style="width: <?= $progressPercent ?>%; background-color: #9f9fdb; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem;">
            <?= $progressCount ?> (<?= $progressPercent ?>%)
        </div>
        <?php endif; ?>
        <?php if ($doneCount > 0): ?>
        <div style="width: <?= $donePercent ?>%; background-color: #99bbad; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem;">
            <?= $doneCount ?> (<?= $donePercent ?>%)
        </div>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.9rem;">
        <span><span style="display: inline-block; width: 12px; height: 12px; background-color: #f28b82; border-radius: 3px;"></span> Новые</span>
        <span><span style="display: inline-block; width: 12px; height: 12px; background-color: #9f9fdb; border-radius: 3px;"></span> В работе</span>
        <span><span style="display: inline-block; width: 12px; height: 12px; background-color: #99bbad; border-radius: 3px;"></span> Выполненные</span>
    </div>
</div>
<?php endif; ?>

<!-- Блок отпусков -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 2rem 0;">
    <div class="stat-card" style="text-align: left;">
        <h3><i class="fas fa-umbrella-beach"></i> Сейчас в отпуске</h3>
        <?php if (empty($currentVacations)): ?>
            <p>Нет сотрудников в отпуске</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($currentVacations as $v): ?>
                <li style="padding: 0.3rem 0;"><?= htmlspecialchars($v['full_name']) ?> (до <?= date('d.m.Y', strtotime($v['end_date'])) ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="stat-card" style="text-align: left;">
        <h3><i class="fas fa-calendar-alt"></i> Отпуск через 7 дней</h3>
        <?php if (empty($upcomingVacations)): ?>
            <p>Нет ближайших отпусков</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($upcomingVacations as $v): ?>
                <li style="padding: 0.3rem 0;"><?= htmlspecialchars($v['full_name']) ?> с <?= date('d.m.Y', strtotime($v['start_date'])) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($birthday_person): ?>
<div class="birthday-today">
    <i class="fas fa-birthday-cake"></i>
    <div>
        <div class="bday-name"><?= htmlspecialchars($birthday_person) ?></div>
        <div class="bday-desc">сегодня празднует день рождения 🎉</div>
    </div>
</div>
<?php endif; ?>