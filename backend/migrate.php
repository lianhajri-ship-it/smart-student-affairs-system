<?php
/**
 * Migration Script — Run Once
 * Creates: colleges, degrees, majors, user_types tables
 * Alters: users table (adds major_id, student_type_id)
 */
require_once __DIR__ . '/../includes/db.php';

$steps = [];

// ── 1. colleges ──────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS colleges (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(200) NOT NULL,
        code       VARCHAR(20)  DEFAULT NULL,
        status     ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $steps[] = ['ok', 'جدول colleges ✓'];
} catch (PDOException $e) {
    $steps[] = ['err', 'colleges: ' . $e->getMessage()];
}

// ── 2. degrees ───────────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS degrees (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(200) NOT NULL,
        level      VARCHAR(100) DEFAULT NULL,
        status     ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $steps[] = ['ok', 'جدول degrees ✓'];
} catch (PDOException $e) {
    $steps[] = ['err', 'degrees: ' . $e->getMessage()];
}

// ── 3. majors (links college + degree) ───────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS majors (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(200) NOT NULL,
        college_id INT UNSIGNED DEFAULT NULL,
        degree_id  INT UNSIGNED DEFAULT NULL,
        status     ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL,
        FOREIGN KEY (degree_id)  REFERENCES degrees(id)  ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $steps[] = ['ok', 'جدول majors ✓'];
} catch (PDOException $e) {
    $steps[] = ['err', 'majors: ' . $e->getMessage()];
}

// ── 4. user_types (student categories: منتظم، منقول، مبتعث…) ──
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_types (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        description VARCHAR(300) DEFAULT NULL,
        status      ENUM('active','inactive') DEFAULT 'active',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $steps[] = ['ok', 'جدول user_types ✓'];
} catch (PDOException $e) {
    $steps[] = ['err', 'user_types: ' . $e->getMessage()];
}

// ── 5. ALTER users: add major_id ─────────────────────────────
try {
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'major_id'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE users ADD COLUMN major_id INT UNSIGNED DEFAULT NULL AFTER degree_id,
                    ADD FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE SET NULL");
        $steps[] = ['ok', 'عمود major_id أُضيف ✓'];
    } else {
        $steps[] = ['info', 'عمود major_id موجود مسبقاً'];
    }
} catch (PDOException $e) {
    $steps[] = ['err', 'major_id: ' . $e->getMessage()];
}

// ── 6. ALTER users: add student_type_id ──────────────────────
try {
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'student_type_id'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE users ADD COLUMN student_type_id INT UNSIGNED DEFAULT NULL AFTER major_id,
                    ADD FOREIGN KEY (student_type_id) REFERENCES user_types(id) ON DELETE SET NULL");
        $steps[] = ['ok', 'عمود student_type_id أُضيف ✓'];
    } else {
        $steps[] = ['info', 'عمود student_type_id موجود مسبقاً'];
    }
} catch (PDOException $e) {
    $steps[] = ['err', 'student_type_id: ' . $e->getMessage()];
}

// ── 7. Seed sample user_types ────────────────────────────────
try {
    $count = $pdo->query("SELECT COUNT(*) FROM user_types")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO user_types (name) VALUES
            ('منتظم'),('منقول'),('مبتعث'),('موازي'),('مدرج')");
        $steps[] = ['ok', 'بيانات user_types الأولية أُضيفت ✓'];
    } else {
        $steps[] = ['info', 'user_types تحتوي على بيانات مسبقاً'];
    }
} catch (PDOException $e) {
    $steps[] = ['err', 'seed user_types: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Migration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width:700px">
    <h4 class="mb-4">نتائج الـ Migration</h4>
    <?php foreach ($steps as [$type, $msg]): ?>
        <?php $cls = $type === 'ok' ? 'success' : ($type === 'err' ? 'danger' : 'info'); ?>
        <div class="alert alert-<?= $cls ?> py-2 mb-2"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>
    <a href="/STD/manager/dashboard.php" class="btn btn-primary mt-3">العودة للوحة التحكم</a>
</div>
</body>
</html>
