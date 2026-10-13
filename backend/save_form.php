<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$back = isManager() ? '/STD/manager/forms.php' : '/STD/admin/forms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id          = (int)($_POST['id'] ?? 0);
$title       = clean($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$serviceId   = (int)($_POST['service_id'] ?? 0) ?: null;
$deptId      = (int)($_POST['department_id'] ?? 0) ?: null;
$status      = in_array($_POST['status'] ?? '', ['published','draft','archived']) ? $_POST['status'] : 'published';
$userId      = getUserId();

if (empty($title)) {
    setFlash('error', 'عنوان النموذج مطلوب');
    header("Location: $back"); exit;
}

$filePath = $_POST['existing_file'] ?? null;
if (!empty($_FILES['file']['name'])) {
    $upload = uploadFile($_FILES['file'], 'forms', ['pdf','doc','docx','jpg','jpeg','png']);
    if (!$upload['success']) {
        setFlash('error', $upload['error']);
        header("Location: $back"); exit;
    }
    $filePath = $upload['path'];
}

if ($id > 0) {
    $pdo->prepare("UPDATE forms SET title=?,description=?,file_path=?,service_id=?,department_id=?,status=?,updated_at=NOW() WHERE id=?")
        ->execute([$title,$description,$filePath,$serviceId,$deptId,$status,$id]);
    $msg = 'تعديل';
} else {
    $pdo->prepare("INSERT INTO forms (title,description,file_path,service_id,department_id,status,created_by) VALUES (?,?,?,?,?,?,?)")
        ->execute([$title,$description,$filePath,$serviceId,$deptId,$status,$userId]);
    $msg = 'إضافة';
}

// مزامنة مع قاعدة المعرفة
if ($status === 'published') {
    $kbContent = $title . "\n" . $description;
    if ($filePath) {
        $kbContent .= "\nلتحميل هذا النموذج: [{$title}](/STD/assets/uploads/{$filePath})";
    }
    syncKbEntry($pdo, 'form', $id, $title, $kbContent, '', $serviceId, $deptId, $userId);
}

logActivity($pdo, $userId, 'save_form', "$msg نموذج: $title");
setFlash('success', "تم {$msg} النموذج بنجاح");
header("Location: $back");
exit;

function syncKbEntry(PDO $pdo, string $type, int $relatedId, string $title, string $content, string $keywords, ?int $serviceId, ?int $deptId, int $userId): void {
    $stmt = $pdo->prepare("SELECT id FROM knowledge_base WHERE type=? AND related_id=?");
    $stmt->execute([$type, $relatedId]);
    $existing = $stmt->fetch();
    $chunkSize = (int)getSetting('chunk_size', '500');
    if ($existing) {
        $pdo->prepare("UPDATE knowledge_base SET title=?,content=?,keywords=?,service_id=?,department_id=?,status='published',updated_at=NOW() WHERE id=?")
            ->execute([$title, $content, $keywords, $serviceId, $deptId, $existing['id']]);
        $kbId = $existing['id'];
        $pdo->prepare("DELETE FROM knowledge_chunks WHERE knowledge_id=?")->execute([$kbId]);
    } else {
        $pdo->prepare("INSERT INTO knowledge_base (title,content,type,related_id,service_id,department_id,keywords,status,created_by) VALUES (?,?,?,?,?,?,?,'published',?)")
            ->execute([$title, $content, $type, $relatedId, $serviceId, $deptId, $keywords, $userId]);
        $kbId = (int)$pdo->lastInsertId();
    }
    $chunks = splitIntoChunks($content, $chunkSize);
    foreach ($chunks as $i => $chunk) {
        $pdo->prepare("INSERT INTO knowledge_chunks (knowledge_id,chunk_text,chunk_order) VALUES (?,?,?)")->execute([$kbId, $chunk, $i]);
    }
}
