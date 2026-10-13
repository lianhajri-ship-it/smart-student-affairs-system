<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

// إحصائيات الطلبات
$reqByStatus  = $pdo->query("SELECT status, COUNT(*) as cnt FROM requests GROUP BY status")->fetchAll();
$reqByService = $pdo->query("SELECT s.title, COUNT(*) as cnt FROM requests mr LEFT JOIN services s ON s.id=mr.service_id GROUP BY mr.service_id ORDER BY cnt DESC LIMIT 10")->fetchAll();
$reqByMonth   = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as cnt FROM requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month")->fetchAll();

// إحصائيات المستخدمين
$userStats = $pdo->query("SELECT ut.title, ut.arabic_title, COUNT(u.id) as cnt FROM user_types ut LEFT JOIN users u ON u.user_type_id=ut.id GROUP BY ut.id ORDER BY cnt DESC")->fetchAll();

// إحصائيات الشات بوت
$chatStats = [
    'total_convs'   => $pdo->query("SELECT COUNT(*) FROM chatbot_conversations")->fetchColumn(),
    'total_msgs'    => $pdo->query("SELECT COUNT(*) FROM chatbot_messages")->fetchColumn(),
    'student_msgs'  => $pdo->query("SELECT COUNT(*) FROM chatbot_messages WHERE sender_type='student'")->fetchColumn(),
    'avg_per_conv'  => round($pdo->query("SELECT AVG(cnt) FROM (SELECT conversation_id, COUNT(*) cnt FROM chatbot_messages GROUP BY conversation_id) t")->fetchColumn(), 1),
];

// أكثر الخدمات طلباً
$topServices = $pdo->query("SELECT s.title, COUNT(*) as cnt FROM requests mr LEFT JOIN services s ON s.id=mr.service_id WHERE mr.service_id IS NOT NULL GROUP BY mr.service_id ORDER BY cnt DESC LIMIT 5")->fetchAll();

// نشاط المستخدمين الأخير
$activityByDay = $pdo->query("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY day ORDER BY day")->fetchAll();

