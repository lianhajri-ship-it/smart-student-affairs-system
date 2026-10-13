<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$token  = $_GET['token'] ?? '';

if (!validateCsrfToken($token)) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/manager/students.php'); exit;
}
if (!$id || !in_array($action, ['activate','suspend'])) {
    setFlash('error', 'بيانات غير صالحة'); header('Location: /STD/manager/students.php'); exit;
}
if ($id === getUserId()) {
    setFlash('error', 'لا يمكنك تعديل حسابك الخاص'); header('Location: /STD/manager/students.php'); exit;
}

$newStatus = $action === 'activate' ? 'active' : 'suspended';
$pdo->prepare("UPDATE users SET status=?,updated_at=NOW() WHERE id=?")->execute([$newStatus, $id]);

$msg = $action === 'activate' ? 'تم تفعيل الحساب' : 'تم إيقاف الحساب';
logActivity($pdo, getUserId(), 'toggle_user', "$msg #$id");
setFlash('success', $msg);

$referer = $_SERVER['HTTP_REFERER'] ?? '/STD/manager/students.php';
header("Location: $referer");
exit;
