<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /STD/login.php');
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$userType   = $_POST['user_type'] ?? 'student';

if (empty($identifier) || empty($password)) {
    setFlash('error', 'يرجى إدخال جميع الحقول المطلوبة');
    header('Location: /STD/login.php');
    exit;
}

// البحث عن المستخدم
if ($userType === 'student') {
    $stmt = $pdo->prepare("
        SELECT u.*, ut.title as user_type
        FROM users u
        JOIN user_types ut ON ut.id = u.user_type_id
        WHERE ut.title = 'student'
          AND (u.email = ? OR u.student_number = ?)
          AND u.activation = 1
    ");
    $stmt->execute([$identifier, $identifier]);
} else {
    $stmt = $pdo->prepare("
        SELECT u.*, ut.title as user_type
        FROM users u
        JOIN user_types ut ON ut.id = u.user_type_id
        WHERE ut.title IN ('admin', 'manager', 'academic_advisor')
          AND u.email = ?
          AND u.activation = 1
    ");
    $stmt->execute([$identifier]);
}

$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'البريد الإلكتروني أو كلمة المرور غير صحيحة');
    header('Location: /STD/login.php');
    exit;
}

if ($user['status'] !== 'active') {
    setFlash('error', 'حسابك موقوف أو غير نشط. يرجى التواصل مع الإدارة');
    header('Location: /STD/login.php');
    exit;
}

// تسجيل الدخول
loginUser($user);

// تحديث آخر تسجيل دخول
$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

// تسجيل النشاط
logActivity($pdo, $user['id'], 'login', 'تسجيل دخول ناجح');

// التوجيه حسب الدور
redirectByRole();
