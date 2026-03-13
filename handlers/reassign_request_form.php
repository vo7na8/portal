<?php
require_once __DIR__ . '/../auth.php';
$id = $_GET['id'] ?? 0;
if (!$id) die('Не указана заявка');

if (!hasPermission($pdo, 'reassign_request')) die('Недостаточно прав');

$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();
if (!$request) die('Заявка не найдена');

$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Переназначение заявки</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="login-page">
    <div class="login-card" style="width: 500px;">
        <h2>Переназначить заявку #<?= $id ?></h2>
        <p><strong><?= htmlspecialchars($request['title']) ?></strong></p>
        <form action="reassign_request.php" method="post">
            <input type="hidden" name="request_id" value="<?= $id ?>">
            <div class="login-field">
                <label>Новый исполнитель</label>
                <select name="new_assignee" required>
                    <option value="">-- Выберите --</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $u['id'] == $request['assigned_to'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-login">Сохранить</button>
            <a href="../main.php?page=requests" class="btn-secondary" style="display:block; text-align:center; margin-top:1rem;">Отмена</a>
        </form>
    </div>
</body>
</html>