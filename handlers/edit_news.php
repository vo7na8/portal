<?php
require_once __DIR__ . '/../auth.php';
if (!hasPermission($pdo, 'edit_news')) die('Недостаточно прав');
$id = $_GET['id'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title === '' || $content === '') die('Заполните все поля');
    $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ? WHERE id = ?");
    $stmt->execute([$title, $content, $id]);
    header('Location: ../main.php?page=news');
    exit;
} else {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    if (!$news) die('Новость не найдена');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Редактирование новости</title>
        <link rel="stylesheet" href="../style.css">
    </head>
    <body class="login-page">
        <div class="login-card" style="width: 500px;">
            <h2>Редактировать новость</h2>
            <form method="post">
                <div class="login-field">
                    <label>Заголовок</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($news['title']) ?>" required>
                </div>
                <div class="login-field">
                    <label>Содержание</label>
                    <textarea name="content" rows="5" required><?= htmlspecialchars($news['content']) ?></textarea>
                </div>
                <button type="submit" class="btn-login">Сохранить</button>
                <a href="../main.php?page=news" class="btn-secondary" style="display:block; text-align:center; margin-top:1rem;">Отмена</a>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>