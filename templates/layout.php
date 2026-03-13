<?php
/**
 * Мастер-шаблон портала
 * Использование:
 *   $layout_title     = 'Название страницы';
 *   $layout_content   = ob_get_clean();
 *   include __DIR__ . '/../templates/layout.php';
 */
$appName = Config::getInstance()->getAppName();
$pageTitle = isset($layout_title) ? e($layout_title) . ' — ' . e($appName) : e($appName);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <script src="script.js" defer></script>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/flash.php'; ?>
        <?= $layout_content ?? '' ?>
    </main>
</div>
</body>
</html>
