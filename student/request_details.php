<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$userId    = getUserId();
$requestId = (int)($_GET['id'] ?? 0);

if (!$requestId) { header('Location: /STD/student/my_requests.php'); exit; }

// جلب الطلب (للطالب المالك فقط)
$stmt = $pdo->prepare("
    SELECT mr.*, s.title as service_title, u2.full_name as assigned_name
    FROM requests mr
    LEFT JOIN services s ON s.id = mr.service_id
    LEFT JOIN users u2 ON u2.id = mr.assigned_to
    WHERE mr.id = ? AND mr.student_id = ?
");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch();

if (!$request) { header('Location: /STD/student/my_requests.php'); exit; }

// جلب الردود
$stmt = $pdo->prepare("
    SELECT rr.*, u.full_name, u.profile_image, ut.title as user_type
    FROM request_replies rr
    JOIN users u ON u.id = rr.user_id
    JOIN user_types ut ON ut.id = u.user_type_id
    WHERE rr.request_id = ?
    ORDER BY rr.created_at ASC
");
$stmt->execute([$requestId]);
$replies = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
$pageTitle = 'تفاصيل الطلب #' . $requestId;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <nav class="modern-breadcrumb" aria-label="breadcrumb">
        <a href="/STD/student/dashboard.php" class="bc-home" title="الرئيسية"><i class="bi bi-house-fill"></i></a>
        <i class="bi bi-chevron-left bc-sep"></i>
        <a href="/STD/student/my_requests.php" class="bc-item"><i class="bi bi-inbox me-1"></i>طلباتي</a>
        <i class="bi bi-chevron-left bc-sep"></i>
        <span class="bc-item active"><i class="bi bi-file-text me-1"></i>طلب #<?= $requestId ?></span>
    </nav>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Request Card -->
            <div class="std-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($request['title']) ?></h4>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($request['service_title']): ?>
                            <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($request['service_title']) ?></span>
                            <?php endif; ?>
                            <?= priorityLabel($request['priority']) ?>
                            <?= requestStatusLabel($request['status']) ?>
                        </div>
                    </div>
                    <span class="text-muted small"><?= formatDateTime($request['created_at']) ?></span>
                </div>

                <div class="p-3 bg-light rounded-3 mb-3">
                    <p class="mb-0" style="white-space:pre-wrap;"><?= htmlspecialchars($request['description']) ?></p>
                </div>

                <?php if ($request['attachment']): ?>
                <div>
                    <a href="/STD/assets/uploads/<?= htmlspecialchars($request['attachment']) ?>" target="_blank"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-paperclip me-1"></i>عرض المرفق
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Replies Thread -->
            <div class="std-card p-4 mb-4">
                <h5 class="fw-bold mb-4">
                    <i class="bi bi-chat-left-text me-2 text-primary"></i>
                    الردود (<?= count($replies) ?>)
                </h5>

                <?php if (empty($replies)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-dots fs-2 d-block mb-2 opacity-25"></i>
                        لا توجد ردود بعد. سنتواصل معك قريباً.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                    <?php foreach ($replies as $reply):
                        $isMe = $reply['user_type'] === 'student';
                    ?>
                        <div class="d-flex gap-3 <?= $isMe ? 'flex-row-reverse' : '' ?>">
                            <img src="<?= getUserAvatar($reply['profile_image'] ?? '') ?>"
                                 alt="" class="rounded-circle flex-shrink-0"
                                 style="width:42px;height:42px;object-fit:cover;">
                            <div class="flex-grow-1 <?= $isMe ? 'text-end' : '' ?>">
                                <div class="d-flex align-items-center gap-2 mb-1 <?= $isMe ? 'justify-content-end' : '' ?>">
                                    <strong class="small"><?= htmlspecialchars($reply['full_name']) ?></strong>
                                    <span class="badge <?= $isMe ? 'bg-primary-soft text-primary' : 'bg-light text-secondary' ?> small">
                                        <?= $isMe ? 'أنت' : 'الإداري' ?>
                                    </span>
                                    <span class="text-muted" style="font-size:.72rem;"><?= formatDateTime($reply['created_at']) ?></span>
                                </div>
                                <div class="p-3 rounded-3 d-inline-block text-start" style="max-width:90%;
                                    <?= $isMe ? 'background:linear-gradient(135deg,#1a5f7a,#159895);color:#fff;' : 'background:#f8f9fa;border:1px solid #e9ecef;' ?>">
                                    <p class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($reply['reply_message']) ?></p>
                                    <?php if ($reply['attachment']): ?>
                                        <a href="/STD/assets/uploads/<?= htmlspecialchars($reply['attachment']) ?>"
                                           target="_blank" class="btn btn-sm btn-light mt-2">
                                            <i class="bi bi-paperclip me-1"></i>مرفق
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reply Form (if not closed/completed/rejected) -->
            <?php if (!in_array($request['status'], ['closed', 'completed', 'rejected'])): ?>
            <div class="std-card p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-reply me-2 text-primary"></i>أضف رداً</h5>
                <form method="POST" action="/STD/backend/save_reply.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                    <input type="hidden" name="redirect" value="student">
                    <div class="mb-3">
                        <textarea name="reply_message" class="form-control auto-resize" rows="4"
                                  placeholder="اكتب ردك هنا..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="file" name="attachment" class="form-control"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>إرسال الرد
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="std-card p-4 mb-3">
                <h6 class="fw-bold mb-3">معلومات الطلب</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">رقم الطلب</td><td class="fw-600 small">#<?= $request['id'] ?></td></tr>
                    <tr><td class="text-muted small">الخدمة</td><td class="small"><?= htmlspecialchars($request['service_title'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted small">الأولوية</td><td><?= priorityLabel($request['priority']) ?></td></tr>
                    <tr><td class="text-muted small">الحالة</td><td><?= requestStatusLabel($request['status']) ?></td></tr>
                    <tr><td class="text-muted small">تاريخ التقديم</td><td class="small"><?= formatDateTime($request['created_at']) ?></td></tr>
                    <tr><td class="text-muted small">آخر تحديث</td><td class="small"><?= formatDateTime($request['updated_at']) ?></td></tr>
                    <?php if ($request['assigned_name']): ?>
                    <tr><td class="text-muted small">مُسند إلى</td><td class="small fw-600"><?= htmlspecialchars($request['assigned_name']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="std-card p-4">
                <h6 class="fw-bold mb-3">تتبع الطلب</h6>
                <div class="timeline">
                    <?php
                    $allStatuses = ['new','under_review','replied','completed'];
                    $currentIdx  = array_search($request['status'], $allStatuses);
                    $labels = ['جديد','تحت المراجعة','تم الرد','مكتمل'];
                    $icons  = ['inbox','search','chat-dots','check-circle'];
                    foreach ($allStatuses as $i => $s):
                        $done   = $currentIdx !== false && $i <= $currentIdx;
                        $active = $currentIdx !== false && $i === $currentIdx;
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:32px;height:32px;
                             background:<?= $done ? 'var(--primary)' : '#e9ecef' ?>;
                             color:<?= $done ? '#fff' : '#adb5bd' ?>;">
                            <i class="bi bi-<?= $icons[$i] ?>" style="font-size:.8rem;"></i>
                        </div>
                        <span class="small <?= $active ? 'fw-bold text-primary' : ($done ? '' : 'text-muted') ?>">
                            <?= $labels[$i] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
