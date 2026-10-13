<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

// إحصائيات المستخدمين حسب الأدوار (ديناميكي)
$userTypes = $pdo->query("
    SELECT ut.id, ut.title, ut.arabic_title, COUNT(u.id) as cnt
    FROM user_types ut
    LEFT JOIN users u ON u.user_type_id = ut.id
    GROUP BY ut.id ORDER BY ut.id
")->fetchAll();

// إحصائيات شاملة
$stats = [];
$stats['total_req']    = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$stats['new_req']      = $pdo->query("SELECT COUNT(*) FROM requests WHERE status='new'")->fetchColumn();
$stats['open_req']     = $pdo->query("SELECT COUNT(*) FROM requests WHERE status IN ('new','under_review')")->fetchColumn();
$stats['conversations']= $pdo->query("SELECT COUNT(*) FROM chatbot_conversations")->fetchColumn();
$stats['services']     = $pdo->query("SELECT COUNT(*) FROM services WHERE status='published'")->fetchColumn();
$stats['knowledge']    = $pdo->query("SELECT COUNT(*) FROM knowledge_base WHERE status='published'")->fetchColumn();

// طلبات حسب الحالة
$byStatus = $pdo->query("SELECT status, COUNT(*) as cnt FROM requests GROUP BY status")->fetchAll();
$statusData = array_column($byStatus, 'cnt', 'status');

// آخر الطلبات
$latestReqs = $pdo->query("
    SELECT mr.*, s.title as service_title, u.full_name as student_name
    FROM requests mr
    JOIN users u ON u.id=mr.student_id
    LEFT JOIN services s ON s.id=mr.service_id
    ORDER BY mr.created_at DESC LIMIT 6")->fetchAll();

// آخر المستخدمين
$latestUsers = $pdo->query("
    SELECT u.*, ut.arabic_title as user_type
    FROM users u JOIN user_types ut ON ut.id=u.user_type_id
    ORDER BY u.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'لوحة تحكم المدير';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="welcome-banner">
        <div class="row align-items-center">
            <div class="col">
                <h2>لوحة تحكم المدير</h2>
                <p>نظرة شاملة على جميع مكونات النظام</p>
            </div>
            <div class="col-auto d-none d-md-flex gap-2">
                <a href="/STD/manager/reports.php" class="btn btn-light fw-bold">
                    <i class="bi bi-bar-chart me-1"></i>التقارير
                </a>
            </div>
        </div>
        <i class="bi bi-graph-up banner-bg-icon"></i>
    </div>

    <!-- Stats Row 1: Users (dynamic from user_types) -->
    <?php
    $typeIconMap = [
        'student'          => ['person-badge-fill','#1a5f7a','#e8f4f8','/STD/manager/students.php'],
        'admin'            => ['person-badge-fill',  '#8e44ad','#f5eef8','/STD/manager/admins.php'],
        'manager'          => ['shield-fill',        '#e74c3c','#fdedec','/STD/manager/admins.php'],
        'academic_advisor' => ['person-check-fill',  '#27ae60','#eafaf1','/STD/manager/advisors.php'],
    ];
    $colSize = count($userTypes) > 0 ? (int)floor(12 / min(count($userTypes) + 1, 6)) : 2;
    $colSize = max($colSize, 2);
    ?>
    <div class="row g-3 mb-3">
        <?php foreach ($userTypes as $ut):
            $map   = $typeIconMap[$ut['title']] ?? ['person-fill','#555','#eee','#'];
            [$icon,$color,$bg,$link] = $map;
        ?>
        <div class="col-6 col-md-<?= $colSize ?>">
            <a href="<?= $link ?>" class="text-decoration-none">
                <div class="stat-card h-100" style="border-color:<?= $color ?>;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>;"><i class="bi bi-<?= $icon ?>"></i></div>
                        <div>
                            <div class="stat-number" style="color:<?= $color ?>;"><?= $ut['cnt'] ?></div>
                            <div class="stat-label"><?= htmlspecialchars($ut['arabic_title']) ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
        <div class="col-6 col-md-<?= $colSize ?>">
            <a href="/STD/manager/services.php" class="text-decoration-none">
                <div class="stat-card h-100" style="border-color:#2980b9;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#ebf5fb;color:#2980b9;"><i class="bi bi-grid-fill"></i></div>
                        <div>
                            <div class="stat-number" style="color:#2980b9;"><?= $stats['services'] ?></div>
                            <div class="stat-label">الخدمات</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Stats Row 2: Requests -->
    <div class="row g-3 mb-4">
        <?php
        $reqCards = [
            ['total_req','#e67e22','#fef9e7','inbox-fill','إجمالي الطلبات'],
            ['new_req','#e74c3c','#fdedec','hourglass-split','طلبات جديدة'],
            ['open_req','#f39c12','#fef9e7','clock-fill','طلبات مفتوحة'],
            ['conversations','#159895','#e8f8f5','robot','محادثات الذكاء الاصطناعي'],
        ];
        foreach ($reqCards as [$key,$color,$bg,$icon,$label]): ?>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:<?= $color ?>;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $color ?>;"><i class="bi bi-<?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-number" style="color:<?= $color ?>;"><?= $stats[$key] ?></div>
                        <div class="stat-label"><?= $label ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- Requests by Status (mini chart) -->
        <div class="col-lg-5">
            <div class="std-card p-4 h-100">
                <h5 class="fw-bold mb-4"><i class="bi bi-pie-chart me-2 text-primary"></i>توزيع الطلبات حسب الحالة</h5>
                <?php
                $statusInfo = [
                    'new'=>['جديد','primary'],
                    'under_review'=>['تحت المراجعة','warning'],
                    'replied'=>['تم الرد','info'],
                    'completed'=>['مكتمل','success'],
                    'rejected'=>['مرفوض','danger'],
                    'closed'=>['مغلق','secondary'],
                ];
                $total = array_sum($statusData) ?: 1;
                foreach ($statusInfo as $s => [$label, $color]):
                    $cnt = $statusData[$s] ?? 0;
                    $pct = round($cnt / $total * 100);
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span><?= $label ?></span>
                        <span class="fw-bold"><?= $cnt ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Latest Requests -->
        <div class="col-lg-7">
            <div class="std-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-inbox me-2 text-primary"></i>آخر الطلبات</h5>
                    <a href="/STD/manager/requests.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-std table-hover mb-0">
                        <thead><tr><th>#</th><th>الطالب</th><th>الموضوع</th><th>الحالة</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($latestReqs as $req): ?>
                            <tr>
                                <td class="text-muted small"><?= $req['id'] ?></td>
                                <td class="small fw-600"><?= htmlspecialchars(mb_substr($req['student_name'],0,20)) ?></td>
                                <td class="small"><?= htmlspecialchars(mb_substr($req['title'],0,28)) ?></td>
                                <td><?= requestStatusLabel($req['status']) ?></td>
                                <td><a href="/STD/manager/request_details.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary px-2"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Latest Users -->
        <div class="col-12">
            <div class="std-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>آخر المستخدمين</h5>
                    <a href="/STD/manager/students.php" class="btn btn-sm btn-outline-primary">عرض الطلاب</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-std table-hover mb-0">
                        <thead><tr><th>الاسم</th><th>البريد</th><th>النوع</th><th>الحالة</th><th>تاريخ التسجيل</th></tr></thead>
                        <tbody>
                            <?php foreach ($latestUsers as $u): ?>
                            <tr>
                                <td class="fw-600 small"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars($u['user_type']) ?></span></td>
                                <td><span class="badge bg-<?= $u['status']==='active'?'success':'danger' ?>"><?= $u['status']==='active'?'نشط':'موقوف' ?></span></td>
                                <td class="text-muted small"><?= formatDate($u['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
