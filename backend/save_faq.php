<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /STD/admin/faq.php'); exit; }
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'طلب غير صالح'); header('Location: /STD/admin/faq.php'); exit;
}

$id         = (int)($_POST['id'] ?? 0);
$question   = trim($_POST['question'] ?? '');
$answer     = trim($_POST['answer'] ?? '');
$serviceId  = (int)($_POST['service_id'] ?? 0) ?: null;
$deptId     = (int)($_POST['department_id'] ?? 0) ?: null;
$keywords   = clean($_POST['keywords'] ?? '');
$sortOrder  = (int)($_POST['sort_order'] ?? 0);
$status     = in_array($_POST['status'] ?? '', ['published','draft','archived']) ? $_POST['status'] : 'published';
$userId     = getUserId();
$back       = isManager() ? '/STD/manager/faq.php' : '/STD/admin/faq.php';

if (empty($question) || empty($answer)) {
    setFlash('error', 'السؤال والجواب مطلوبان');
    header("Location: $back"); exit;
}

if ($id > 0) {
    $pdo->prepare("UPDATE faq SET question=?,answer=?,service_id=?,department_id=?,keywords=?,sort_order=?,status=?,updated_at=NOW() WHERE id=?")
        ->execute([$question,$answer,$serviceId,$deptId,$keywords,$sortOrder,$status,$id]);
    $msg = 'تعديل';
} else {
    $pdo->prepare("INSERT INTO faq (question,answer,service_id,department_id,keywords,sort_order,status,created_by) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$question,$answer,$serviceId,$deptId,$keywords,$sortOrder,$status,$userId]);
    $id  = (int)$pdo->lastInsertId();
    $msg = 'إضافة';
}

// مزامنة مع قاعدة المعرفة
syncKbEntry($pdo, 'faq', $id, $question, "س: $question\nج: $answer", $keywords, $serviceId, $deptId, $userId);

function syncKbEntry(PDO $pdo, string $type, int $relatedId, string $title, string $content, string $keywords, ?int $serviceId, ?int $deptId, int $userId): void {
    $stmt = $pdo->prepare("SELECT id FROM knowledge_base WHERE type=? AND related_id=?");
    $stmt->execute([$type, $relatedId]);
    $existing = $stmt->fetch();
    $chunkSize = (int)(function() use ($pdo) {
        $s = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key='chunk_size'");
        $s->execute(); $r = $s->fetch(); return $r ? (int)$r['setting_value'] : 500;
    })();
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
    $words = explode(' ', $content);
    $chunks = []; $cur = '';
    foreach ($words as $w) {
        if (mb_strlen($cur . ' ' . $w) > $chunkSize && $cur) { $chunks[] = trim($cur); $cur = $w; }
        else { $cur .= ($cur ? ' ' : '') . $w; }
    }
    if ($cur) $chunks[] = trim($cur);
    foreach ($chunks as $i => $chunk) {
        $pdo->prepare("INSERT INTO knowledge_chunks (knowledge_id,chunk_text,chunk_order) VALUES (?,?,?)")->execute([$kbId,$chunk,$i]);
    }
}

logActivity($pdo, $userId, 'save_faq', "$msg سؤال شائع");
setFlash('success', "تم {$msg} السؤال بنجاح");
header("Location: $back");
exit;
