<?php
require_once __DIR__ . '/config.php';
$session->flash('info', 'Вы вышли из системы.');
$logger->info('User logged out', ['user_id' => $_SESSION['user_id'] ?? null]);
$session->destroy();
redirect('index.php');
