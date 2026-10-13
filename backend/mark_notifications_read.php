<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([getUserId()]);

$referer = $_SERVER['HTTP_REFERER'] ?? '/STD/index.php';
header("Location: $referer");
exit;
