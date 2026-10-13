<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userType = $_SESSION['user_type'] ?? '';

// Determine redirect URL per role
$profileUrl = match($userType) {
    'student'          => '/STD/student/profile.php',
    'admin'            => '/STD/admin/profile.php',
    'manager'          => '/STD/manager/profile.php',
    'academic_advisor' => '/STD/advisor/profile.php',
    default            => '/STD/index.php',
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $profileUrl"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $profileUrl"); exit;
}

$userId = getUserId();
$action = $_POST['action'] ?? 'update_info';

if ($action === 'update_info') {
    $fullName    = clean($_POST['full_name'] ?? '');
    $phone       = clean($_POST['phone'] ?? '');
    $dateOfBirth = $_POST['date_of_birth'] ?? '';

    if (empty($fullName)) {
        setFlash('error', 'الاسم مطلوب'); header("Location: $profileUrl"); exit;
    }

    // رفع الصورة
    $imagePath = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $upload = uploadFile($_FILES['profile_image'], 'profiles', ['jpg','jpeg','png','gif','webp']);
        if ($upload['success']) {
            $imagePath = $upload['path'];
            $_SESSION['profile_image'] = $imagePath;
        }
    }

    $sql    = "UPDATE users SET full_name=?, phone=?, date_of_birth=?, updated_at=NOW()";
    $params = [$fullName, $phone, $dateOfBirth ?: null];

    // Extra fields only for students
    if ($userType === 'student') {
        $collegeId  = (int)($_POST['college_id']  ?? 0) ?: null;
        $degreeId   = (int)($_POST['degree_id']   ?? 0) ?: null;
        $majorId    = (int)($_POST['major_id']    ?? 0) ?: null;
        $advisorId  = (int)($_POST['academic_advisor_id'] ?? 0) ?: null;
        $sql    .= ", college_id=?, degree_id=?, major_id=?, academic_advisor_id=?";
        $params[] = $collegeId;
        $params[] = $degreeId;
        $params[] = $majorId;
        $params[] = $advisorId;
    }

    if ($imagePath) {
        $sql    .= ", profile_image=?";
        $params[] = $imagePath;
    }
    $sql    .= " WHERE id=?";
    $params[] = $userId;

    $pdo->prepare($sql)->execute($params);
    $_SESSION['full_name'] = $fullName;
    logActivity($pdo, $userId, 'update_profile', 'تحديث البيانات الشخصية');
    setFlash('success', 'تم تحديث بياناتك بنجاح');

} elseif ($action === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        setFlash('error', 'كلمة المرور الجديدة وتأكيدها غير متطابقتين');
        header("Location: $profileUrl"); exit;
    }
    if (mb_strlen($newPassword) < 8) {
        setFlash('error', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل');
        header("Location: $profileUrl"); exit;
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        setFlash('error', 'كلمة المرور الحالية غير صحيحة');
        header("Location: $profileUrl"); exit;
    }

    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $userId]);
    logActivity($pdo, $userId, 'change_password', 'تغيير كلمة المرور');
    setFlash('success', 'تم تغيير كلمة المرور بنجاح');
}

header("Location: $profileUrl");
exit;
