<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireManager();

// جلب الإعدادات
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$csrfToken = generateCsrfToken();
$pageTitle = 'إعدادات النظام';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-3">
    <div class="page-header">
        <h1><i class="bi bi-gear-fill me-2"></i>إعدادات النظام</h1>
        <p>ضبط إعدادات النظام والذكاء الاصطناعي</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- Logo Upload (separate form — needs multipart) -->
            <form method="POST" action="/STD/backend/save_settings.php" enctype="multipart/form-data" class="std-card p-4 mb-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="upload_logo">
                <h5 class="fw-bold mb-4"><i class="bi bi-image me-2 text-primary"></i>شعار النظام</h5>
                <div class="row g-3 align-items-center">
                    <?php $currentLogo = $settings['site_logo'] ?? ''; ?>
                    <?php if ($currentLogo): ?>
                    <div class="col-auto">
                        <img src="/STD/assets/uploads/<?= htmlspecialchars($currentLogo) ?>" alt="الشعار الحالي"
                             style="height:70px;max-width:200px;object-fit:contain;border:1px solid #e9ecef;border-radius:10px;padding:6px;background:#fff;">
                    </div>
                    <?php endif; ?>
                    <div class="col">
                        <label class="form-label">رفع شعار جديد</label>
                        <input type="file" name="site_logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp">
                        <div class="form-text">الصيغ المدعومة: PNG, JPG, SVG, WEBP — يُفضَّل PNG شفاف أو SVG</div>
                    </div>
                    <?php if ($currentLogo): ?>
                    <div class="col-auto align-self-end">
                        <button type="submit" name="remove_logo" value="1" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>حذف الشعار
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-upload me-2"></i>رفع الشعار
                </button>
            </form>

            <form method="POST" action="/STD/backend/save_settings.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- General Settings -->
                <div class="std-card p-4 mb-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-info-circle me-2 text-primary"></i>الإعدادات العامة</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم النظام (عربي)</label>
                            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">اسم النظام (إنجليزي)</label>
                            <input type="text" name="site_name_en" class="form-control" value="<?= htmlspecialchars($settings['site_name_en'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">اسم الجامعة</label>
                            <input type="text" name="university_name" class="form-control" value="<?= htmlspecialchars($settings['university_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني للدعم</label>
                            <input type="email" name="support_email" class="form-control" value="<?= htmlspecialchars($settings['support_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الدعم</label>
                            <input type="text" name="support_phone" class="form-control" value="<?= htmlspecialchars($settings['support_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">وضع الصيانة</label>
                            <select name="maintenance_mode" class="form-select">
                                <option value="0" <?= ($settings['maintenance_mode']??'0')==='0'?'selected':'' ?>>مفعّل (يعمل)</option>
                                <option value="1" <?= ($settings['maintenance_mode']??'0')==='1'?'selected':'' ?>>صيانة (موقوف)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- AI Settings -->
                <div class="std-card p-4 mb-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-robot me-2 text-primary"></i>إعدادات الذكاء الاصطناعي (Gemini)</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">مفتاح Gemini API <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="gemini_api_key" id="geminiKey" class="form-control"
                                       value="<?= htmlspecialchars($settings['gemini_api_key'] ?? '') ?>"
                                       placeholder="AIzaSy...">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('geminiKey',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">احصل على مفتاحك من <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نموذج Gemini</label>
                            <select name="gemini_model" class="form-select">
                                <option value="gemini-2.5-flash"      <?= ($settings['gemini_model']??'')==='gemini-2.5-flash'     ?'selected':'' ?>>gemini-2.5-flash (موصى به)</option>
                                <option value="gemini-2.5-pro"        <?= ($settings['gemini_model']??'')==='gemini-2.5-pro'       ?'selected':'' ?>>gemini-2.5-pro (أدق)</option>
                                <option value="gemini-2.5-flash-lite"  <?= ($settings['gemini_model']??'')==='gemini-2.5-flash-lite'?'selected':'' ?>>gemini-2.5-flash-lite (أسرع)</option>
                                <option value="gemini-2.0-flash"      <?= ($settings['gemini_model']??'')==='gemini-2.0-flash'     ?'selected':'' ?>>gemini-2.0-flash</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نموذج Embedding</label>
                            <select name="gemini_embedding_model" class="form-select">
                                <option value="gemini-embedding-001"       <?= ($settings['gemini_embedding_model']??'')==='gemini-embedding-001'      ?'selected':'' ?>>gemini-embedding-001 (موصى به)</option>
                                <option value="gemini-embedding-2-preview" <?= ($settings['gemini_embedding_model']??'')==='gemini-embedding-2-preview'?'selected':'' ?>>gemini-embedding-2-preview (تجريبي)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تفعيل الشات بوت</label>
                            <select name="chatbot_enabled" class="form-select">
                                <option value="1" <?= ($settings['chatbot_enabled']??'1')==='1'?'selected':'' ?>>مفعّل</option>
                                <option value="0" <?= ($settings['chatbot_enabled']??'1')==='0'?'selected':'' ?>>موقوف</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">حجم الـ Chunk (كلمة)</label>
                            <input type="number" name="chunk_size" class="form-control" value="<?= htmlspecialchars($settings['chunk_size'] ?? '500') ?>" min="100" max="2000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">أقصى Chunks للاسترجاع</label>
                            <input type="number" name="max_chunks_retrieve" class="form-control" value="<?= htmlspecialchars($settings['max_chunks_retrieve'] ?? '5') ?>" min="1" max="20">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-save me-2"></i>حفظ الإعدادات
                </button>
            </form><!-- end settings form -->
        </div>

        <!-- Info Sidebar -->
        <div class="col-lg-4">
            <div class="std-card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-1"></i>كيفية تشغيل الذكاء الاصطناعي</h6>
                <ol class="small text-muted ps-3">
                    <li class="mb-2">احصل على مفتاح API من Google AI Studio مجاناً</li>
                    <li class="mb-2">ضع المفتاح في حقل "مفتاح Gemini API" أعلاه</li>
                    <li class="mb-2">أضف محتوى لقاعدة المعرفة</li>
                    <li class="mb-2">اضغط "إنشاء Embeddings" في قاعدة المعرفة</li>
                    <li>اختبر الشات بوت مع الطلاب</li>
                </ol>
            </div>
            <div class="std-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-cpu text-primary me-1"></i>حالة الـ Embeddings</h6>
                <?php
                $total    = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks")->fetchColumn();
                $embedded = $pdo->query("SELECT COUNT(*) FROM knowledge_chunks WHERE embedding_json IS NOT NULL")->fetchColumn();
                $pending  = $total - $embedded;
                $pct      = $total > 0 ? round($embedded/$total*100) : 0;
                ?>
                <div class="mb-2 small d-flex justify-content-between">
                    <span>المعالج: <?= $embedded ?></span>
                    <span>المتبقي: <?= $pending ?></span>
                </div>
                <div class="progress mb-3" style="height:10px;">
                    <div class="progress-bar <?= $pct==100?'bg-success':'bg-primary' ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <?php if ($pending > 0): ?>
                <a href="/STD/backend/generate_embeddings.php" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-play-circle me-1"></i>إنشاء المتبقية (<?= $pending ?>)
                </a>
                <?php else: ?>
                <div class="text-success small text-center"><i class="bi bi-check-circle-fill me-1"></i>جميع المقاطع لها embedding</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
