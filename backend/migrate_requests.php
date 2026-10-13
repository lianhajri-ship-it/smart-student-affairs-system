<?php
/**
 * Migration: Service-linked requests
 *
 * 1. ADD requests.service_id  — nullable FK → services(id)
 * 2. ADD services.assigned_role      — ENUM('admin','advisor','both') DEFAULT 'admin'
 *
 * Run once: http://localhost/STD/backend/migrate_requests.php
 */
require_once __DIR__ . '/../includes/db.php';

$steps = [];

try {
    // 1. Add service_id to requests
    try {
        $pdo->exec("ALTER TABLE requests ADD COLUMN service_id INT UNSIGNED NULL AFTER student_id");
        $pdo->exec("ALTER TABLE requests ADD CONSTRAINT fk_request_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL");
        $steps[] = '✓ Added requests.service_id (FK → services.id)';
    } catch (PDOException $e) {
        $steps[] = '~ service_id already exists or error: ' . $e->getMessage();
    }

    // 2. Add assigned_role to services
    try {
        $pdo->exec("ALTER TABLE services ADD COLUMN assigned_role ENUM('admin','advisor','both') NOT NULL DEFAULT 'admin' AFTER status");
        $steps[] = '✓ Added services.assigned_role';
    } catch (PDOException $e) {
        $steps[] = '~ assigned_role already exists or error: ' . $e->getMessage();
    }

    echo "<h2 style='font-family:monospace;color:green'>Migration Complete</h2><pre>";
    echo implode("\n", $steps);
    echo "</pre><p><a href='/STD/index.php'>Go to login</a> | <a href='/STD/manager/services.php'>Services</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='font-family:monospace;color:red'>Migration Failed</h2><pre>";
    echo implode("\n", $steps) . "\n\nERROR: " . $e->getMessage();
    echo "</pre>";
}
