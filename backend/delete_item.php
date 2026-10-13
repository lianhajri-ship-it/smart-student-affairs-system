<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin() && !isAdvisor()) {
    setFlash('error', 'لا تملك صلاحية هذا الإجراء'); header('Location: /STD/index.php'); exit;
}

$type   = $_GET['type'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$token  = $_GET['token'] ?? $_GET['csrf'] ?? '';

if (!validateCsrfToken($token)) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/index.php'); exit;
}

$redirectMap = [
    'service'  => isManager() ? '/STD/manager/services.php'  : '/STD/admin/services.php',
    'guide'    => isManager() ? '/STD/manager/guides.php'     : '/STD/admin/guides.php',
    'form'     => isManager() ? '/STD/manager/forms.php'      : '/STD/admin/forms.php',
    'faq'      => isManager() ? '/STD/manager/faq.php'        : '/STD/admin/faq.php',
    'knowledge'=> isManager() ? '/STD/manager/knowledge_base.php' : '/STD/admin/knowledge_base.php',
    'user'     => '/STD/manager/students.php',
    'college'  => '/STD/manager/colleges.php',
    'degree'   => '/STD/manager/degrees.php',
    'major'    => '/STD/manager/majors.php',
    'user_type'      => '/STD/manager/user_types.php',
    'department'     => '/STD/manager/departments.php',
    'meeting'        => '/STD/manager/meetings.php',
    'recommendation' => '/STD/manager/meeting_details.php?id=' . (int)($_GET['meeting_id'] ?? 0),
];

if (!isset($redirectMap[$type]) || !$id) {
    setFlash('error', 'بيانات غير صالحة'); header('Location: /STD/index.php'); exit;
}

$tableMap = [
    'service'  => 'services',
    'guide'    => 'guides',
    'form'     => 'forms',
    'faq'      => 'faq',
    'knowledge'=> 'knowledge_base',
    'user'     => 'users',
    'college'  => 'colleges',
    'degree'   => 'degrees',
    'major'    => 'majors',
    'user_type'      => 'user_types',
    'department'     => 'departments',
    'meeting'        => 'meeting_minutes',
    'recommendation' => 'meeting_recommendations',
];

// منع حذف البنية الأكاديمية إلا من المدير
if (in_array($type, ['college','degree','major','user_type','department']) && !isManager()) {
    setFlash('error', 'لا تملك صلاحية هذا الإجراء'); header('Location: /STD/index.php'); exit;
}
// منع حذف المحاضر إلا من الإداريين
if ($type === 'meeting' && !isAdmin()) {
    setFlash('error', 'لا تملك صلاحية حذف المحاضر'); header('Location: /STD/manager/meetings.php'); exit;
}
// منع حذف المدير الوحيد
if ($type === 'user' && !isManager()) {
    setFlash('error', 'لا تملك صلاحية حذف المستخدمين'); header('Location: /STD/index.php'); exit;
}
if ($type === 'user' && $id === getUserId()) {
    setFlash('error', 'لا يمكنك حذف حسابك الخاص'); header('Location: /STD/manager/students.php'); exit;
}

// For recommendations: capture meeting_id before delete so we can recompute status
$meetingIdForRec = 0;
if ($type === 'recommendation') {
    $stmtRec = $pdo->prepare("SELECT meeting_id FROM meeting_recommendations WHERE id=?");
    $stmtRec->execute([$id]);
    $meetingIdForRec = (int)($stmtRec->fetchColumn() ?: ($_GET['meeting_id'] ?? 0));
}

$table = $tableMap[$type];
$pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);

// Recompute meeting status after recommendation delete
if ($type === 'recommendation' && $meetingIdForRec) {
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM meeting_recommendations WHERE meeting_id=?");
    $stmtTotal->execute([$meetingIdForRec]);
    $totalRecs = (int)$stmtTotal->fetchColumn();

    $stmtPend = $pdo->prepare("SELECT COUNT(*) FROM meeting_recommendations WHERE meeting_id=? AND rec_status IS NULL");
    $stmtPend->execute([$meetingIdForRec]);
    $pendRecs = (int)$stmtPend->fetchColumn();

    $newStatus = ($totalRecs > 0 && $pendRecs === 0) ? 'complete' : 'incomplete';
    $pdo->prepare("UPDATE meeting_minutes SET status=? WHERE id=?")->execute([$newStatus, $meetingIdForRec]);
}

logActivity($pdo, getUserId(), "delete_$type", "حذف $type #$id");
setFlash('success', 'تم الحذف بنجاح');
header("Location: " . $redirectMap[$type]);
exit;
