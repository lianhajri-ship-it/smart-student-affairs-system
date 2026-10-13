<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();
$back = '/STD/manager/majors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id        = (int)($_POST['id']         ?? 0);
$title     = clean($_POST['title']      ?? '');
$collegeId = (int)($_POST['college_id'] ?? 0) ?: null;
$degreeId  = (int)($_POST['degree_id']  ?? 0) ?: null;
$status    = in_array($_POST['status']  ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

if (empty($title)) {
    setFlash('error', 'اسم التخصص مطلوب'); header("Location: $back"); exit;
}

if ($id > 0) {
    $pdo->prepare("UPDATE majors SET title=?, college_id=?, degree_id=?, status=? WHERE id=?")
        ->execute([$title, $collegeId, $degreeId, $status, $id]);
    setFlash('success', 'تم تعديل التخصص بنجاح');
} else {
    $pdo->prepare("INSERT INTO majors (title, college_id, degree_id, status) VALUES (?, ?, ?, ?)")
        ->execute([$title, $collegeId, $degreeId, $status]);
    setFlash('success', 'تمت إضافة التخصص بنجاح');
}

header("Location: $back"); exit;
