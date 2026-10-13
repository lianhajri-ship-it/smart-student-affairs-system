<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireStudent();

$search    = clean($_GET['q'] ?? '');
$deptFilter= (int)($_GET['dept'] ?? 0);

$departments = $pdo->query("SELECT * FROM departments WHERE status='active' ORDER BY title")->fetchAll();

$params = [];
$where  = ["f.status = 'published'"];
if (!empty($search)) {
    $where[]  = "(f.question LIKE ? OR f.answer LIKE ? OR f.keywords LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($deptFilter > 0) {
    $where[]  = "f.department_id = ?";
    $params[] = $deptFilter;
}

$stmt = $pdo->prepare("
    SELECT f.*, d.title as dept_title, s.title as service_title
    FROM faq f
    LEFT JOIN departments d ON d.id = f.department_id
    LEFT JOIN services s ON s.id = f.service_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY f.sort_order ASC, f.id ASC
");
$stmt->execute($params);
$faqs = $stmt->fetchAll();

$pageTitle = 'الأسئلة الشائعة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-question-circle-fill me-2"></i>الأسئلة الشائعة</h1>
                <p>إجابات على أكثر الأسئلة شيوعاً من الطلاب</p>
            </div>
            <div class="col-auto">
                <a href="/STD/student/chatbot.php" class="btn btn-light fw-bold">
                    <i class="bi bi-robot me-1"></i>لم تجد إجابتك؟ اسأل المساعد
                </a>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-6">
                <div class="search-bar">
                    <input type="text" name="q" class="form-control" placeholder="ابحث في الأسئلة الشائعة..." value="<?= htmlspecialchars($search) ?>">
                    <i class="bi bi-search search-icon"></i>
                </div>
            </div>
            <div class="col-md-4">
                <select name="dept" class="form-select">
                    <option value="0">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">بحث</button>
            </div>
        </form>
    </div>

    <!-- Results Count -->
    <?php if (!empty($search) || $deptFilter > 0): ?>
        <p class="text-muted mb-3"><i class="bi bi-info-circle me-1"></i>تم العثور على <?= count($faqs) ?> نتيجة</p>
    <?php endif; ?>

    <!-- FAQ Accordion -->
    <?php if (empty($faqs)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-question-circle"></i></div>
            <h5>لا توجد أسئلة مطابقة</h5>
            <p>جرب البحث بكلمات مختلفة أو تواصل مع المساعد الذكي</p>
            <a href="/STD/student/chatbot.php" class="btn btn-primary"><i class="bi bi-robot me-1"></i>المساعد الذكي</a>
        </div>
    <?php else: ?>
        <div class="accordion faq-accordion" id="faqAccordion">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq<?= $faq['id'] ?>">
                        <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                        <?= htmlspecialchars($faq['question']) ?>
                    </button>
                </h2>
                <div id="faq<?= $faq['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                     data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p class="mb-2"><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            <?php if ($faq['dept_title']): ?>
                                <span class="badge bg-light text-secondary"><i class="bi bi-building me-1"></i><?= htmlspecialchars($faq['dept_title']) ?></span>
                            <?php endif; ?>
                            <?php if ($faq['service_title']): ?>
                                <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($faq['service_title']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="std-card p-4 mt-4 text-center">
        <h5 class="fw-bold mb-2">لم تجد إجابة لسؤالك؟</h5>
        <p class="text-muted mb-3">تواصل مع مساعدنا الذكي أو قدم طلباً رسمياً</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/STD/student/chatbot.php" class="btn btn-primary">
                <i class="bi bi-robot me-1"></i>المساعد الذكي
            </a>
            <a href="/STD/student/new_request.php" class="btn btn-outline-primary">
                <i class="bi bi-send me-1"></i>تقديم طلب
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
