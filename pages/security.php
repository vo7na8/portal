<?php
require_once __DIR__ . '/../config.php';
if (!hasPermission($pdo, 'view_security')) {
    echo "<p>Доступ запрещён.</p>";
    return;
}
$entries = $pdo->query("SELECT * FROM security ORDER BY created_at DESC")->fetchAll();
?>
<h2 class="section-title">Информационная безопасность</h2>
<?php if (hasPermission($pdo, 'add_security')): ?>
<div class="form-container">
    <h3>Добавить запись</h3>
    <form action="handlers/add_security.php" method="post">
        <div class="form-group">
            <label>Заголовок</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        <button type="submit" class="btn-primary">Добавить</button>
    </form>
</div>
<?php endif; ?>
<div class="item-list">
    <?php foreach ($entries as $e): ?>
    <div class="item-row">
        <div>
            <strong><?= htmlspecialchars($e['title']) ?></strong><br>
            <small><?= htmlspecialchars($e['description']) ?></small>
        </div>
        <?php if (hasPermission($pdo, 'delete_security')): ?>
        <a href="handlers/delete_security.php?id=<?= $e['id'] ?>" class="btn-delete delete-confirm"><i class="fas fa-trash"></i></a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>