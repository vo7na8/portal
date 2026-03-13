<?php
require_once __DIR__ . '/../config.php';
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = $status_filter != 'all' ? "WHERE r.status = :status" : "";
$countSql = "SELECT COUNT(*) FROM requests r $where";
$countStmt = $pdo->prepare($countSql);
if ($status_filter != 'all') $countStmt->execute(['status' => $status_filter]); else $countStmt->execute();
$total = $countStmt->fetchColumn();

$sql = "SELECT r.*, u.full_name as assigned_name FROM requests r LEFT JOIN users u ON r.assigned_to = u.id $where ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$paramIndex = 1;
if ($status_filter != 'all') {
    $stmt->bindValue($paramIndex++, $status_filter, PDO::PARAM_STR);
}
$stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();
?>
<h2 class="section-title">Заявки</h2>
<div class="period-selector">
    <form method="get">
        <input type="hidden" name="page" value="requests">
        <label>Фильтр:</label>
        <select name="status" onchange="this.form.submit()">
            <option value="all" <?= $status_filter=='all'?'selected':'' ?>>Все</option>
            <option value="новая" <?= $status_filter=='новая'?'selected':'' ?>>Новые</option>
            <option value="в работе" <?= $status_filter=='в работе'?'selected':'' ?>>В работе</option>
            <option value="выполнена" <?= $status_filter=='выполнена'?'selected':'' ?>>Выполненные</option>
        </select>
    </form>
</div>
<?php if (hasPermission($pdo, 'add_request')): ?>
<div class="form-container">
    <h3>Создать заявку</h3>
    <form action="handlers/add_request.php" method="post">
        <div class="form-group">
            <label>Тема</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        <button type="submit" class="btn-primary">Создать</button>
    </form>
</div>
<?php endif; ?>
<div class="item-list">
    <?php foreach ($requests as $req): ?>
    <div class="item-row">
        <div>
            <strong>#<?= $req['id'] ?>: <?= htmlspecialchars($req['title']) ?></strong><br>
            <small><?= htmlspecialchars($req['description']) ?></small><br>
            <small>Статус: <?= $req['status'] ?>, назначена: <?= $req['assigned_name'] ?? 'не назначена' ?></small>
        </div>
        <div>
            <?php if ($req['status'] == 'новая' && hasPermission($pdo, 'take_request')): ?>
                <a href="handlers/take_request.php?id=<?= $req['id'] ?>" class="btn-take"><i class="fas fa-hand-paper"></i> Взять</a>
            <?php endif; ?>
            <?php if ($req['status'] == 'в работе' && hasPermission($pdo, 'complete_request')): ?>
                <a href="handlers/complete_request.php?id=<?= $req['id'] ?>" class="btn-edit"><i class="fas fa-check"></i> Завершить</a>
            <?php endif; ?>
            <?php if (hasPermission($pdo, 'reassign_request')): ?>
                <a href="handlers/reassign_request_form.php?id=<?= $req['id'] ?>" class="btn-edit"><i class="fas fa-exchange-alt"></i> Переназначить</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php if ($total > $perPage): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
        <a href="?page=requests&status=<?= $status_filter ?>&p=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>