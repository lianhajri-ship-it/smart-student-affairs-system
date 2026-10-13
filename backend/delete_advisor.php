<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

$back  = '/STD/manager/advisors.php';
$id    = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if (!validateCsrfToken($token)) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}
if (!$id) {
    setFlash('error', 'معرف غير صالح'); header("Location: $back"); exit;
}

// Verify this user is actually an advisor
$advisorTypeId = (int)$pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();
$check = $pdo->prepare("SELECT id FROM users WHERE id=? AND user_type_id=?");
$check->execute([$id, $advisorTypeId]);
if (!$check->fetch()) {
    setFlash('error', 'المرشد غير موجود'); header("Location: $back"); exit;
}

// NULL out students referencing this advisor before deleting
$pdo->prepare("UPDATE users SET academic_advisor_id = NULL WHERE academic_advisor_id = ?")->execute([$id]);

// Delete the advisor user
$pdo->prepare("DELETE FROM users WHERE id=? AND user_type_id=?")->execute([$id, $advisorTypeId]);

logActivity($pdo, getUserId(), 'delete_advisor', "حذف مرشد أكاديمي #$id");
setFlash('success', 'تم حذف المرشد الأكاديمي بنجاح');
header("Location: $back");
exit;
