<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_equipment')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($name === '') die('Введите название');
    $stmt = $pdo->prepare("UPDATE equipment SET name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $description, $id]);
    cacheDelete('equipment_list');
    header('Location: ../main.php?page=equipment');
    exit;
} else {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$id]);
    $eq = $stmt->fetch();
    if (!$eq) die('Оборудование не найдено');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Редактирование оборудования</title>
        <link rel="stylesheet" href="../style.css">
    </head>
    <body class="login-page">
        <div class="login-card" style="width: 500px;">
            <h2>Редактировать оборудование</h2>
            <form method="post">
                <div class="login-field">
                    <label>Название</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($eq['name']) ?>" required>
                </div>
                <div class="login-field">
                    <label>Описание</label>
                    <textarea name="description" rows="5"><?= htmlspecialchars($eq['description']) ?></textarea>
                </div>
                <button type="submit" class="btn-login">Сохранить</button>
                <a href="../main.php?page=equipment" class="btn-secondary" style="display:block; text-align:center; margin-top:1rem;">Отмена</a>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>