<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/manager/students.php'); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/manager/students.php'); exit;
}

$id         = (int)($_POST['id'] ?? 0);
$userType   = $_POST['user_type'] ?? 'student';
$roleType   = $_POST['role_type'] ?? 'admin';
$redirect   = $_POST['redirect'] ?? '';
$fullName   = clean($_POST['full_name'] ?? '');
$email      = clean($_POST['email'] ?? '');
$phone      = clean($_POST['phone'] ?? '');
$natId      = clean($_POST['national_id'] ?? '');
$gender     = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : 'male';
$studentNum = clean($_POST['student_number'] ?? '');
$collegeId      = (int)($_POST['college_id']      ?? 0) ?: null;
$degreeId       = (int)($_POST['degree_id']       ?? 0) ?: null;
$majorId        = (int)($_POST['major_id']        ?? 0) ?: null;
$advisorId      = (int)($_POST['academic_advisor_id'] ?? 0) ?: null;
$status     = in_array($_POST['status'] ?? '', ['active','inactive','suspended']) ? $_POST['status'] : 'active';
$password   = $_POST['password'] ?? '';

// تحديد نوع المستخدم
$typeMap = ['student'=>1,'admin'=>2,'manager'=>3];
if ($userType === 'admin') {
    $userTypeId = $roleType === 'manager' ? 3 : 2;
} else {
    $userTypeId = $typeMap[$userType] ?? 1;
}

if (empty($fullName) || empty($email)) {
    setFlash('error', 'الاسم والبريد الإلكتروني مطلوبان');
    header('Location: /STD/manager/' . ($redirect ?: ($userType==='student'?'students':'admins')) . '.php'); exit;
}

if ($id > 0) {
    // تعديل
    $params = [$fullName, $email, $phone ?: null, $natId ?: null, $gender, $collegeId, $degreeId, $majorId,$advisorId, $status, $userTypeId, $studentNum ?: null, $id];
    $pdo->prepare("UPDATE users SET full_name=?,email=?,phone=?,national_id=?,gender=?,college_id=?,degree_id=?,major_id=?,academic_advisor_id=?,status=?,user_type_id=?,student_number=?,updated_at=NOW() WHERE id=?")
        ->execute($params);
    $msg = 'تم تعديل البيانات بنجاح';
} else {
    // إضافة جديد
    if (empty($password)) {
        setFlash('error', 'كلمة المرور مطلوبة');
        header('Location: /STD/manager/' . ($redirect ?: ($userType==='student'?'students':'admins')) . '.php'); exit;
    }

    // التحقق من عدم تكرار البريد
    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);
    if ($check->fetch()) {
        setFlash('error', 'البريد الإلكتروني مستخدم مسبقاً');
        header('Location: /STD/manager/' . ($redirect ?: ($userType==='student'?'students':'admins')) . '.php'); exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (user_type_id,full_name,email,phone,national_id,gender,college_id,degree_id,major_id,academic_advisor_id,student_number,password,status,activation) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)")
        ->execute([$userTypeId,$fullName,$email,$phone ?: null,$natId ?: null,$gender,$collegeId,$degreeId,$majorId,$advisorId,$studentNum ?: null,$hashed,$status]);
    $msg = 'تم إضافة المستخدم بنجاح';
}

logActivity($pdo, getUserId(), 'save_user', $msg . ": $fullName");
setFlash('success', $msg);

$backMap = ['admins'=>'/STD/manager/admins.php', 'students'=>'/STD/manager/students.php'];
header('Location: ' . ($backMap[$redirect] ?? ($userType==='student' ? '/STD/manager/students.php' : '/STD/manager/admins.php')));
exit;
