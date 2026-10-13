<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$services    = $pdo->query("SELECT id,title FROM services WHERE status='published'")->fetchAll();
$departments = $pdo->query("SELECT id,title FROM departments WHERE status='active'")->fetchAll();

$page   = max(1, (int)($_GET['page'] ?? 1));
$search = clean($_GET['q'] ?? '');
$params = [];
$where  = ['1=1'];
if (!empty($search)) {
    $where[]  = "(kb.title LIKE ? OR kb.content LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

$query  = "SELECT kb.*, d.title as dept_title,
            (SELECT COUNT(*) FROM knowledge_chunks kc WHERE kc.knowledge_id=kb.id) as chunk_count,
            (SELECT COUNT(*) FROM knowledge_chunks kc WHERE kc.knowledge_id=kb.id AND kc.embedding_json IS NOT NULL) as embedded_count
           FROM knowledge_base kb
           LEFT JOIN departments d ON d.id=kb.department_id
           WHERE " . implode(' AND ', $where) . "
           ORDER BY kb.id DESC";

$result   = paginate($pdo, $query, $params, $page, 15);
$entries  = $result['data'];

// إحصائيات
$totalChunks    = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks")->fetchColumn();
$embeddedChunks = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks WHERE embedding_json IS NOT NULL")->fetchColumn();
$pendingChunks  = $totalChunks - $embeddedChunks;

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM knowledge_base WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'قاعدة المعرفة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="bi bi-database-fill me-2"></i>قاعدة المعرفة</h1>
                <p>إدارة المحتوى المستخدم في المساعد الذكي</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <?php if ($pendingChunks > 0): ?>
                <a href="/STD/backend/generate_embeddings.php" class="btn btn-warning text-dark fw-bold">
                    <i class="bi bi-cpu me-1"></i>إنشاء Embeddings (<?= $pendingChunks ?>)
                </a>
                <?php endif; ?>
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#kbModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة
                </button>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card" style="border-color:#1a5f7a;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-soft text-primary"><i class="bi bi-database"></i></div>
                    <div>
                        <div class="stat-number text-primary"><?= $result['total'] ?></div>
                        <div class="stat-label">سجلات المعرفة</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-color:#27ae60;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-cpu-fill"></i></div>
                    <div>
                        <div class="stat-number" style="color:#27ae60;"><?= $embeddedChunks ?></div>
                        <div class="stat-label">مقاطع لها Embedding</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-color:#e67e22;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fef9e7;color:#e67e22;"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-number" style="color:#e67e22;"><?= $pendingChunks ?></div>
                        <div class="stat-label">مقاطع بانتظار المعالجة</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="std-card p-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <div class="search-bar flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="ابحث في المعرفة..." value="<?= htmlspecialchars($search) ?>">
                <i class="bi bi-search search-icon"></i>
            </div>
            <button type="submit" class="btn btn-primary px-4">بحث</button>
            <?php if ($search): ?><a href="?" class="btn btn-outline-secondary">إعادة</a><?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>العنوان</th><th>النوع</th><th>القسم</th><th>المقاطع</th><th>Embeddings</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($entries as $kb): ?>
                    <tr>
                        <td class="text-muted small"><?= $kb['id'] ?></td>
                        <td>
                            <div class="fw-600 small"><?= htmlspecialchars($kb['title']) ?></div>
                            <div class="text-muted" style="font-size:.73rem;"><?= htmlspecialchars(mb_substr($kb['content'],0,60)) ?>...</div>
                        </td>
                        <td><span class="badge bg-primary-soft text-primary"><?= knowledgeTypeLabel($kb['type']) ?></span></td>
                        <td class="small text-muted"><?= htmlspecialchars($kb['dept_title'] ?? '—') ?></td>
                        <td class="text-center"><span class="badge bg-light text-dark"><?= $kb['chunk_count'] ?></span></td>
                        <td>
                            <?php $pct = $kb['chunk_count'] > 0 ? round($kb['embedded_count']/$kb['chunk_count']*100) : 0; ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px;min-width:60px;">
                                    <div class="progress-bar <?= $pct==100?'bg-success':'bg-primary' ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span style="font-size:.72rem;"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td><span class="badge bg-<?= $kb['status']==='published'?'success':'secondary' ?>"><?= $kb['status']==='published'?'منشور':'غير منشور' ?></span></td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $kb['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="/STD/backend/delete_item.php?type=knowledge&id=<?= $kb['id'] ?>&token=<?= urlencode($csrfToken) ?>"
                               class="btn btn-sm btn-outline-danger" data-confirm="حذف هذا السجل؟"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= renderPagination($result['current_page'], $result['total_pages'], '?q='.urlencode($search)) ?></div>
</div>

<!-- Modal -->
<div class="modal fade <?= $edit?'show d-block':'' ?>" id="kbModal" tabindex="-1" <?= $edit?'style="background:rgba(0,0,0,.5);"':'' ?>>
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit?'تعديل سجل معرفة':'إضافة محتوى لقاعدة المعرفة' ?></h5>
                <a href="/STD/admin/knowledge_base.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_knowledge.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">العنوان *</label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">النوع</label>
                            <select name="type" class="form-select">
                                <?php foreach (['service'=>'خدمة','faq'=>'سؤال شائع','guide'=>'دليل','form'=>'نموذج','policy'=>'سياسة','info'=>'معلومة'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($edit['type']??'')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>منشور</option>
                                <option value="draft"     <?= ($edit['status']??'')==='draft'    ?'selected':'' ?>>مسودة</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الخدمة المرتبطة</label>
                            <select name="service_id" class="form-select">
                                <option value="">— اختياري —</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($edit['service_id']??'')==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <select name="department_id" class="form-select">
                                <option value="">— اختياري —</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($edit['department_id']??'')==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">المحتوى * <span class="text-muted small">(سيُقسَّم تلقائياً إلى مقاطع ويُنشأ له Embedding)</span></label>
                            <textarea name="content" class="form-control" rows="8" required><?= htmlspecialchars($edit['content'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">كلمات مفتاحية</label>
                            <input type="text" name="keywords" class="form-control" value="<?= htmlspecialchars($edit['keywords'] ?? '') ?>" placeholder="كلمة1، كلمة2، ...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/admin/knowledge_base.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ وإنشاء Chunks</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
