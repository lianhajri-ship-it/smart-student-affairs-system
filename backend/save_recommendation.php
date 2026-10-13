<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin() && !isAdvisor()) { header('Location: /STD/index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/manager/meetings.php'); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/manager/meetings.php'); exit;
}

$id                = (int)($_POST['id'] ?? 0);
$meetingId         = (int)($_POST['meeting_id'] ?? 0);
$title             = clean($_POST['title'] ?? '');
$recStatus         = $_POST['rec_status'] ?? '';
$recNotes          = trim($_POST['rec_notes'] ?? '');
$supportType       = $_POST['support_type'] ?? '';
$supportAmount     = $_POST['support_amount'] !== '' ? (float)$_POST['support_amount'] : null;
$disbursementStatus = $_POST['disbursement_status'] ?? '';

$back = "/STD/manager/meeting_details.php?id=$meetingId";

if (!$meetingId || empty($title)) {
    setFlash('error', 'بيانات غير مكتملة'); header("Location: $back"); exit;
}

$validRecStatus    = ['approved','rejected','other','notes'];
$validSupportType  = ['loan','prize','fixed_grant','marriage_grant','other'];
$validDisburse     = ['disbursed','not_disbursed'];

$recStatusVal      = in_array($recStatus, $validRecStatus) ? $recStatus : null;
$supportTypeVal    = in_array($supportType, $validSupportType) ? $supportType : null;
$disbursementVal   = in_array($disbursementStatus, $validDisburse) ? $disbursementStatus : null;

if ($id > 0) {
    $pdo->prepare("
        UPDATE meeting_recommendations
        SET title=?, rec_status=?, rec_notes=?, support_type=?, support_amount=?, disbursement_status=?
        WHERE id=? AND meeting_id=?
    ")->execute([$title, $recStatusVal, $recNotes, $supportTypeVal, $supportAmount, $disbursementVal, $id, $meetingId]);
    logActivity($pdo, getUserId(), 'update_recommendation', "تعديل توصية #$id للاجتماع #$meetingId");
    setFlash('success', 'تم تعديل التوصية بنجاح');
} else {
    $pdo->prepare("
        INSERT INTO meeting_recommendations (meeting_id, title, rec_status, rec_notes, support_type, support_amount, disbursement_status)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([$meetingId, $title, $recStatusVal, $recNotes, $supportTypeVal, $supportAmount, $disbursementVal]);
    $newId = $pdo->lastInsertId();
    logActivity($pdo, getUserId(), 'add_recommendation', "إضافة توصية #$newId للاجتماع #$meetingId");
    setFlash('success', 'تمت إضافة التوصية بنجاح');
}

// Auto-update meeting status
$stmt = $pdo->prepare("SELECT COUNT(*) FROM meeting_recommendations WHERE meeting_id=?");
$stmt->execute([$meetingId]);
$total = (int)$stmt->fetchColumn();

$stmtPending = $pdo->prepare("SELECT COUNT(*) FROM meeting_recommendations WHERE meeting_id=? AND rec_status IS NULL");
$stmtPending->execute([$meetingId]);
$pending = (int)$stmtPending->fetchColumn();

$newStatus = ($total > 0 && $pending === 0) ? 'complete' : 'incomplete';
$pdo->prepare("UPDATE meeting_minutes SET status=? WHERE id=?")->execute([$newStatus, $meetingId]);

header("Location: $back"); exit;
