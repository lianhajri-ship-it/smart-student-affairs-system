<?php
// ===================================================
// دوال مساعدة عامة للنظام
// ===================================================

// تنظيف المدخلات
function clean(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// رسائل الفلاش (Flash Messages)
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                <i class='bi bi-" . ($type === 'success' ? 'check-circle' : 'exclamation-circle') . "-fill me-2'></i>
                {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// تسجيل النشاط
function logActivity(PDO $pdo, int $userId, string $actionType, string $description = ''): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $actionType, $description, $ip]);
}

// إرسال إشعار
function sendNotification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', string $link = ''): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $message, $type, $link]);
}

// عدد الإشعارات غير المقروءة
function getUnreadNotifications(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// جلب آخر الإشعارات
function getLatestNotifications(PDO $pdo, int $userId, int $limit = 5): array {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// تحويل حالة الطلب إلى عربي
function requestStatusLabel(string $status): string {
    $labels = [
        'new'          => '<span class="badge bg-primary">جديد</span>',
        'under_review' => '<span class="badge bg-warning text-dark">تحت المراجعة</span>',
        'replied'      => '<span class="badge bg-info text-dark">تم الرد</span>',
        'completed'    => '<span class="badge bg-success">مكتمل</span>',
        'rejected'     => '<span class="badge bg-danger">مرفوض</span>',
        'closed'       => '<span class="badge bg-secondary">مغلق</span>',
    ];
    return $labels[$status] ?? '<span class="badge bg-secondary">' . clean($status) . '</span>';
}

// تحويل الأولوية إلى عربي
function priorityLabel(string $priority): string {
    $labels = [
        'low'    => '<span class="badge bg-success">منخفضة</span>',
        'medium' => '<span class="badge bg-warning text-dark">متوسطة</span>',
        'high'   => '<span class="badge bg-danger">عالية</span>',
    ];
    return $labels[$priority] ?? '<span class="badge bg-secondary">' . clean($priority) . '</span>';
}

// تحويل نوع قاعدة المعرفة إلى عربي
function knowledgeTypeLabel(string $type): string {
    $labels = [
        'service' => 'خدمة',
        'faq'     => 'سؤال شائع',
        'guide'   => 'دليل',
        'form'    => 'نموذج',
        'policy'  => 'سياسة',
        'info'    => 'معلومة',
    ];
    return $labels[$type] ?? $type;
}

// تنسيق التاريخ بالعربي
function formatDate(string $date): string {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return date('Y/m/d', $timestamp);
}

function formatDateTime(string $date): string {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return date('Y/m/d H:i', $timestamp);
}

// رفع الملفات
function uploadFile(array $file, string $uploadDir, array $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'حدث خطأ أثناء رفع الملف'];
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'حجم الملف يتجاوز الحد المسموح (5 ميغابايت)'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'error' => 'نوع الملف غير مسموح. المسموح: ' . implode(', ', $allowedTypes)];
    }

    $fullDir = $_SERVER['DOCUMENT_ROOT'] . '/STD/assets/uploads/' . $uploadDir . '/';
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }

    $fileName = uniqid('file_', true) . '.' . $ext;
    $filePath = $fullDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'error' => 'فشل في حفظ الملف'];
    }

    return ['success' => true, 'path' => $uploadDir . '/' . $fileName];
}

// تحويل نص المحادثة من Markdown إلى HTML آمن
function formatChatMessage(string $text): string {
    // 1. Escape HTML
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // 2. Markdown links [text](url)
    $text = preg_replace(
        '/\[([^\]]+)\]\((\/[^)]+)\)/',
        '<a href="$2" target="_blank" class="chat-link">$1 <i class="bi bi-download"></i></a>',
        $text
    );
    // 3. Bold **text**
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    // 4. Bullet points
    $text = preg_replace('/(^|\n)[•\-] (.+)/', '$1<span class="chat-bullet">•</span> $2', $text);
    // 5. Line breaks
    $text = nl2br($text);
    return $text;
}

// تقسيم النص إلى chunks
function splitIntoChunks(string $text, int $chunkSize = 500): array {
    $text = trim($text);
    if (empty($text)) return [];

    $words = explode(' ', $text);
    $chunks = [];
    $current = '';

    foreach ($words as $word) {
        if (mb_strlen($current . ' ' . $word) > $chunkSize && !empty($current)) {
            $chunks[] = trim($current);
            $current = $word;
        } else {
            $current .= ($current ? ' ' : '') . $word;
        }
    }

    if (!empty($current)) {
        $chunks[] = trim($current);
    }

    return $chunks;
}

// حساب التشابه بين متجهين (Cosine Similarity)
function cosineSimilarity(array $vecA, array $vecB): float {
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;

    $len = min(count($vecA), count($vecB));
    for ($i = 0; $i < $len; $i++) {
        $dotProduct += $vecA[$i] * $vecB[$i];
        $normA += $vecA[$i] ** 2;
        $normB += $vecB[$i] ** 2;
    }

    if ($normA == 0 || $normB == 0) return 0;
    return $dotProduct / (sqrt($normA) * sqrt($normB));
}

// جلب أفضل chunks من قاعدة المعرفة
function findRelevantChunks(PDO $pdo, array $queryEmbedding, int $limit = 5): array {
    $stmt = $pdo->query("SELECT kc.id, kc.chunk_text, kc.embedding_json, kb.title as kb_title
                          FROM knowledge_chunks kc
                          JOIN knowledge_base kb ON kb.id = kc.knowledge_id
                          WHERE kc.embedding_json IS NOT NULL AND kb.status = 'published'");
    $chunks = $stmt->fetchAll();

    $scored = [];
    foreach ($chunks as $chunk) {
        $embedding = json_decode($chunk['embedding_json'], true);
        if (!is_array($embedding)) continue;
        $score = cosineSimilarity($queryEmbedding, $embedding);
        $scored[] = ['chunk' => $chunk, 'score' => $score];
    }

    usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($scored, 0, $limit);
}

// إنشاء CSRF token
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// صورة المستخدم
function getUserAvatar(string $imagePath = '', string $gender = 'male'): string {
    if (!empty($imagePath) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/STD/assets/uploads/' . $imagePath)) {
        return '/STD/assets/uploads/' . $imagePath;
    }
    return '/STD/assets/images/' . ($gender === 'female' ? 'avatar-female.svg' : 'avatar-male.svg');
}

// ترقيم الصفحات
function paginate(PDO $pdo, string $query, array $params, int $page, int $perPage = 10): array {
    $countQuery = "SELECT COUNT(*) FROM (" . $query . ") AS total_count";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $totalPages = (int)ceil($total / $perPage);
    $page = max(1, min($page, $totalPages ?: 1));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare($query . " LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    return [
        'data'        => $data,
        'total'       => $total,
        'per_page'    => $perPage,
        'current_page'=> $page,
        'total_pages' => $totalPages,
    ];
}

// عرض أزرار الصفحات
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm justify-content-center flex-wrap">';

    if ($currentPage > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page=" . ($currentPage - 1) . "'>السابق</a></li>";
    }

    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}&page={$i}'>{$i}</a></li>";
    }

    if ($currentPage < $totalPages) {
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page=" . ($currentPage + 1) . "'>التالي</a></li>";
    }

    $html .= '</ul></nav>';
    return $html;
}
