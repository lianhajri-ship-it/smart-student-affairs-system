<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();
$back = '/STD/manager/user_types.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id           = (int)($_POST['id'] ?? 0);
$title        = clean($_POST['title'] ?? '');
$arabic_title = clean($_POST['arabic_title'] ?? '');

if (empty($title)) {
    setFlash('error', 'اسم الدور بالإنجليزي مطلوب'); header("Location: $back"); exit;
}
if (empty($arabic_title)) {
    setFlash('error', 'اسم الدور بالعربي مطلوب'); header("Location: $back"); exit;
}

if ($id > 0) {
    $pdo->prepare("UPDATE user_types SET title=?, arabic_title=? WHERE id=?")
        ->execute([$title, $arabic_title, $id]);
    setFlash('success', 'تم تعديل الدور بنجاح');
} else {
    $pdo->prepare("INSERT INTO user_types (title, arabic_title) VALUES (?, ?)")
        ->execute([$title, $arabic_title]);
    setFlash('success', 'تمت إضافة الدور بنجاح');
}

header("Location: $back"); exit;
