<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';

requireAdmin();

$back   = isManager() ? '/STD/manager/knowledge_base.php' : '/STD/admin/knowledge_base.php';
$apiKey = getSetting('gemini_api_key', '');
if (empty($apiKey)) {
    setFlash('error', 'مفتاح Gemini API غير مضبوط. يرجى إضافته من إعدادات النظام.');
    header("Location: $back"); exit;
}

$gemini = new GeminiAPI($apiKey, getSetting('gemini_model'), getSetting('gemini_embedding_model', 'gemini-embedding-001'));

// جلب الـ chunks التي ليس لها embedding
$stmt = $pdo->query("SELECT kc.id, kc.chunk_text FROM knowledge_chunks kc
                     JOIN knowledge_base kb ON kb.id = kc.knowledge_id
                     WHERE kc.embedding_json IS NULL AND kb.status='published'
                     LIMIT 50");
$chunks = $stmt->fetchAll();

$success   = 0;
$failed    = 0;
$lastApiError = '';

foreach ($chunks as $chunk) {
    $embedding = $gemini->generateEmbedding($chunk['chunk_text']);
    if ($embedding) {
        $pdo->prepare("UPDATE knowledge_chunks SET embedding_json=?, embedding_model=? WHERE id=?")
            ->execute([json_encode($embedding), getSetting('gemini_embedding_model', 'gemini-embedding-001'), $chunk['id']]);
        $success++;
    } else {
        $failed++;
        if (!$lastApiError && $gemini->lastError) {
            $lastApiError = $gemini->lastError;
        }
    }
    usleep(200000); // تأخير 200ms لتجنب rate limit
}

$totalPending = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks WHERE embedding_json IS NULL")->fetchColumn();
$msg = "تم إنشاء $success embedding بنجاح.";
if ($failed > 0) {
    $msg .= " فشل: $failed.";
    if ($lastApiError) $msg .= " السبب: $lastApiError";
}
if ($totalPending > 0) $msg .= " متبقي: $totalPending chunk.";

logActivity($pdo, getUserId(), 'generate_embeddings', $msg);
setFlash('success', $msg);
header("Location: $back");
exit;
