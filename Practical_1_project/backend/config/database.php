<?php
declare(strict_types=1);

class DatabaseConnectionException extends RuntimeException
{
}

function getDatabaseConnection(): PDO
{
    $host = getenv('STUDENTHUB_DB_HOST') ?: 'localhost';
    $name = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
    // XAMPP's default root account is convenient for local coursework only.
    // Set both environment variables to use a dedicated account instead.
    $user = getenv('STUDENTHUB_DB_USER');
    $password = getenv('STUDENTHUB_DB_PASSWORD');
    if ($user === false && $host === 'localhost') {
        $user = 'root';
        $password = '';
    } elseif ($user === false || $password === false) {
        error_log('StudentHub database credentials are not configured.');
        throw new DatabaseConnectionException('Database credentials are not configured. Set STUDENTHUB_DB_USER and STUDENTHUB_DB_PASSWORD.');
    }
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

    try {
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $error) {
        error_log('StudentHub database connection failed: ' . $error->getMessage());
        $mysqlError = (int)($error->errorInfo[1] ?? 0);
        if ($mysqlError === 1049) {
            throw new DatabaseConnectionException("The 'studenthub' database is not set up. Run backend/cli/setup_database.php.", 0, $error);
        }
        if ($mysqlError === 1045) {
            throw new DatabaseConnectionException('MySQL rejected the database account. Check STUDENTHUB_DB_USER and STUDENTHUB_DB_PASSWORD.', 0, $error);
        }
        throw new DatabaseConnectionException('Could not connect to MySQL. Start the XAMPP MySQL service and check the database settings.', 0, $error);
    }
}

function sendJson(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
