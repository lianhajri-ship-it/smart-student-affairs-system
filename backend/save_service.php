<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$back = isManager() ? '/STD/manager/services.php' : '/STD/admin/services.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id           = (int)($_POST['id'] ?? 0);
$title        = clean($_POST['title'] ?? '');
$description  = trim($_POST['description'] ?? '');
$deptId       = (int)($_POST['department_id'] ?? 0) ?: null;
$conditions   = trim($_POST['conditions'] ?? '');
$requiredDocs = trim($_POST['required_documents'] ?? '');
$steps        = trim($_POST['steps'] ?? '');
$keywords     = clean($_POST['keywords'] ?? '');
$icon         = clean($_POST['icon'] ?? 'bi-gear');
$status       = in_array($_POST['status'] ?? '', ['published','draft','archived']) ? $_POST['status'] : 'published';
$assignedRole = in_array($_POST['assigned_role'] ?? '', ['admin','advisor','both']) ? $_POST['assigned_role'] : 'admin';
$userId       = getUserId();

if (empty($title)) {
    setFlash('error', 'عنوان الخدمة مطلوب');
    header("Location: $back"); exit;
}

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE services SET title=?,description=?,department_id=?,conditions=?,required_documents=?,steps=?,keywords=?,icon=?,status=?,assigned_role=?,updated_at=NOW() WHERE id=?");
    $stmt->execute([$title,$description,$deptId,$conditions,$requiredDocs,$steps,$keywords,$icon,$status,$assignedRole,$id]);
    $action = 'تعديل';
} else {
    $stmt = $pdo->prepare("INSERT INTO services (title,description,department_id,conditions,required_documents,steps,keywords,icon,status,assigned_role,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$title,$description,$deptId,$conditions,$requiredDocs,$steps,$keywords,$icon,$status,$assignedRole,$userId]);
    $id = (int)$pdo->lastInsertId();
    $action = 'إضافة';
}

// تحديث قاعدة المعرفة
syncToKnowledgeBase($pdo, 'service', $id, $title, "$description\n$conditions\n$requiredDocs\n$steps", $keywords, null, $deptId, $userId);

logActivity($pdo, $userId, 'save_service', "$action خدمة: $title");
setFlash('success', "تم {$action} الخدمة بنجاح");
header("Location: $back");
exit;

function syncToKnowledgeBase(PDO $pdo, string $type, int $relatedId, string $title, string $content, string $keywords, ?int $serviceId, ?int $deptId, int $userId): void {
    $stmt = $pdo->prepare("SELECT id FROM knowledge_base WHERE type=? AND related_id=?");
    $stmt->execute([$type, $relatedId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE knowledge_base SET title=?,content=?,keywords=?,service_id=?,department_id=?,updated_at=NOW() WHERE id=?")
            ->execute([$title, $content, $keywords, $serviceId, $deptId, $existing['id']]);
        $kbId = $existing['id'];
        $pdo->prepare("DELETE FROM knowledge_chunks WHERE knowledge_id=?")->execute([$kbId]);
    } else {
        $pdo->prepare("INSERT INTO knowledge_base (title,content,type,related_id,service_id,department_id,keywords,created_by) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$title, $content, $type, $relatedId, $serviceId, $deptId, $keywords, $userId]);
        $kbId = (int)$pdo->lastInsertId();
    }

    // تقسيم إلى chunks
    $chunkSize = (int)getSetting('chunk_size', '500');
    $chunks    = splitIntoChunks($content, $chunkSize);
    foreach ($chunks as $i => $chunk) {
        $pdo->prepare("INSERT INTO knowledge_chunks (knowledge_id, chunk_text, chunk_order) VALUES (?,?,?)")
            ->execute([$kbId, $chunk, $i]);
    }
}
