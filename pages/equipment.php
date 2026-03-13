<?php
require_once __DIR__ . '/../config.php';

// Обработка удаления (если есть разрешение)
if (isset($_GET['delete']) && hasPermission($pdo, 'delete_equipment')) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([$id]);
    cacheDelete('equipment_list');
    header('Location: equipment.php');
    exit;
}

$equipments = cacheGet('equipment_list', 600);
if (!$equipments) {
    $equipments = $pdo->query("SELECT * FROM equipment ORDER BY id")->fetchAll();
    cacheSet('equipment_list', $equipments);
}
?>
<h2 class="section-title">Техника</h2>

<?php if (hasPermission($pdo, 'add_equipment')): ?>
<div class="form-container">
    <h3>Добавить оборудование</h3>
    <form action="handlers/add_equipment.php" method="post">
        <div class="form-group">
            <label>Название</label>
            <input type="text" name="name" required>
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
    <?php foreach ($equipments as $eq): ?>
    <div class="item-row">
        <div>
            <strong><?= htmlspecialchars($eq['name']) ?></strong><br>
            <small><?= htmlspecialchars($eq['description']) ?></small>
        </div>
        <div>
            <?php if (hasPermission($pdo, 'edit_equipment')): ?>
                <a href="handlers/edit_equipment.php?id=<?= $eq['id'] ?>" class="btn-edit"><i class="fas fa-pen"></i></a>
            <?php endif; ?>
            <?php if (hasPermission($pdo, 'delete_equipment')): ?>
                <a href="?delete=<?= $eq['id'] ?>" class="btn-delete delete-confirm" onclick="return confirm('Удалить оборудование?')"><i class="fas fa-trash"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php if (hasPermission($pdo, 'add_comment')): ?>
    <div class="comment-section">
        <form action="handlers/add_comment.php" method="post">
            <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
            <input type="text" name="comment" class="comment-input" placeholder="Ваш комментарий" required>
            <button type="submit" class="btn-comment">Отправить</button>
        </form>
        <div class="comment-list">
            <?php
            $stmt = $pdo->prepare("SELECT c.*, u.full_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.equipment_id = ? ORDER BY c.created_at DESC");
            $stmt->execute([$eq['id']]);
            $comments = $stmt->fetchAll();
            ?>
            <?php foreach ($comments as $c): ?>
            <div class="comment-item">
                <strong><?= htmlspecialchars($c['full_name']) ?>:</strong> <?= htmlspecialchars($c['comment']) ?> <small><?= $c['created_at'] ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>