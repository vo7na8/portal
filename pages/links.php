<?php
require_once __DIR__ . '/../config.php';
$links = cacheGet('links_list', 600);
if (!$links) {
    $links = $pdo->query("SELECT * FROM links ORDER BY sort")->fetchAll();
    cacheSet('links_list', $links);
}
?>
<h2 class="section-title">Полезные ссылки</h2>
<?php if (hasPermission($pdo, 'add_link')): ?>
<div class="form-container">
    <h3>Добавить ссылку</h3>
    <form action="handlers/add_link.php" method="post">
        <div class="form-group">
            <label>Название</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>URL</label>
            <input type="url" name="url" required>
        </div>
        <button type="submit" class="btn-primary">Добавить</button>
    </form>
</div>
<?php endif; ?>
<div class="item-list">
    <?php foreach ($links as $link): ?>
    <div class="item-row">
        <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"><?= htmlspecialchars($link['title']) ?></a>
        <?php if (hasPermission($pdo, 'delete_link')): ?>
        <a href="handlers/delete_link.php?id=<?= $link['id'] ?>" class="btn-delete delete-confirm"><i class="fas fa-trash"></i></a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>