$pageTitle = 'التقارير والإحصائيات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <h1><i class="bi bi-bar-chart-fill me-2"></i>التقارير والإحصائيات</h1>
        <p>نظرة تحليلية شاملة على أداء النظام</p>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php
        $summary = [
            ['إجمالي الطلاب',   $pdo->query("SELECT COUNT(*) FROM users WHERE user_type_id=1")->fetchColumn(), 'person-badge-fill','#1a5f7a','#e8f4f8'],
            ['إجمالي الطلبات',  $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn(),            'inbox-fill',       '#e67e22','#fef9e7'],
            ['المحادثات',       $chatStats['total_convs'], 'robot',              '#159895','#e8f8f5'],
            ['سجلات المعرفة',   $pdo->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn(),             'database-fill',    '#8e44ad','#f5eef8'],
        ];
        foreach ($summary as [$label,$val,$icon,$color,$bg]): ?>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:<?= $color ?>;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>;"><i class="bi bi-<?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-number" style="color:<?= $color ?>;"><?= number_format($val) ?></div>
                        <div class="stat-label"><?= $label ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- Requests by Status -->
        <div class="col-lg-4">
            <div class="std-card p-4 h-100">
                <h5 class="fw-bold mb-4"><i class="bi bi-pie-chart me-2 text-primary"></i>الطلبات حسب الحالة</h5>
                <?php
                $total = array_sum(array_column($reqByStatus,'cnt')) ?: 1;
                $colors = ['new'=>'primary','under_review'=>'warning','replied'=>'info','completed'=>'success','rejected'=>'danger','closed'=>'secondary'];
                $labels = ['new'=>'جديد','under_review'=>'تحت المراجعة','replied'=>'تم الرد','completed'=>'مكتمل','rejected'=>'مرفوض','closed'=>'مغلق'];
                foreach ($reqByStatus as $r):
                    $pct = round($r['cnt']/$total*100);
                    $color = $colors[$r['status']] ?? 'secondary';
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small"><?= $labels[$r['status']] ?? $r['status'] ?></span>
                        <strong class="small"><?= $r['cnt'] ?> (<?= $pct ?>%)</strong>
                    </div>
                    <div class="progress" style="height:10px;border-radius:5px;">
                        <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%;border-radius:5px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Requests by Service -->
        <div class="col-lg-4">
            <div class="std-card p-4 h-100">
                <h5 class="fw-bold mb-4"><i class="bi bi-grid me-2 text-primary"></i>الطلبات حسب الخدمة</h5>
                <?php $maxCnt = max(array_column($reqByService,'cnt') ?: [1]); ?>
                <?php foreach ($reqByService as $r): $pct = round($r['cnt']/$maxCnt*100); ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small"><?= htmlspecialchars($r['title'] ?? 'غير محدد') ?></span>
                        <strong class="small"><?= $r['cnt'] ?></strong>
                    </div>
                    <div class="progress" style="height:8px;border-radius:4px;">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%;border-radius:4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Stats -->
        <div class="col-lg-4">
            <div class="std-card p-4 h-100">
                <h5 class="fw-bold mb-4"><i class="bi bi-robot me-2 text-primary"></i>إحصائيات الشات بوت</h5>
                <div class="d-flex flex-column gap-3">
                    <?php
                    $chatItems = [
                        ['إجمالي المحادثات', $chatStats['total_convs'], 'chat-dots-fill','#1a5f7a'],
                        ['إجمالي الرسائل',   $chatStats['total_msgs'],  'chat-fill',     '#27ae60'],
                        ['أسئلة الطلاب',     $chatStats['student_msgs'],'question-circle-fill','#e67e22'],
                        ['معدل الرسائل/محادثة', $chatStats['avg_per_conv'],'calculator-fill','#8e44ad'],
                    ];
                    foreach ($chatItems as [$label,$val,$icon,$color]): ?>
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3 bg-light">
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                             style="width:40px;height:40px;background:<?= $color ?>20;color:<?= $color ?>;">
                            <i class="bi bi-<?= $icon ?>"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:<?= $color ?>;"><?= $val ?></div>
                            <div class="text-muted small"><?= $label ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Monthly Requests Chart (text-based) -->
        <div class="col-lg-6">
            <div class="std-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-calendar-bar me-2 text-primary"></i>الطلبات خلال آخر 6 أشهر</h5>
                <?php if (empty($reqByMonth)): ?>
                    <div class="text-center text-muted py-4">لا توجد بيانات</div>
                <?php else:
                    $maxM = max(array_column($reqByMonth,'cnt') ?: [1]);
                    foreach ($reqByMonth as $m):
                        $pct = round($m['cnt']/$maxM*100); ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small"><?= $m['month'] ?></span>
                            <strong class="small"><?= $m['cnt'] ?></strong>
                        </div>
                        <div class="progress" style="height:14px;border-radius:6px;">
                            <div class="progress-bar" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#1a5f7a,#159895);border-radius:6px;">
                                <?php if ($pct > 15): ?><span style="font-size:.68rem;padding:0 .3rem;"><?= $m['cnt'] ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>

        <!-- User Activity -->
        <div class="col-lg-6">
            <div class="std-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-activity me-2 text-primary"></i>نشاط المستخدمين (14 يوم)</h5>
                <?php if (empty($activityByDay)): ?>
                    <div class="text-center text-muted py-4">لا توجد بيانات</div>
                <?php else:
                    $maxA = max(array_column($activityByDay,'cnt') ?: [1]);
                    foreach ($activityByDay as $a):
                        $pct = round($a['cnt']/$maxA*100); ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small"><?= $a['day'] ?></span>
                            <strong class="small"><?= $a['cnt'] ?></strong>
                        </div>
                        <div class="progress" style="height:10px;border-radius:5px;">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%;border-radius:5px;"></div>
                        </div>
                    </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>

        <!-- Users by type -->
        <div class="col-12">
            <div class="std-card p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-people me-2 text-primary"></i>المستخدمون حسب الدور</h5>
                <div class="row g-3">
                    <?php
                    $typeIconMap = [
                        'student'          => ['#1a5f7a','#e8f4f8','person-badge-fill'],
                        'admin'            => ['#8e44ad','#f5eef8','person-gear-fill'],
                        'manager'          => ['#e74c3c','#fdedec','shield-fill'],
                        'academic_advisor' => ['#27ae60','#eafaf1','person-check-fill'],
                    ];
                    foreach ($userStats as $us):
                        [$color,$bg,$icon] = $typeIconMap[$us['title']] ?? ['#555','#eee','person-fill'];
                    ?>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded-3 text-center" style="background:<?= $bg ?>;">
                            <i class="bi bi-<?= $icon ?> fs-3 d-block mb-1" style="color:<?= $color ?>;"></i>
                            <div class="fw-bold fs-4" style="color:<?= $color ?>;"><?= $us['cnt'] ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($us['arabic_title']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
