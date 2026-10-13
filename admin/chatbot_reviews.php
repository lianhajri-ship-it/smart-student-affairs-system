<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$page    = max(1, (int)($_GET['page'] ?? 1));
$search  = clean($_GET['q'] ?? '');
$convId  = (int)($_GET['conv'] ?? 0);

// جلب قائمة المحادثات
$params = [];
$where  = ['1=1'];
if (!empty($search)) {
    $where[]  = "(u.full_name LIKE ? OR cc.session_title LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$query = "SELECT cc.*, u.full_name, COUNT(cm.id) as msg_count,
           MAX(cm.confidence_score) as max_score
          FROM chatbot_conversations cc
          LEFT JOIN users u ON u.id=cc.student_id
          LEFT JOIN chatbot_messages cm ON cm.conversation_id=cc.id
          WHERE " . implode(' AND ', $where) . "
          GROUP BY cc.id ORDER BY cc.updated_at DESC";

$result  = paginate($pdo, $query, $params, $page, 15);
$convs   = $result['data'];

// تفاصيل محادثة
$messages = [];
$convDetail = null;
if ($convId > 0) {
    $stmt = $pdo->prepare("
        SELECT cc.*, u.full_name FROM chatbot_conversations cc
        LEFT JOIN users u ON u.id=cc.student_id
        WHERE cc.id=?
    ");
    $stmt->execute([$convId]);
    $convDetail = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM chatbot_messages WHERE conversation_id=? ORDER BY created_at ASC");
    $stmt->execute([$convId]);
    $messages = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'مراجعة المحادثات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <h1><i class="bi bi-chat-dots-fill me-2"></i>مراجعة محادثات الشات بوت</h1>
        <p>مراجعة جودة إجابات المساعد الذكي وتحسين قاعدة المعرفة</p>
    </div>

    <div class="row g-4">
        <!-- Conversations List -->
        <div class="col-lg-4">
            <div class="std-card">
                <div class="p-3 border-bottom">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="بحث..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div style="max-height:calc(100vh - 260px);overflow-y:auto;">
                    <?php if (empty($convs)): ?>
                        <div class="text-center text-muted py-5"><i class="bi bi-chat-dots fs-3 opacity-25 d-block mb-2"></i>لا توجد محادثات</div>
                    <?php else: ?>
                        <?php foreach ($convs as $conv): ?>
                        <a href="?conv=<?= $conv['id'] ?>&q=<?= urlencode($search) ?>"
                           class="d-block p-3 border-bottom text-decoration-none <?= $convId==$conv['id']?'bg-primary-soft':'' ?>"
                           style="transition:.15s;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-600 text-truncate small" style="max-width:160px;"><?= htmlspecialchars($conv['session_title']) ?></span>
                                <span class="badge bg-secondary small"><?= $conv['msg_count'] ?></span>
                            </div>
                            <div class="text-muted" style="font-size:.73rem;">
                                <i class="bi bi-person me-1"></i><?= htmlspecialchars($conv['full_name'] ?? 'مجهول') ?>
                            </div>
                            <div class="text-muted" style="font-size:.72rem;"><?= formatDate($conv['updated_at']) ?></div>
                        </a>
                        <?php endforeach; ?>
                        <div class="p-2"><?= renderPagination($result['current_page'], $result['total_pages'], '?q='.urlencode($search)) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Conversation Detail -->
        <div class="col-lg-8">
            <?php if ($convDetail): ?>
            <div class="std-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($convDetail['session_title']) ?></h6>
                        <div class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($convDetail['full_name'] ?? 'مجهول') ?> · <?= formatDateTime($convDetail['created_at']) ?></div>
                    </div>
                    <span class="badge bg-primary-soft text-primary"><?= count($messages) ?> رسائل</span>
                </div>
                <div style="max-height:calc(100vh - 280px);overflow-y:auto;padding:1.5rem;">
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($messages as $msg): ?>
                        <div class="<?= $msg['sender_type']==='student'?'text-end':'' ?>">
                            <div class="d-inline-block p-3 rounded-3" style="max-width:80%;
                                <?= $msg['sender_type']==='student'
                                    ? 'background:linear-gradient(135deg,#1a5f7a,#159895);color:#fff;'
                                    : 'background:#f8f9fa;border:1px solid #e9ecef;' ?>">
                                <p class="mb-1 small" style="white-space:pre-wrap;"><?= htmlspecialchars($msg['message_text']) ?></p>
                                <div class="d-flex align-items-center justify-content-between gap-3" style="font-size:.7rem;opacity:.7;">
                                    <span><?= $msg['sender_type']==='student'?'طالب':'مساعد ذكي' ?></span>
                                    <span><?= formatDateTime($msg['created_at']) ?></span>
                                    <?php if ($msg['confidence_score']): ?>
                                        <span>دقة: <?= round($msg['confidence_score']*100) ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($msg['sender_type']==='bot'): ?>
                            <div class="mt-1 d-flex gap-2 justify-content-start">
                                <form method="POST" action="/STD/backend/save_review.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="conversation_id" value="<?= $convId ?>">
                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                    <select name="review_status" class="form-select form-select-sm d-inline-block w-auto me-1">
                                        <option value="good">جيد</option>
                                        <option value="needs_improvement">يحتاج تحسين</option>
                                        <option value="bad">سيء</option>
                                    </select>
                                    <input type="text" name="review_note" class="form-control form-control-sm d-inline-block w-auto me-1" placeholder="ملاحظة...">
                                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-send"></i></button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-chat-dots"></i></div>
                <h5>اختر محادثة للمراجعة</h5>
                <p>انقر على محادثة من القائمة لعرض تفاصيلها</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
