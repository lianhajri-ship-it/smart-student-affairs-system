<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$departments = $pdo->query("SELECT * FROM departments WHERE status='active'")->fetchAll();
$services    = $pdo->query("
    SELECT s.*, d.title as dept_title
    FROM services s LEFT JOIN departments d ON d.id=s.department_id
    ORDER BY s.id DESC")->fetchAll();

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$csrfToken = generateCsrfToken();
$pageTitle = 'إدارة الخدمات';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col"><h1><i class="bi bi-grid-fill me-2"></i>إدارة الخدمات</h1></div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#serviceModal">
                    <i class="bi bi-plus-circle me-1"></i>إضافة خدمة
                </button>
            </div>
        </div>
    </div>

    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>العنوان</th><th>القسم</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($services as $svc): ?>
                    <tr>
                        <td class="text-muted small"><?= $svc['id'] ?></td>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($svc['title']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars(mb_substr($svc['description'],0,60)) ?></div>
                        </td>
                        <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($svc['dept_title'] ?? '—') ?></span></td>
                        <td>
                            <?php if ($svc['status']==='published'): ?>
                                <span class="badge bg-success">منشور</span>
                            <?php elseif ($svc['status']==='draft'): ?>
                                <span class="badge bg-warning text-dark">مسودة</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">مؤرشف</span>
                            <?php endif; ?>
                        </td>
                        <td class="d-flex gap-1">
                            <a href="?edit=<?= $svc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="/STD/backend/delete_item.php?type=service&id=<?= $svc['id'] ?>&token=<?= urlencode($csrfToken) ?>"
                               class="btn btn-sm btn-outline-danger"
                               data-confirm="هل أنت متأكد من حذف هذه الخدمة؟"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade <?= $edit ? 'show d-block' : '' ?>" id="serviceModal" tabindex="-1"
     <?= $edit ? 'style="background:rgba(0,0,0,.5);"' : '' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit ? 'تعديل خدمة' : 'إضافة خدمة جديدة' ?></h5>
                <a href="/STD/admin/services.php" class="btn-close btn-close-white"></a>
            </div>
            <form method="POST" action="/STD/backend/save_service.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">عنوان الخدمة *</label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>منشور</option>
                                <option value="draft"     <?= ($edit['status']??'')==='draft'    ?'selected':'' ?>>مسودة</option>
                                <option value="archived"  <?= ($edit['status']??'')==='archived' ?'selected':'' ?>>مؤرشف</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <select name="department_id" class="form-select">
                                <option value="">اختر القسم</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($edit['department_id']??'')==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">أيقونة Bootstrap Icons</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text" id="iconPreview" style="min-width:44px;justify-content:center;font-size:1.2rem;">
                                    <i class="bi <?= htmlspecialchars($edit['icon'] ?? 'bi-gear') ?>" id="iconPreviewEl"></i>
                                </span>
                                <input type="text" name="icon" id="iconInput" class="form-control"
                                       value="<?= htmlspecialchars($edit['icon'] ?? 'bi-gear') ?>"
                                       placeholder="bi-gear" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleIconPicker()">
                                    <i class="bi bi-grid-3x3 me-1"></i>اختر
                                </button>
                            </div>
                            <div id="iconPickerPanel" class="border rounded-3 p-3 d-none" style="background:#f8f9fa;">
                                <input type="text" id="iconSearch" class="form-control form-control-sm mb-2" placeholder="ابحث عن أيقونة... مثال: person, file, check">
                                <div id="iconGrid" class="d-flex flex-wrap gap-1" style="max-height:200px;overflow-y:auto;"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">شروط الاستفادة</label>
                            <textarea name="conditions" class="form-control" rows="2"><?= htmlspecialchars($edit['conditions'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">المستندات المطلوبة</label>
                            <textarea name="required_documents" class="form-control" rows="2"><?= htmlspecialchars($edit['required_documents'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">خطوات التنفيذ</label>
                            <textarea name="steps" class="form-control" rows="2"><?= htmlspecialchars($edit['steps'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">كلمات مفتاحية</label>
                            <input type="text" name="keywords" class="form-control" value="<?= htmlspecialchars($edit['keywords'] ?? '') ?>" placeholder="كلمة1، كلمة2">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/STD/admin/services.php" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BI_ICONS = [
'bi-gear','bi-gear-fill','bi-tools','bi-wrench','bi-sliders','bi-bell','bi-bell-fill',
'bi-bookmark','bi-bookmark-fill','bi-calendar','bi-calendar-fill','bi-camera','bi-camera-fill',
'bi-chat','bi-chat-fill','bi-chat-dots','bi-chat-dots-fill','bi-check-circle','bi-check-circle-fill',
'bi-clock','bi-clock-fill','bi-envelope','bi-envelope-fill','bi-eye','bi-eye-fill',
'bi-file','bi-file-fill','bi-file-text','bi-file-text-fill','bi-file-earmark-text','bi-file-earmark-pdf',
'bi-folder','bi-folder-fill','bi-heart','bi-heart-fill','bi-house','bi-house-fill','bi-house-door','bi-house-door-fill',
'bi-info-circle','bi-info-circle-fill','bi-key','bi-key-fill','bi-lock','bi-lock-fill',
'bi-megaphone','bi-megaphone-fill','bi-person','bi-person-fill','bi-person-circle',
'bi-person-badge','bi-person-badge-fill','bi-person-check','bi-person-check-fill',
'bi-person-gear','bi-person-plus','bi-people','bi-people-fill',
'bi-phone','bi-telephone','bi-telephone-fill',
'bi-question-circle','bi-question-circle-fill','bi-search','bi-shield','bi-shield-fill',
'bi-shield-check','bi-shield-check-fill','bi-star','bi-star-fill','bi-trash','bi-trash-fill',
'bi-upload','bi-download','bi-share','bi-share-fill','bi-send','bi-send-fill',
'bi-pencil','bi-pencil-fill','bi-pencil-square','bi-pen','bi-pen-fill',
'bi-mortarboard','bi-mortarboard-fill','bi-building','bi-building-fill','bi-bank','bi-bank2',
'bi-award','bi-award-fill','bi-trophy','bi-trophy-fill','bi-backpack','bi-backpack-fill',
'bi-book','bi-book-fill','bi-book-half','bi-journal','bi-journal-fill','bi-journal-text','bi-journal-bookmark',
'bi-clipboard','bi-clipboard-fill','bi-clipboard-check','bi-card-text','bi-card-list','bi-newspaper',
'bi-cash','bi-cash-stack','bi-credit-card','bi-credit-card-fill','bi-wallet','bi-wallet-fill',
'bi-currency-dollar','bi-piggy-bank','bi-piggy-bank-fill',
'bi-globe','bi-globe2','bi-link','bi-wifi','bi-cpu','bi-database','bi-database-fill',
'bi-server','bi-hdd','bi-laptop','bi-printer','bi-printer-fill','bi-robot',
'bi-grid','bi-grid-fill','bi-grid-3x3','bi-list','bi-list-ul','bi-list-ol','bi-list-check',
'bi-exclamation-circle','bi-exclamation-circle-fill','bi-exclamation-triangle','bi-exclamation-triangle-fill',
'bi-check-lg','bi-x-circle','bi-x-circle-fill','bi-patch-check','bi-patch-check-fill',
'bi-hospital','bi-hospital-fill','bi-heart-pulse','bi-heart-pulse-fill','bi-capsule','bi-bandaid',
'bi-arrow-left','bi-arrow-right','bi-arrow-up','bi-arrow-down',
'bi-lightning','bi-lightning-fill','bi-sun','bi-moon','bi-tree',
'bi-shop','bi-shop-window','bi-cart','bi-cart-fill','bi-bag','bi-bag-fill',
'bi-box','bi-box-fill','bi-archive','bi-archive-fill','bi-inbox','bi-inbox-fill',
'bi-flag','bi-flag-fill','bi-pin','bi-pin-fill','bi-tags','bi-tags-fill',
'bi-image','bi-image-fill','bi-images','bi-music-note','bi-play-circle','bi-play-circle-fill',
'bi-map','bi-map-fill','bi-geo-alt','bi-geo-alt-fill','bi-compass','bi-compass-fill',
'bi-bar-chart','bi-bar-chart-fill','bi-pie-chart','bi-pie-chart-fill','bi-graph-up','bi-graph-down',
'bi-at','bi-hash','bi-whatsapp','bi-telegram','bi-twitter','bi-facebook','bi-instagram',
'bi-file-pdf','bi-file-word','bi-file-excel','bi-file-zip','bi-file-image',
];

function renderIcons(filter) {
    const grid = document.getElementById('iconGrid');
    const lower = (filter||'').toLowerCase();
    const filtered = filter ? BI_ICONS.filter(i => i.includes(lower)) : BI_ICONS;
    grid.innerHTML = filtered.map(ic =>
        `<button type="button" class="btn btn-light p-2" title="${ic}" style="font-size:1.2rem;min-width:42px;"
                 onclick="selectIcon('${ic}')"><i class="bi ${ic}"></i></button>`
    ).join('');
}

function selectIcon(icon) {
    document.getElementById('iconInput').value = icon;
    document.getElementById('iconPreviewEl').className = 'bi ' + icon;
    document.getElementById('iconPickerPanel').classList.add('d-none');
}

function toggleIconPicker() {
    const panel = document.getElementById('iconPickerPanel');
    const hidden = panel.classList.toggle('d-none');
    if (!hidden) { renderIcons(''); document.getElementById('iconSearch').focus(); }
}

document.addEventListener('DOMContentLoaded', function() {
    const inp = document.getElementById('iconInput');
    if (inp) {
        inp.addEventListener('input', function() {
            document.getElementById('iconPreviewEl').className = 'bi ' + this.value;
        });
    }
    const srch = document.getElementById('iconSearch');
    if (srch) srch.addEventListener('input', function() { renderIcons(this.value); });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
