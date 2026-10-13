<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin() && !isAdvisor()) { header('Location: /STD/index.php'); exit; }

$back = '/STD/manager/meetings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id          = (int)($_POST['id'] ?? 0);
$title       = clean($_POST['title'] ?? '');
$meetingDate = clean($_POST['meeting_date'] ?? '');
$notes       = trim($_POST['notes'] ?? '');

if (empty($title) || empty($meetingDate)) {
    setFlash('error', 'عنوان الاجتماع وتاريخه مطلوبان'); header("Location: $back"); exit;
}

if ($id > 0) {
    $pdo->prepare("UPDATE meeting_minutes SET title=?, meeting_date=?, notes=? WHERE id=?")
        ->execute([$title, $meetingDate, $notes, $id]);
    logActivity($pdo, getUserId(), 'update_meeting', "تعديل محضر اجتماع #$id");
    setFlash('success', 'تم تعديل الاجتماع بنجاح');
} else {
    $pdo->prepare("INSERT INTO meeting_minutes (title, meeting_date, notes, status, created_by) VALUES (?,?,?,'incomplete',?)")
        ->execute([$title, $meetingDate, $notes, getUserId()]);
    $newId = $pdo->lastInsertId();
    logActivity($pdo, getUserId(), 'add_meeting', "إضافة محضر اجتماع #$newId");
    setFlash('success', 'تمت إضافة الاجتماع بنجاح');
}

header("Location: $back"); exit;
