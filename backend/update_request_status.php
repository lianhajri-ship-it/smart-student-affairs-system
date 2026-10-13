<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin() && !isAdvisor()) {
    header('Location: /STD/index.php'); exit;
}

// تحديد إعادة التوجيه بحسب الدور (أو القيمة المرسلة)
$redirectRole = $_POST['redirect'] ?? (isAdvisor() ? 'advisor' : (isManager() ? 'manager' : 'admin'));
$backList     = match($redirectRole) {
    'advisor' => '/STD/advisor/requests.php',
    'manager' => '/STD/manager/requests.php',
    default   => '/STD/admin/requests.php',
};
$backDetail   = match($redirectRole) {
    'advisor' => '/STD/advisor/request_details.php',
    'manager' => '/STD/manager/request_details.php',
    default   => '/STD/admin/request_details.php',
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $backList"); exit; }

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $backList"); exit;
}

$requestId = (int)($_POST['request_id'] ?? 0);
$status    = $_POST['status'] ?? '';
$priority  = $_POST['priority'] ?? '';
$userId    = getUserId();

if (!$requestId) {
    setFlash('error', 'طلب غير صالح'); header("Location: $backList"); exit;
}

$validStatuses = ['new','under_review','replied','completed','rejected','closed'];
if (!in_array($status, $validStatuses)) {
    setFlash('error', 'حالة غير صالحة'); header("Location: $backDetail?id=$requestId"); exit;
}

// جلب الطلب مع التحقق من الصلاحية
if (isAdvisor()) {
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id=? AND assigned_to=?");
    $stmt->execute([$requestId, $userId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id=?");
    $stmt->execute([$requestId]);
}
$request = $stmt->fetch();

if (!$request) {
    setFlash('error', 'الطلب غير موجود'); header("Location: $backList"); exit;
}

$updates = ['status=?', 'updated_at=NOW()'];
$params  = [$status];

// الإداري والمدير يمكنهم تغيير المعين وإلغاء التعيين
if (!isAdvisor() && array_key_exists('assigned_to', $_POST)) {
    $newAssignedTo = (int)$_POST['assigned_to'] ?: null;
    $updates[]     = 'assigned_to=?';
    $params[]      = $newAssignedTo;
}

if (!empty($priority) && in_array($priority, ['low','medium','high'])) {
    $updates[] = 'priority=?';
    $params[]  = $priority;
}

$params[] = $requestId;
$pdo->prepare("UPDATE requests SET " . implode(',', $updates) . " WHERE id=?")->execute($params);

// إشعار الطالب بتغيير الحالة
$statusLabels = [
    'under_review' => 'تحت المراجعة',
    'replied'      => 'تم الرد عليه',
    'completed'    => 'مكتمل',
    'rejected'     => 'مرفوض',
    'closed'       => 'مغلق',
];
if (isset($statusLabels[$status])) {
    sendNotification($pdo, $request['student_id'],
        'تحديث حالة طلبك',
        "تم تحديث حالة طلبك #{$requestId} إلى: {$statusLabels[$status]}",
        'info', "/STD/student/request_details.php?id=$requestId"
    );
}

logActivity($pdo, $userId, 'update_request', "تحديث حالة الطلب #{$requestId} إلى $status");
setFlash('success', 'تم تحديث الطلب بنجاح');
header("Location: $backDetail?id=$requestId");
exit;
