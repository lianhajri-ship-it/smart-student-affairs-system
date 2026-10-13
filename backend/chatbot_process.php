<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gemini.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$userId         = getUserId();
$message        = trim($_POST['message'] ?? '');
$conversationId = (int)($_POST['conversation_id'] ?? 0);

if (empty($message)) {
    echo json_encode(['error' => 'Empty message']); exit;
}

// التحقق من تفعيل الشات بوت
if (getSetting('chatbot_enabled', '1') !== '1') {
    echo json_encode(['answer' => 'عذراً، المساعد الذكي غير متاح حالياً. يرجى تقديم طلب رسمي.', 'suggest_request' => true]);
    exit;
}

$apiKey = getSetting('gemini_api_key', '');
if (empty($apiKey)) {
    // Fallback: بحث نصي بسيط بدون AI
    $answer = simpleFallbackSearch($pdo, $message);
    saveConversation($pdo, $userId, $conversationId, $message, $answer);
    echo json_encode(['answer' => $answer, 'suggest_request' => false, 'conversation_id' => $conversationId]);
    exit;
}

try {
    $gemini = new GeminiAPI($apiKey, getSetting('gemini_model', 'gemini-2.5-flash'), getSetting('gemini_embedding_model', 'gemini-embedding-001'));

    // إنشاء embedding للسؤال
    $queryEmbedding = $gemini->generateEmbedding($message);

    $relevantChunks = [];
    if ($queryEmbedding) {
        // استرجاع أفضل chunks
        $maxChunks = (int)getSetting('max_chunks_retrieve', '5');
        $relevantChunks = findRelevantChunks($pdo, $queryEmbedding, $maxChunks);
    } else {
        // Fallback بدون embedding: جلب chunks عشوائية ذات صلة
        $stmt = $pdo->prepare("
            SELECT kc.id, kc.chunk_text, kb.title as kb_title
            FROM knowledge_chunks kc
            JOIN knowledge_base kb ON kb.id = kc.knowledge_id
            WHERE kb.status='published' AND kc.chunk_text LIKE ?
            LIMIT 5
        ");
        $stmt->execute(["%$message%"]);
        $fallbackChunks = $stmt->fetchAll();
        foreach ($fallbackChunks as $c) {
            $relevantChunks[] = ['chunk' => $c, 'score' => 0.5];
        }
    }

    // إذا لم تُوجد chunks مناسبة، ابحث في الـ FAQ مباشرة
    if (empty($relevantChunks)) {
        $stmt = $pdo->prepare("
            SELECT question, answer FROM faq
            WHERE status='published' AND (question LIKE ? OR keywords LIKE ?)
            LIMIT 3
        ");
        $stmt->execute(["%$message%", "%$message%"]);
        $faqResults = $stmt->fetchAll();
        foreach ($faqResults as $faq) {
            $relevantChunks[] = [
                'chunk' => ['chunk_text' => "س: {$faq['question']}\nج: {$faq['answer']}"],
                'score' => 0.6
            ];
        }
    }

    // توليد الإجابة
    $answer = $gemini->generateAnswer($message, $relevantChunks);
    $topScore = !empty($relevantChunks) ? $relevantChunks[0]['score'] : 0;

    // Gemini embeddings score 0.4–0.8 even for unrelated content;
    // use 0.55 as the threshold AND check if the response says "I don't know"
    $lowConfidence  = $topScore < 0.55;
    $unknownReply   = isUnknownAiResponse($answer);
    $suggestRequest = $lowConfidence || $unknownReply;

    // حفظ المحادثة
    $conversationId = saveConversation($pdo, $userId, $conversationId, $message, $answer,
        array_map(fn($c) => $c['chunk']['id'] ?? 0, $relevantChunks), $topScore);

    // تسجيل السؤال المجهول وإشعار المسؤولين
    if ($suggestRequest) {
        flagUnknownQuestion($pdo, $userId, $conversationId, $message, $topScore);
    }

    echo json_encode([
        'answer'          => $answer,
        'suggest_request' => $suggestRequest,
        'conversation_id' => $conversationId,
    ]);

} catch (Throwable $e) {
    $fallback = simpleFallbackSearch($pdo, $message);
    $convId   = saveConversation($pdo, $userId, $conversationId, $message, $fallback);
    // Fallback always means unknown — AI failed entirely
    flagUnknownQuestion($pdo, $userId, $convId, $message, 0.0);
    echo json_encode([
        'answer'          => $fallback,
        'suggest_request' => true,
        'conversation_id' => $convId,
    ]);
}

// -----------------------------------------------
function saveConversation(PDO $pdo, int $userId, int $convId, string $question,
                           string $answer, array $chunkIds = [], float $score = 0): int
{
    // إنشاء جلسة جديدة إذا لزم
    if ($convId <= 0) {
        $title = mb_substr($question, 0, 50);
        $stmt  = $pdo->prepare("INSERT INTO chatbot_conversations (student_id, session_title) VALUES (?, ?)");
        $stmt->execute([$userId, $title]);
        $convId = (int)$pdo->lastInsertId();
    } else {
        $pdo->prepare("UPDATE chatbot_conversations SET updated_at=NOW() WHERE id=?")->execute([$convId]);
    }

    // حفظ رسالة الطالب
    $stmt = $pdo->prepare("INSERT INTO chatbot_messages (conversation_id, sender_type, message_text) VALUES (?, 'student', ?)");
    $stmt->execute([$convId, $question]);

    // حفظ رد البوت
    $stmt = $pdo->prepare("
        INSERT INTO chatbot_messages (conversation_id, sender_type, message_text, matched_chunks, confidence_score)
        VALUES (?, 'bot', ?, ?, ?)
    ");
    $stmt->execute([$convId, $answer, json_encode($chunkIds), $score]);

    return $convId;
}

// Detect when the AI response signals it could not find a proper answer
function isUnknownAiResponse(string $response): bool
{
    $markers = [
        'لا توجد تفاصيل',
        'لا توجد معلومات',
        'لا تتوفر لدينا',
        'لا يمكنني إيجاد',
        'لا يمكنني تقديم',
        'لا أملك معلومات',
        'لا أعلم تحديداً',
        'لم أجد إجابة',
        'لم يتم العثور',
        'غير متوفرة حالياً',
        'غير محددة حالياً',
        'المعلومات المتوفرة لدينا حالياً، لا',
        'يرجى التواصل مع عمادة',
        'تقديم طلب يدوي',
        'تقديم طلب رسمي',
        'مراجعة العمادة شخصياً',
        'لا تتضمن المعلومات',
    ];
    foreach ($markers as $marker) {
        if (mb_stripos($response, $marker) !== false) return true;
    }
    return false;
}

function flagUnknownQuestion(PDO $pdo, int $studentId, int $convId, string $question, float $score): void
{
    // تجنب التكرار: نفس الطالب + نفس السؤال خلال آخر 5 دقائق
    $dup = $pdo->prepare("
        SELECT id FROM unknown_questions
        WHERE student_id=? AND question_text=? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        LIMIT 1
    ");
    $dup->execute([$studentId, $question]);
    if ($dup->fetch()) return;

    // حفظ السؤال المجهول
    $pdo->prepare("
        INSERT INTO unknown_questions (student_id, conversation_id, question_text, confidence_score)
        VALUES (?,?,?,?)
    ")->execute([$studentId, $convId, $question, $score]);

    // إشعار جميع المسؤولين والمديرين
    $admins = $pdo->query("
        SELECT u.id FROM users u
        JOIN user_types ut ON ut.id = u.user_type_id
        WHERE ut.title IN ('admin','manager') AND u.status='active'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $preview  = mb_substr($question, 0, 80) . (mb_strlen($question) > 80 ? '…' : '');
    $notifLink = '/STD/admin/unknown_questions.php';

    foreach ($admins as $adminId) {
        sendNotification(
            $pdo,
            (int)$adminId,
            'سؤال غير مُجاب في الشات بوت',
            "سؤال الطالب: «{$preview}»\nيحتاج إضافة معلومات لقاعدة المعرفة.",
            'warning',
            $notifLink
        );
    }
}

function simpleFallbackSearch(PDO $pdo, string $query): string
{
    // بحث في FAQ
    $stmt = $pdo->prepare("
        SELECT question, answer FROM faq
        WHERE status='published' AND (question LIKE ? OR answer LIKE ? OR keywords LIKE ?)
        LIMIT 1
    ");
    $stmt->execute(["%$query%", "%$query%", "%$query%"]);
    $faq = $stmt->fetch();
    if ($faq) {
        return "**{$faq['question']}**\n\n{$faq['answer']}";
    }

    // بحث في الخدمات
    $stmt = $pdo->prepare("
        SELECT title, description FROM services
        WHERE status='published' AND (title LIKE ? OR description LIKE ? OR keywords LIKE ?)
        LIMIT 1
    ");
    $stmt->execute(["%$query%", "%$query%", "%$query%"]);
    $svc = $stmt->fetch();
    if ($svc) {
        return "يمكنني إرشادك إلى خدمة **{$svc['title']}**:\n\n{$svc['description']}\n\nللمزيد من التفاصيل تفضل بزيارة صفحة الخدمات.";
    }

    return "عذراً، لم أجد إجابة دقيقة لسؤالك في الوقت الحالي. يمكنك:\n\n• مراجعة صفحة **الأسئلة الشائعة**\n• الاطلاع على **الخدمات المتاحة**\n• تقديم **طلب رسمي** وسيُرد عليك خلال 1-3 أيام عمل.";
}
