<?php
require_once __DIR__ . '/../config.php';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$total = $pdo->query("SELECT COUNT(*) FROM birthdays")->fetchColumn();
$stmt = $pdo->prepare("SELECT * FROM birthdays ORDER BY birth_date LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$birthdays = $stmt->fetchAll();
?>
<h2 class="section-title">Дни рождения</h2>

<?php if (hasPermission($pdo, 'add_birthday')): ?>
<div class="form-container">
    <h3>➕ Добавить вручную</h3>
    <form action="handlers/add_birthday.php" method="post">
        <div class="form-group">
            <label>ФИО</label>
            <input type="text" name="full_name" required placeholder="Иванов Иван Иванович">
        </div>
        <div class="form-group">
            <label>Дата рождения</label>
            <input type="date" name="birth_date" required value="<?= date('Y-m-d') ?>">
            <small style="color: var(--text-secondary);">В формате ГГГГ-ММ-ДД</small>
        </div>
        <button type="submit" class="btn-primary">Добавить</button>
    </form>
</div>
<?php endif; ?>

<?php if (hasPermission($pdo, 'add_birthday')): ?>
<div class="excel-upload">
    <i class="fas fa-file-excel" style="font-size: 2.5rem;"></i>
    <p>Загрузите список дней рождений в формате CSV (поля: full_name, birth_date)</p>
    <a href="/portal/templates/birthday_template.csv" class="btn-template" download>📥 Скачать шаблон</a>
    <form action="handlers/upload_birthdays.php" method="post" enctype="multipart/form-data">
        <input type="file" name="birthday_file" accept=".csv" required>
        <button type="submit" class="btn-upload">Загрузить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
    <?php foreach ($birthdays as $b): ?>
    <div class="item-row">
        <div>
            <?= htmlspecialchars($b['full_name']) ?> — <?= date('d.m.Y', strtotime($b['birth_date'])) ?>
        </div>
        <?php if (hasPermission($pdo, 'delete_birthday')): ?>
        <a href="handlers/delete_birthday.php?id=<?= $b['id'] ?>" class="btn-delete delete-confirm"><i class="fas fa-trash"></i></a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php if ($total > $perPage): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
        <a href="?page=birthdays&p=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>