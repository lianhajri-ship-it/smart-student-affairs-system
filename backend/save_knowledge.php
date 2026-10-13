<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';

requireAdmin();

$back = isManager() ? '/STD/manager/knowledge_base.php' : '/STD/admin/knowledge_base.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: $back"); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header("Location: $back"); exit;
}

$id         = (int)($_POST['id'] ?? 0);
$title      = clean($_POST['title'] ?? '');
$content    = trim($_POST['content'] ?? '');
$type       = in_array($_POST['type'] ?? '', ['service','faq','guide','form','policy','info']) ? $_POST['type'] : 'info';
$serviceId  = (int)($_POST['service_id'] ?? 0) ?: null;
$deptId     = (int)($_POST['department_id'] ?? 0) ?: null;
$keywords   = clean($_POST['keywords'] ?? '');
$status     = in_array($_POST['status'] ?? '', ['published','draft','archived']) ? $_POST['status'] : 'published';
$userId     = getUserId();

if (empty($title) || empty($content)) {
    setFlash('error', 'العنوان والمحتوى مطلوبان');
    header("Location: $back"); exit;
}

if ($id > 0) {
    $pdo->prepare("UPDATE knowledge_base SET title=?,content=?,type=?,service_id=?,department_id=?,keywords=?,status=?,updated_at=NOW() WHERE id=?")
        ->execute([$title,$content,$type,$serviceId,$deptId,$keywords,$status,$id]);
    // حذف الـ chunks القديمة
    $pdo->prepare("DELETE FROM knowledge_chunks WHERE knowledge_id=?")->execute([$id]);
    $kbId = $id;
    $msg  = 'تعديل';
} else {
    $pdo->prepare("INSERT INTO knowledge_base (title,content,type,service_id,department_id,keywords,status,created_by) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$title,$content,$type,$serviceId,$deptId,$keywords,$status,$userId]);
    $kbId = (int)$pdo->lastInsertId();
    $msg  = 'إضافة';
}

// تقسيم إلى chunks وإنشاء embeddings
$chunkSize = (int)getSetting('chunk_size', '500');
$chunks    = splitIntoChunks($content, $chunkSize);
$apiKey    = getSetting('gemini_api_key', '');
$gemini    = $apiKey ? new GeminiAPI($apiKey, getSetting('gemini_model'), getSetting('gemini_embedding_model', 'gemini-embedding-001')) : null;

$embeddingsGenerated = 0;
foreach ($chunks as $i => $chunk) {
    $embeddingJson = null;
    $embeddingModel = null;

    if ($gemini) {
        $embedding = $gemini->generateEmbedding($chunk);
        if ($embedding) {
            $embeddingJson  = json_encode($embedding);
            $embeddingModel = getSetting('gemini_embedding_model', 'gemini-embedding-001');
            $embeddingsGenerated++;
        }
    }

    $pdo->prepare("INSERT INTO knowledge_chunks (knowledge_id, chunk_text, chunk_order, embedding_json, embedding_model) VALUES (?,?,?,?,?)")
        ->execute([$kbId, $chunk, $i, $embeddingJson, $embeddingModel]);
}

$successMsg = "تم {$msg} المعرفة بنجاح. تم تقسيم المحتوى إلى " . count($chunks) . " مقطع";
if ($embeddingsGenerated > 0) {
    $successMsg .= " وإنشاء $embeddingsGenerated embedding.";
} elseif ($apiKey) {
    $successMsg .= ". (فشل إنشاء بعض الـ embeddings)";
}

// إذا جاء من صفحة الأسئلة غير المُجابة، حدِّث حالتها
$uqId = (int)($_POST['uq_id'] ?? 0);
if ($uqId > 0) {
    $pdo->prepare("
        UPDATE unknown_questions
        SET status='added_to_kb', reviewed_by=?, reviewed_at=NOW()
        WHERE id=?
    ")->execute([$userId, $uqId]);
    $back = '/STD/admin/unknown_questions.php';
}

logActivity($pdo, $userId, 'save_knowledge', "$msg محتوى معرفة: $title");
setFlash('success', $successMsg);
header("Location: $back");
exit;
