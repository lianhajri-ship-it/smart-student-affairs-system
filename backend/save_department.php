<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();
$back = '/STD/manager/departments.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id     = (int)($_POST['id']     ?? 0);
$title  = clean($_POST['title']  ?? '');
$status = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

if (empty($title)) {
    setFlash('error', 'اسم القسم مطلوب'); header("Location: $back"); exit;
}

if ($id > 0) {
    $pdo->prepare("UPDATE departments SET title=?, status=? WHERE id=?")
        ->execute([$title, $status, $id]);
    setFlash('success', 'تم تعديل القسم بنجاح');
} else {
    $pdo->prepare("INSERT INTO departments (title, status) VALUES (?, ?)")
        ->execute([$title, $status]);
    setFlash('success', 'تمت إضافة القسم بنجاح');
}

header("Location: $back"); exit;
