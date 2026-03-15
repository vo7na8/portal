<?php
if (!hasPermission($pdo, 'view_news')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db   = Database::getInstance();
$news = $db->select('SELECT n.*, u.full_name FROM news n LEFT JOIN users u ON n.author_id=u.id ORDER BY n.created_at DESC');
$canAdd    = hasPermission($pdo, 'add_news');
$canDelete = hasPermission($pdo, 'delete_news');
?>
<h2 class="section-title">Новости</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-header" style="margin-bottom:1rem">
        <span class="card-title">Новая новость</span>
    </div>
    <form method="post" action="handlers/add_news.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Заголовок *</label>
            <input type="text" name="title" required maxlength="255">
        </div>
        <div class="form-group">
            <label>Текст *</label>
            <textarea name="body" required rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($news)): ?>
    <div class="empty-state"><i class="fas fa-newspaper"></i><p>Новостей пока нет</p></div>
<?php else: foreach ($news as $item): ?>
    <div class="item-row">
        <div style="flex:1;min-width:0">
            <div style="font-weight:500"><?= e($item['title']) ?></div>
            <div class="text-muted mt-1" style="font-size:.85rem;white-space:pre-wrap"><?= e($item['body']) ?></div>
            <div class="text-muted mt-1" style="font-size:.78rem">
                <i class="fas fa-user"></i> <?= e($item['full_name'] ?? 'Автор неизвестен') ?>
                &nbsp;<i class="fas fa-clock"></i> <?= format_datetime($item['created_at']) ?>
            </div>
        </div>
        <div class="item-actions">
        <?php if ($canDelete): ?>
            <form method="post" action="handlers/delete_news.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" data-confirm="Удалить новость?">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        <?php endif; ?>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
