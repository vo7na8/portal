<?php
require_once __DIR__ . '/config.php';
// Выход допускается только POST через форму с CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security->requireCsrf();
}
$logger->info('User logged out', ['user_id' => $_SESSION['user_id'] ?? null]);
$session->destroy();
flash('info', 'Вы вышли из системы.');
redirect('index.php');
