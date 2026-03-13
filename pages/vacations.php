<?php
require_once __DIR__ . '/../config.php';
if (!hasPermission($pdo, 'view_vacations')) {
    echo "<p>Доступ запрещён.</p>";
    return;
}
$vacations = $pdo->query("SELECT v.*, u.full_name FROM vacations v JOIN users u ON v.user_id = u.id ORDER BY start_date")->fetchAll();
?>
<h2 class="section-title">График отпусков</h2>
<?php if (hasPermission($pdo, 'add_vacation')): ?>
<div class="form-container">
    <h3>Добавить отпуск</h3>
    <form action="handlers/add_vacation.php" method="post">
        <div class="form-group">
            <label>Сотрудник</label>
            <select name="user_id" required>
                <?php
                $users = $pdo->query("SELECT id, full_name FROM users")->fetchAll();
                foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Дата начала</label>
            <input type="date" name="start_date" required>
        </div>
        <div class="form-group">
            <label>Дата окончания</label>
            <input type="date" name="end_date" required>
        </div>
        <button type="submit" class="btn-primary">Добавить</button>
    </form>
</div>
<?php endif; ?>
<div class="item-list">
    <?php foreach ($vacations as $v): ?>
    <div class="item-row">
        <?= htmlspecialchars($v['full_name']) ?>: <?= $v['start_date'] ?> — <?= $v['end_date'] ?>
    </div>
    <?php endforeach; ?>
</div>