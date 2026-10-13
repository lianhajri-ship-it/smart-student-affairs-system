<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    logActivity($pdo, getUserId(), 'logout', 'تسجيل خروج');
}

logoutUser();
setFlash('success', 'تم تسجيل الخروج بنجاح');
header('Location: /STD/login.php');
exit;
