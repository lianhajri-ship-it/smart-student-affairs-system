<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$userId = getUserId();

// جلب المحادثات السابقة
$conversations = $pdo->prepare("
    SELECT cc.*, COUNT(cm.id) as msg_count
    FROM chatbot_conversations cc
    LEFT JOIN chatbot_messages cm ON cm.conversation_id = cc.id
    WHERE cc.student_id = ?
    GROUP BY cc.id
    ORDER BY cc.updated_at DESC
    LIMIT 10
");
$conversations->execute([$userId]);
$conversations = $conversations->fetchAll();

// المحادثة الحالية
$convId = (int)($_GET['conv'] ?? 0);
$messages = [];
if ($convId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM chatbot_conversations WHERE id = ? AND student_id = ?");
    $stmt->execute([$convId, $userId]);
    if (!$stmt->fetch()) $convId = 0;
}
if ($convId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM chatbot_messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt->execute([$convId]);
    $messages = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'المساعد الذكي';
$extraCss  = '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken) . '">';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="row g-3">
        <!-- Conversations Sidebar -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="std-card h-100" style="max-height:calc(100vh - 120px);overflow-y:auto;">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-chat-dots me-1 text-primary"></i>المحادثات</h6>
                    <button class="btn btn-sm btn-primary" onclick="Chatbot.newConversation()">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <div class="p-2">
                    <?php if (empty($conversations)): ?>
                        <p class="text-muted small text-center p-3">لا توجد محادثات سابقة</p>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                        <a href="/STD/student/chatbot.php?conv=<?= $conv['id'] ?>"
                           class="d-block p-2 rounded mb-1 text-decoration-none <?= $convId == $conv['id'] ? 'bg-primary-soft' : '' ?>"
                           style="transition:.15s;" onmouseover="this.style.background='#f0f4f8'" onmouseout="this.style.background='<?= $convId == $conv['id'] ? '#e8f4f8' : '' ?>'">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-chat-text text-primary"></i>
                                <div class="overflow-hidden">
                                    <div class="fw-600 text-truncate" style="font-size:.82rem;color:#2c3e50;"><?= htmlspecialchars($conv['session_title']) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?= formatDate($conv['updated_at']) ?> · <?= $conv['msg_count'] ?> رسائل</div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-lg-9">
            <div class="chat-container">
                <!-- Chat Header -->
                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                    <div class="bot-avatar flex-shrink-0" style="width:44px;height:44px;font-size:1.25rem;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <div class="fw-bold">المساعد الذكي</div>
                        <div class="text-muted small"><span class="text-success">●</span> متاح الآن · مدعوم بـ Gemini AI</div>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="Chatbot.newConversation()" title="محادثة جديدة">
                            <i class="bi bi-plus-circle me-1"></i>جديد
                        </button>
                        <a href="/STD/student/new_request.php" class="btn btn-sm btn-outline-secondary" title="تقديم طلب رسمي">
                            <i class="bi bi-send me-1"></i>طلب رسمي
                        </a>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <div class="bot-avatar mx-auto mb-3" style="width:64px;height:64px;font-size:1.8rem;">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h5 class="fw-bold mb-2">مرحباً! أنا مساعدك الذكي</h5>
                        <p class="text-muted mb-4">يمكنني الإجابة على أسئلتك المتعلقة بشؤون الطلاب</p>
                        <!-- Suggestions -->
                        <div class="row g-2 justify-content-center">
                            <?php
                            $suggestions = [
                                'ما هي الخدمات المتاحة؟',
                                'كيف أتواصل مع مرشدي الأكاديمي؟',
                                'كيف أقدم طلب دعم مالي؟',
                                'ما هي أوقات الدوام؟'
                            ];
                            foreach ($suggestions as $sugg): ?>
                            <div class="col-auto">
                                <button class="btn btn-outline-primary btn-sm"
                                        onclick="document.getElementById('chatInput').value=this.textContent;Chatbot.sendMessage()">
                                    <?= htmlspecialchars($sugg) ?>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="chat-message <?= $msg['sender_type'] === 'student' ? 'user-msg' : 'bot-msg' ?>">
                            <?php if ($msg['sender_type'] === 'bot'): ?>
                                <div class="bot-avatar"><i class="bi bi-robot"></i></div>
                            <?php endif; ?>
                            <div>
                                <div class="chat-bubble"><?= formatChatMessage($msg['message_text']) ?></div>
                                <div class="chat-time"><?= formatDateTime($msg['created_at']) ?></div>
                            </div>
                            <?php if ($msg['sender_type'] === 'student'): ?>
                                <img src="<?= getUserAvatar($_SESSION['profile_image'] ?? '') ?>" alt="" class="chat-avatar">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Input Area -->
                <div class="chat-input-area">
                    <div class="chat-input-wrap">
                        <textarea id="chatInput" class="form-control chat-input auto-resize"
                                  placeholder="اكتب سؤالك هنا..." rows="1"></textarea>
                        <button class="btn-send" onclick="Chatbot.sendMessage()" title="إرسال">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                    <div class="text-muted mt-2" style="font-size:.72rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        اضغط Enter للإرسال · Shift+Enter لسطر جديد
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Chatbot.init(<?= $convId > 0 ? $convId : 'null' ?>);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
