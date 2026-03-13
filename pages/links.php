<?php
if (!hasPermission($pdo, 'view_links')) { echo '<div class="empty-state"><i class="fas fa-lock"></i><p>Доступ запрещён.</p></div>'; return; }
$db    = Database::getInstance();
$links = cacheGet('links_list') ?: [];
if (empty($links)) {
    $links = $db->select('SELECT * FROM links ORDER BY title');
    if ($links) cacheSet('links_list', $links);
}
$canAdd    = hasPermission($pdo, 'add_link');
$canDelete = hasPermission($pdo, 'delete_link');
?>
<h2 class="section-title">Полезные ссылки</h2>

<?php if ($canAdd): ?>
<div class="form-container">
    <div class="card-title mb-2">Добавить ссылку</div>
    <form method="post" action="handlers/add_link.php">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label>Название</label><input type="text" name="title" required maxlength="255"></div>
            <div class="form-group"><label>URL</label><input type="url" name="url" required maxlength="500"></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
    </form>
</div>
<?php endif; ?>

<div class="item-list">
<?php if (empty($links)): ?>
    <div class="empty-state"><i class="fas fa-link"></i><p>Ссылок пока нет</p></div>
<?php else: foreach ($links as $l): ?>
    <div class="item-row">
        <div>
            <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener">
                <i class="fas fa-arrow-up-right-from-square" style="font-size:.8rem;margin-right:.3rem"></i><?= e($l['title']) ?>
            </a>
        </div>
        <?php if ($canDelete): ?>
        <div class="item-actions">
            <form method="post" action="handlers/delete_link.php" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                <button class="btn btn-danger btn-sm" data-confirm="Удалить ссылку?"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; endif; ?>
</div>
