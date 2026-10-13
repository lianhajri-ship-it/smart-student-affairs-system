<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$token  = $_GET['csrf'] ?? '';
$status = $_GET['status'] ?? '';

$back = '/STD/admin/unknown_questions.php' . (!empty($status) ? '?status=' . urlencode($status) : '');

if (!validateCsrfToken($token) || !$id) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

if ($action === 'reviewed') {
    $pdo->prepare("
        UPDATE unknown_questions
        SET status='reviewed', reviewed_by=?, reviewed_at=NOW()
        WHERE id=? AND status='pending'
    ")->execute([getUserId(), $id]);
    logActivity($pdo, getUserId(), 'review_unknown_question', "مراجعة سؤال #$id");
    setFlash('success', 'تم تحديث الحالة');

} elseif ($action === 'delete') {
    $pdo->prepare("DELETE FROM unknown_questions WHERE id=?")->execute([$id]);
    logActivity($pdo, getUserId(), 'delete_unknown_question', "حذف سؤال #$id");
    setFlash('success', 'تم الحذف');

} else {
    setFlash('error', 'إجراء غير صالح');
}

header("Location: $back"); exit;
