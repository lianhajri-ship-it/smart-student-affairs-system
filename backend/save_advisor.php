<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();
$back = '/STD/manager/advisors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id        = (int)($_POST['id'] ?? 0);
$fullName  = clean($_POST['full_name']   ?? '');
$email     = trim(strtolower($_POST['email'] ?? ''));
$phone     = clean($_POST['phone']       ?? '');
$natId     = clean($_POST['national_id'] ?? '');
$collegeId = (int)($_POST['college_id']  ?? 0) ?: null;
$password  = $_POST['password'] ?? '';
$status    = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

if (empty($fullName) || empty($email)) {
    setFlash('error', 'الاسم والبريد الإلكتروني مطلوبان');
    header("Location: $back"); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'البريد الإلكتروني غير صحيح');
    header("Location: $back"); exit;
}

// Get academic_advisor user_type id
$advisorTypeId = (int)$pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();

if ($id > 0) {
    // Edit existing advisor user
    // Check email uniqueness (exclude self)
    $dup = $pdo->prepare("SELECT id FROM users WHERE email=? AND id != ?");
    $dup->execute([$email, $id]);
    if ($dup->fetch()) {
        setFlash('error', 'البريد الإلكتروني مستخدم من قِبل مستخدم آخر');
        header("Location: $back?edit=$id"); exit;
    }

    if (!empty($password)) {
        if (mb_strlen($password) < 8) {
            setFlash('error', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل');
            header("Location: $back?edit=$id"); exit;
        }
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET full_name=?,email=?,phone=?,national_id=?,college_id=?,password=?,status=? WHERE id=? AND user_type_id=?")
            ->execute([$fullName, $email, $phone ?: null, $natId ?: null, $collegeId, $hashed, $status, $id, $advisorTypeId]);
    } else {
        $pdo->prepare("UPDATE users SET full_name=?,email=?,phone=?,national_id=?,college_id=?,status=? WHERE id=? AND user_type_id=?")
            ->execute([$fullName, $email, $phone ?: null, $natId ?: null, $collegeId, $status, $id, $advisorTypeId]);
    }
    $msg = 'تم تعديل المرشد الأكاديمي بنجاح';
} else {
    // Add new advisor user
    if (mb_strlen($password) < 8) {
        setFlash('error', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل');
        header("Location: $back"); exit;
    }
    // Check email uniqueness
    $dup = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $dup->execute([$email]);
    if ($dup->fetch()) {
        setFlash('error', 'البريد الإلكتروني مستخدم مسبقاً');
        header("Location: $back"); exit;
    }
    // Check national_id uniqueness
    if (!empty($natId)) {
        $dupNat = $pdo->prepare("SELECT id FROM users WHERE national_id=?");
        $dupNat->execute([$natId]);
        if ($dupNat->fetch()) {
            setFlash('error', 'رقم الهوية مستخدم مسبقاً');
            header("Location: $back"); exit;
        }
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("
        INSERT INTO users (user_type_id, full_name, email, phone, national_id, college_id, password, status, activation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ")->execute([$advisorTypeId, $fullName, $email, $phone ?: null, $natId ?: null, $collegeId, $hashed, $status]);
    $msg = 'تم إضافة المرشد الأكاديمي بنجاح';
}

logActivity($pdo, getUserId(), 'save_advisor', $msg);
setFlash('success', $msg);
header("Location: $back");
exit;
