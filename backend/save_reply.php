<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/index.php'); exit; }

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/STD/index.php')); exit;
}

$userId       = getUserId();
$requestId    = (int)($_POST['request_id'] ?? 0);
$replyMessage = trim($_POST['reply_message'] ?? '');
$redirect     = $_POST['redirect'] ?? 'student';

if (!$requestId || empty($replyMessage)) {
    setFlash('error', 'يرجى كتابة الرد');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

// التحقق من صلاحية الوصول للطلب
if (isStudent()) {
    $stmt = $pdo->prepare("SELECT id, student_id, status FROM requests WHERE id = ? AND student_id = ?");
    $stmt->execute([$requestId, $userId]);
} elseif (isAdvisor()) {
    $stmt = $pdo->prepare("SELECT id, student_id, status FROM requests WHERE id = ? AND assigned_to = ?");
    $stmt->execute([$requestId, $userId]);
} else {
    $stmt = $pdo->prepare("SELECT id, student_id, status FROM requests WHERE id = ?");
    $stmt->execute([$requestId]);
}
$request = $stmt->fetch();

if (!$request) {
    setFlash('error', 'الطلب غير موجود');
    header('Location: /STD/index.php'); exit;
}

// رفع المرفق
$attachmentPath = null;
if (!empty($_FILES['attachment']['name'])) {
    $upload = uploadFile($_FILES['attachment'], 'requests');
    if ($upload['success']) $attachmentPath = $upload['path'];
}

// حفظ الرد
$stmt = $pdo->prepare("INSERT INTO request_replies (request_id, user_id, reply_message, attachment) VALUES (?, ?, ?, ?)");
$stmt->execute([$requestId, $userId, $replyMessage, $attachmentPath]);

// تحديث حالة الطلب
$newStatus = isStudent() ? $request['status'] : 'replied';
if (!isStudent()) {
    $pdo->prepare("UPDATE requests SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $requestId]);
}

// إشعار
if (isStudent()) {
    // جلب معلومات الطلب (المعين)
    $assignedStmt = $pdo->prepare("SELECT mr.assigned_to, ut.title as assigned_type
        FROM requests mr
        LEFT JOIN users au ON au.id = mr.assigned_to
        LEFT JOIN user_types ut ON ut.id = au.user_type_id
        WHERE mr.id = ?");
    $assignedStmt->execute([$requestId]);
    $assignedRow = $assignedStmt->fetch();

    $assignedUserId   = $assignedRow ? (int)$assignedRow['assigned_to'] : 0;
    $assignedUserType = $assignedRow ? ($assignedRow['assigned_type'] ?? '') : '';

    if ($assignedUserId && $assignedUserType === 'academic_advisor') {
        // إشعار المرشد الأكاديمي المعين فقط
        sendNotification($pdo, $assignedUserId, 'رد من طالب', "أضاف طالب رداً على الطلب #$requestId",
            'info', "/STD/advisor/request_details.php?id=$requestId");
        // إشعار الإداريين أيضاً
        $admins = $pdo->query("SELECT id FROM users WHERE user_type_id IN (2,3) AND status='active'")->fetchAll();
        foreach ($admins as $admin) {
            sendNotification($pdo, $admin['id'], 'رد من طالب', "أضاف طالب رداً على الطلب #$requestId",
                'info', "/STD/admin/request_details.php?id=$requestId");
        }
    } else {
        // إشعار الإداريين فقط (الطلب لم يُعيَّن لمرشد)
        $admins = $pdo->query("SELECT id FROM users WHERE user_type_id IN (2,3) AND status='active'")->fetchAll();
        foreach ($admins as $admin) {
            sendNotification($pdo, $admin['id'], 'رد من طالب', "أضاف طالب رداً على الطلب #$requestId",
                'info', "/STD/admin/request_details.php?id=$requestId");
        }
    }
} else {
    // إشعار الطالب
    sendNotification($pdo, $request['student_id'], 'رد على طلبك', "تم الرد على طلبك #$requestId",
        'success', "/STD/student/request_details.php?id=$requestId");
}

logActivity($pdo, $userId, 'reply_request', "رد على الطلب #$requestId");
setFlash('success', 'تم إرسال الرد بنجاح');

if ($redirect === 'admin') {
    header("Location: /STD/admin/request_details.php?id=$requestId");
} elseif ($redirect === 'manager') {
    header("Location: /STD/manager/request_details.php?id=$requestId");
} elseif ($redirect === 'advisor') {
    header("Location: /STD/advisor/request_details.php?id=$requestId");
} else {
    header("Location: /STD/student/request_details.php?id=$requestId");
}
exit;
