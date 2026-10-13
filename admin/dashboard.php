<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// إحصائيات عامة
$stats = [];
$stats['total_requests']     = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$stats['new_requests']       = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='new'")->fetchColumn();
$stats['under_review']       = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='under_review'")->fetchColumn();
$stats['completed']          = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='completed'")->fetchColumn();
$stats['total_conversations']= $pdo->query("SELECT COUNT(*) FROM chatbot_conversations")->fetchColumn();
$stats['knowledge_entries']  = $pdo->query("SELECT COUNT(*) FROM knowledge_base WHERE status='published'")->fetchColumn();
$stats['total_chunks']       = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks")->fetchColumn();
$stats['chunks_embedded']    = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks WHERE embedding_json IS NOT NULL")->fetchColumn();

// آخر الطلبات
$latestRequests = $pdo->query("
    SELECT mr.*, s.title as service_title, u.full_name as student_name
    FROM requests mr
    JOIN users u ON u.id = mr.student_id
    LEFT JOIN services s ON s.id = mr.service_id
    ORDER BY mr.created_at DESC LIMIT 8
")->fetchAll();

// آخر المحادثات
$latestConversations = $pdo->query("
    SELECT cc.*, u.full_name, COUNT(cm.id) as msg_count
    FROM chatbot_conversations cc
    LEFT JOIN users u ON u.id = cc.student_id
    LEFT JOIN chatbot_messages cm ON cm.conversation_id = cc.id
    GROUP BY cc.id
    ORDER BY cc.updated_at DESC LIMIT 5
")->fetchAll();

// طلبات حسب الحالة (للرسم البياني)
$requestsByStatus = $pdo->query("SELECT status, COUNT(*) as cnt FROM requests GROUP BY status")->fetchAll();

$pageTitle = 'لوحة التحكم';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="welcome-banner">
        <div class="row align-items-center">
            <div class="col">
                <h2>لوحة تحكم الإداري</h2>
                <p>مرحباً <?= htmlspecialchars($_SESSION['full_name']) ?> — نظرة عامة على النظام</p>
            </div>
            <div class="col-auto d-none d-md-flex gap-2">
                <a href="/STD/admin/requests.php" class="btn btn-light fw-bold">
                    <i class="bi bi-inbox me-1"></i>الطلبات الجديدة
                    <?php if ($stats['new_requests'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $stats['new_requests'] ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <i class="bi bi-shield-check banner-bg-icon"></i>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $statCards = [
            ['icon'=>'inbox-fill',      'color'=>'#1a5f7a', 'bg'=>'#e8f4f8', 'val'=>$stats['total_requests'],      'label'=>'إجمالي الطلبات'],
            ['icon'=>'hourglass-split', 'color'=>'#e67e22', 'bg'=>'#fef9e7', 'val'=>$stats['new_requests'],         'label'=>'طلبات جديدة'],
            ['icon'=>'search',          'color'=>'#8e44ad', 'bg'=>'#f5eef8', 'val'=>$stats['under_review'],         'label'=>'تحت المراجعة'],
            ['icon'=>'check-circle-fill','color'=>'#27ae60','bg'=>'#eafaf1', 'val'=>$stats['completed'],             'label'=>'مكتملة'],
            ['icon'=>'robot',           'color'=>'#2980b9', 'bg'=>'#ebf5fb', 'val'=>$stats['total_conversations'],  'label'=>'محادثات الشات بوت'],
            ['icon'=>'database-fill',   'color'=>'#159895', 'bg'=>'#e8f8f5', 'val'=>$stats['knowledge_entries'],    'label'=>'سجلات المعرفة'],
        ];
        foreach ($statCards as $card): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card h-100" style="border-color:<?= $card['color'] ?>;">
                <div class="d-flex flex-column align-items-center text-center gap-2">
                    <div class="stat-icon" style="background:<?= $card['bg'] ?>;color:<?= $card['color'] ?>;width:48px;height:48px;border-radius:12px;">
                        <i class="bi bi-<?= $card['icon'] ?>"></i>
                    </div>
                    <div class="stat-number" style="color:<?= $card['color'] ?>;font-size:1.6rem;"><?= $card['val'] ?></div>
                    <div class="stat-label"><?= $card['label'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Embeddings Progress -->
    <?php if ($stats['total_chunks'] > 0): ?>
    <div class="std-card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold small"><i class="bi bi-cpu me-1 text-primary"></i>تغطية الـ Embeddings</span>
            <span class="text-muted small"><?= $stats['chunks_embedded'] ?> / <?= $stats['total_chunks'] ?> مقطع</span>
        </div>
        <div class="progress" style="height:8px;">
            <?php $pct = $stats['total_chunks'] > 0 ? round($stats['chunks_embedded'] / $stats['total_chunks'] * 100) : 0; ?>
            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
        </div>
        <?php if ($pct < 100): ?>
        <div class="mt-2">
            <a href="/STD/backend/generate_embeddings.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-play-circle me-1"></i>إنشاء الـ Embeddings المتبقية
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Latest Requests -->
        <div class="col-lg-8">
            <div class="std-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-inbox me-2 text-primary"></i>آخر الطلبات</h5>
                    <a href="/STD/admin/requests.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-std table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>الطالب</th><th>الموضوع</th><th>الخدمة</th><th>الحالة</th><th>التاريخ</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestRequests as $req): ?>
                            <tr>
                                <td class="text-muted small"><?= $req['id'] ?></td>
                                <td class="small fw-600"><?= htmlspecialchars(mb_substr($req['student_name'],0,20)) ?></td>
                                <td class="small"><?= htmlspecialchars(mb_substr($req['title'],0,30)) ?></td>
                                <td><span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars($req['service_title'] ?? '—') ?></span></td>
                                <td><?= requestStatusLabel($req['status']) ?></td>
                                <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                                <td><a href="/STD/admin/request_details.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary px-2"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Latest Conversations -->
        <div class="col-lg-4">
            <div class="std-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2 text-primary"></i>محادثات حديثة</h5>
                    <a href="/STD/admin/chatbot_reviews.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <?php if (empty($latestConversations)): ?>
                    <div class="text-center text-muted py-4"><i class="bi bi-chat-dots fs-3 opacity-25 d-block mb-2"></i>لا توجد محادثات</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                    <?php foreach ($latestConversations as $conv): ?>
                        <a href="/STD/admin/chatbot_reviews.php?conv=<?= $conv['id'] ?>" class="text-decoration-none">
                            <div class="p-2 rounded-3 bg-light hover-bg" style="transition:.15s;">
                                <div class="d-flex justify-content-between">
                                    <span class="small fw-600 text-truncate" style="max-width:150px;"><?= htmlspecialchars($conv['session_title']) ?></span>
                                    <span class="badge bg-primary-soft text-primary"><?= $conv['msg_count'] ?> رسائل</span>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    <?= htmlspecialchars($conv['full_name'] ?? 'مجهول') ?> · <?= formatDate($conv['updated_at']) ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
