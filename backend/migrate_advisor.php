<?php
/**
 * Migration: Move academic_advisors out of separate table into users table.
 *
 * What this does:
 *  1. NULL-out users.academic_advisor_id (old IDs referenced academic_advisors.id, not users.id)
 *  2. DROP the foreign key fk_advisor on users.academic_advisor_id
 *  3. DROP TABLE academic_advisors
 *  4. Add new FK: users.academic_advisor_id → users.id (self-referential)
 *  5. Ensure user_types has an 'academic_advisor' entry (id=4)
 *
 * Run once: http://localhost/STD/backend/migrate_advisor.php
 */

require_once __DIR__ . '/../includes/db.php';

$steps = [];

try {
    // 1. NULL out old references (they pointed to academic_advisors.id not users.id)
    $pdo->exec("UPDATE users SET academic_advisor_id = NULL");
    $steps[] = '✓ Cleared old academic_advisor_id values';

    // 2. Drop old FK (may not exist if DB was set up differently)
    try {
        $pdo->exec("ALTER TABLE users DROP FOREIGN KEY fk_advisor");
        $steps[] = '✓ Dropped old FK fk_advisor';
    } catch (PDOException $e) {
        $steps[] = '~ fk_advisor not found or already dropped (OK)';
    }

    // 3. Drop the academic_advisors table
    $pdo->exec("DROP TABLE IF EXISTS academic_advisors");
    $steps[] = '✓ Dropped table academic_advisors';

    // 4. Add new self-referential FK
    try {
        $pdo->exec("ALTER TABLE users
            ADD CONSTRAINT fk_user_advisor
            FOREIGN KEY (academic_advisor_id) REFERENCES users(id) ON DELETE SET NULL");
        $steps[] = '✓ Added new FK fk_user_advisor (users.academic_advisor_id → users.id)';
    } catch (PDOException $e) {
        $steps[] = '~ FK fk_user_advisor already exists or column missing: ' . $e->getMessage();
    }

    // 5. Ensure academic_advisor user_type exists (id=4)
    $row = $pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetch();
    if (!$row) {
        $pdo->exec("INSERT INTO user_types (title) VALUES ('academic_advisor')");
        $steps[] = '✓ Inserted user_type academic_advisor';
    } else {
        $steps[] = '✓ user_type academic_advisor already exists (id=' . $row['id'] . ')';
    }

    // 6. Seed a demo advisor user (password: Admin@123)
    $exists = $pdo->query("
        SELECT u.id FROM users u
        JOIN user_types ut ON ut.id = u.user_type_id
        WHERE ut.title = 'academic_advisor' LIMIT 1
    ")->fetch();

    if (!$exists) {
        $typeId = $pdo->query("SELECT id FROM user_types WHERE title='academic_advisor'")->fetchColumn();
        $hash   = password_hash('Admin@123', PASSWORD_BCRYPT);
        $pdo->prepare("
            INSERT INTO users (user_type_id, full_name, national_id, email, password, status, activation)
            VALUES (?, 'د. عبدالله المرشد', '2000000001', 'advisor@std.edu.sa', ?, 'active', 1)
        ")->execute([$typeId, $hash]);
        $steps[] = '✓ Seeded demo advisor: advisor@std.edu.sa / Admin@123';
    } else {
        $steps[] = '~ Demo advisor already exists';
    }

    echo "<h2 style='font-family:monospace;color:green'>Migration Complete</h2><pre>";
    echo implode("\n", $steps);
    echo "</pre><p><a href='/STD/index.php'>Go to login</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='font-family:monospace;color:red'>Migration Failed</h2><pre>";
    echo implode("\n", $steps) . "\n\nERROR: " . $e->getMessage();
    echo "</pre>";
}
