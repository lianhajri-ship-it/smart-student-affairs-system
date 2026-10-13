<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$back = '/STD/signup.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

// Collect & validate
$fullName         = clean($_POST['full_name']           ?? '');
$email            = trim(strtolower($_POST['email']      ?? ''));
$phone            = clean($_POST['phone']               ?? '');
$natId            = clean($_POST['national_id']         ?? '');
$gender           = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : '';
$studentNum       = clean($_POST['student_number']      ?? '');
$collegeId        = (int)($_POST['college_id']           ?? 0) ?: null;
$degreeId         = (int)($_POST['degree_id']            ?? 0) ?: null;
$majorId          = (int)($_POST['major_id']             ?? 0) ?: null;
$academicAdvisorId= (int)($_POST['academic_advisor_id']  ?? 0) ?: null;
$password         = $_POST['password']               ?? '';
$passConfirm      = $_POST['password_confirm']       ?? '';

// Required fields
if (empty($fullName) || empty($email) || empty($natId) || empty($gender)) {
    setFlash('error', 'يرجى تعبئة جميع الحقول المطلوبة');
    $_POST['csrf_token'] = '';   // force new token on reload
    header("Location: $back"); exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'البريد الإلكتروني غير صحيح');
    header("Location: $back"); exit;
}

if (empty($collegeId) || empty($degreeId)) {
    setFlash('error', 'يرجى اختيار الكلية والدرجة العلمية');
    header("Location: $back"); exit;
}

if (mb_strlen($password) < 8) {
    setFlash('error', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل');
    header("Location: $back"); exit;
}

if ($password !== $passConfirm) {
    setFlash('error', 'كلمتا المرور غير متطابقتين');
    header("Location: $back"); exit;
}

// Duplicate email check
$dup = $pdo->prepare("SELECT id FROM users WHERE email=?");
$dup->execute([$email]);
if ($dup->fetch()) {
    setFlash('error', 'البريد الإلكتروني مسجل مسبقاً، يرجى تسجيل الدخول أو استخدام بريد آخر');
    header("Location: $back"); exit;
}

// Duplicate national ID check
if (!empty($natId)) {
    $dupNat = $pdo->prepare("SELECT id FROM users WHERE national_id=?");
    $dupNat->execute([$natId]);
    if ($dupNat->fetch()) {
        setFlash('error', ' رقم الهوية مسجل مسبقاً في النظام');
        header("Location: $back"); exit;
    }
}

// Insert
$hashed = password_hash($password, PASSWORD_BCRYPT);

$pdo->prepare("
    INSERT INTO users
        (user_type_id, full_name, email, phone, national_id, gender,
         college_id, degree_id, major_id,
         academic_advisor_id, student_number, password, status, activation)
    VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,  ?, 'active', 1)
")->execute([
    $fullName, $email, $phone ?: null, $natId, $gender,
    $collegeId, $degreeId, $majorId,
    $academicAdvisorId, $studentNum ?: null, $hashed,
]);

setFlash('success', 'تم إنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول.');
header('Location: /STD/index.php'); exit;
