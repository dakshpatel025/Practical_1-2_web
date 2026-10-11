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
    echo "StudentHub database and tables are ready.\n";
} catch (Throwable $error) {
    error_log('StudentHub database setup failed: ' . $error->getMessage());
    fwrite(STDERR, "Database setup failed. Check MySQL is running and the setup account has permission to create the studenthub database.\n");
    exit(1);
}
