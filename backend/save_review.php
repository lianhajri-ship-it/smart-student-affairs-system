<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/admin/chatbot_reviews.php'); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/admin/chatbot_reviews.php'); exit;
}

$convId       = (int)($_POST['conversation_id'] ?? 0);
$messageId    = (int)($_POST['message_id'] ?? 0) ?: null;
$reviewStatus = in_array($_POST['review_status'] ?? '', ['good','needs_improvement','bad']) ? $_POST['review_status'] : 'good';
$reviewNote   = trim($_POST['review_note'] ?? '');

if (!$convId) {
    setFlash('error', 'بيانات غير صالحة'); header('Location: /STD/admin/chatbot_reviews.php'); exit;
}

$pdo->prepare("INSERT INTO chatbot_reviews (conversation_id, message_id, reviewed_by, review_note, review_status) VALUES (?,?,?,?,?)")
    ->execute([$convId, $messageId, getUserId(), $reviewNote, $reviewStatus]);

logActivity($pdo, getUserId(), 'review_chatbot', "مراجعة محادثة #$convId");
setFlash('success', 'تم حفظ المراجعة');
$back = isManager() ? '/STD/manager/chatbot_reviews.php' : '/STD/admin/chatbot_reviews.php';
header("Location: $back?conv=$convId");
exit;
