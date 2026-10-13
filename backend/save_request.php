<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/student/new_request.php'); exit; }

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح. يرجى المحاولة مرة أخرى.');
    header('Location: /STD/student/new_request.php'); exit;
}

$userId      = getUserId();
$serviceId   = (int)($_POST['service_id'] ?? 0) ?: null;
$title       = clean($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority    = in_array($_POST['priority'] ?? '', ['low','medium','high']) ? $_POST['priority'] : 'medium';

if (!$serviceId || empty($title) || empty($description)) {
    setFlash('error', 'يرجى تعبئة جميع الحقول المطلوبة');
    $back = $serviceId ? "/STD/student/new_request.php?service_id=$serviceId" : '/STD/student/services.php';
    header("Location: $back"); exit;
}

// رفع المرفق
$attachmentPath = null;
if (!empty($_FILES['attachment']['name'])) {
    $upload = uploadFile($_FILES['attachment'], 'requests');
    if (!$upload['success']) {
        setFlash('error', $upload['error']);
        $back = $serviceId ? "/STD/student/new_request.php?service_id=$serviceId" : '/STD/student/new_request.php';
        header("Location: $back"); exit;
    }
    $attachmentPath = $upload['path'];
}

// تحديد التوجيه بناءً على الخدمة
$assignedTo   = null;
$assignedRole = 'admin'; // default

if ($serviceId) {
    $svcStmt = $pdo->prepare("SELECT assigned_role FROM services WHERE id = ? AND status = 'published'");
    $svcStmt->execute([$serviceId]);
    $svc = $svcStmt->fetch();

    if ($svc) {
        $assignedRole = $svc['assigned_role'];

        if (in_array($assignedRole, ['advisor', 'both'])) {
            // جلب المرشد الأكاديمي للطالب
            $advStmt = $pdo->prepare("SELECT academic_advisor_id FROM users WHERE id = ?");
            $advStmt->execute([$userId]);
            $advRow = $advStmt->fetch();
            if ($advRow && $advRow['academic_advisor_id']) {
                $assignedTo = (int)$advRow['academic_advisor_id'];
            }
        }
    } else {
        // الخدمة غير موجودة أو غير منشورة — لا نربط service_id
        $serviceId = null;
    }
}

// حفظ الطلب
$stmt = $pdo->prepare("
    INSERT INTO requests (student_id, service_id, title, description, attachment, priority, assigned_to)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$userId, $serviceId, $title, $description, $attachmentPath, $priority, $assignedTo]);
$requestId = (int)$pdo->lastInsertId();

// الإشعارات
if ($assignedTo && in_array($assignedRole, ['advisor', 'both'])) {
    // إشعار المرشد الأكاديمي
    sendNotification($pdo, $assignedTo, 'طلب جديد من طالبك', "تم تقديم طلب جديد: $title",
        'info', "/STD/advisor/request_details.php?id=$requestId");
}

if ($assignedRole !== 'advisor') {
    // إشعار الإداريين (للطلبات الإدارية أو الموجهة لكليهما)
    $admins = $pdo->query("SELECT id FROM users WHERE user_type_id IN (2,3) AND status='active'")->fetchAll();
    foreach ($admins as $admin) {
        sendNotification($pdo, $admin['id'], 'طلب جديد', "تم تقديم طلب جديد: $title",
            'info', "/STD/admin/request_details.php?id=$requestId");
    }
}

logActivity($pdo, $userId, 'submit_request', "تقديم طلب جديد #$requestId: $title");

setFlash('success', 'تم إرسال طلبك بنجاح. سنتواصل معك قريباً.');
header("Location: /STD/student/request_details.php?id=$requestId");
exit;
