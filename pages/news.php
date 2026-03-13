<?php
require_once __DIR__ . '/../config.php';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
$stmt = $pdo->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$news = $stmt->fetchAll();
?>
<h2 class="section-title">Новости</h2>

<?php if (hasPermission($pdo, 'add_news')): ?>
<div class="form-container">
    <h3>Добавить новость</h3>
    <form action="handlers/add_news.php" method="post">
        <div class="form-group">
            <label>Заголовок</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Содержание</label>
            <textarea name="content" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn-primary">Опубликовать</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
    <?php foreach ($news as $item): ?>
    <div class="item-row">
        <div>
            <strong><?= htmlspecialchars($item['title']) ?></strong><br>
            <small><?= htmlspecialchars($item['content']) ?></small><br>
            <small><?= $item['created_at'] ?></small>
        </div>
        <?php if (hasPermission($pdo, 'edit_news') || hasPermission($pdo, 'delete_news')): ?>
        <div>
            <?php if (hasPermission($pdo, 'edit_news')): ?>
                <a href="handlers/edit_news.php?id=<?= $item['id'] ?>" class="btn-edit"><i class="fas fa-pen"></i> Ред.</a>
            <?php endif; ?>
            <?php if (hasPermission($pdo, 'delete_news')): ?>
                <a href="handlers/delete_news.php?id=<?= $item['id'] ?>" class="btn-delete delete-confirm"><i class="fas fa-trash"></i> Уд.</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($total > $perPage): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
        <a href="?page=news&p=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>