<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$host = getenv('STUDENTHUB_DB_HOST') ?: 'localhost';
$user = getenv('STUDENTHUB_SETUP_DB_USER') ?: 'root';
$password = getenv('STUDENTHUB_SETUP_DB_PASSWORD') ?: '';
$schemaPath = __DIR__ . '/../../database/schema.sql';
$schema = file_get_contents($schemaPath);
if ($schema === false) {
    fwrite(STDERR, "Could not read database/schema.sql.\n");
    exit(1);
}

try {
    $db = new PDO("mysql:host={$host};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    foreach (explode(';', $schema) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $db->exec($statement);
        }
    }

    $hasUsername = (int)$db->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'username'"
    )->fetchColumn() > 0;
    if (!$hasUsername) {
        $db->exec('ALTER TABLE students ADD COLUMN username VARCHAR(50) NULL AFTER name');
    }
    $hasUsernameIndex = (int)$db->query(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND INDEX_NAME = 'uq_students_username'"
    )->fetchColumn() > 0;
    if (!$hasUsernameIndex) {
        $db->exec('ALTER TABLE students ADD UNIQUE KEY uq_students_username (username)');
    }
    $hasRole = (int)$db->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'role'"
    )->fetchColumn() > 0;
    if (!$hasRole) {
        $db->exec("ALTER TABLE students ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'student' AFTER password_hash");
    }
    $hasEventPoster = (int)$db->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'poster_filename'"
    )->fetchColumn() > 0;
    if (!$hasEventPoster) {
        $db->exec('ALTER TABLE events ADD COLUMN poster_filename VARCHAR(255) NULL AFTER badge');
    }
    $db->exec(
        'CREATE TABLE IF NOT EXISTS audit_logs (
            audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_student_id VARCHAR(20) NOT NULL,
            action VARCHAR(40) NOT NULL,
            entity_type VARCHAR(40) NOT NULL,
            entity_id VARCHAR(64) NOT NULL,
            details JSON NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (audit_id),
            KEY idx_audit_logs_created_at (created_at),
            KEY idx_audit_logs_actor (actor_student_id),
            KEY idx_audit_logs_entity (entity_type, entity_id)
        ) ENGINE=InnoDB'
    );
    echo "StudentHub database and tables are ready.\n";
} catch (Throwable $error) {
    error_log('StudentHub database setup failed: ' . $error->getMessage());
    fwrite(STDERR, "Database setup failed. Check MySQL is running and the setup account has permission to create the studenthub database.\n");
    exit(1);
}
