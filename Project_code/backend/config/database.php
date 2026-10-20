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

function getMysqliConnection(): mysqli
{
    $host = getenv('STUDENTHUB_DB_HOST') ?: 'localhost';
    $name = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
    $user = getenv('STUDENTHUB_DB_USER');
    $password = getenv('STUDENTHUB_DB_PASSWORD');
    if ($user === false && $host === 'localhost') {
        $user = 'root';
        $password = '';
    } elseif ($user === false || $password === false) {
        error_log('StudentHub database credentials are not configured.');
        throw new DatabaseConnectionException('Database credentials are not configured. Set STUDENTHUB_DB_USER and STUDENTHUB_DB_PASSWORD.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $mysqli = new mysqli($host, $user, $password, $name);
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    } catch (mysqli_sql_exception $error) {
        error_log('StudentHub MySQLi connection failed: ' . $error->getMessage());
        $mysqlError = (int)$error->getCode();
        if ($mysqlError === 1049) {
            throw new DatabaseConnectionException("The 'studenthub' database is not set up. Run backend/cli/setup_database.php.", 0, $error);
        }
        if ($mysqlError === 1045) {
            throw new DatabaseConnectionException('MySQL rejected the database account. Check STUDENTHUB_DB_USER and STUDENTHUB_DB_PASSWORD.', 0, $error);
        }
        throw new DatabaseConnectionException('Could not connect to MySQL. Start the XAMPP MySQL service and check the database settings.', 0, $error);
    }
}

function startStudentSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();

    if (isset($_SESSION['student_id'])) {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if (!is_int($lastActivity) || time() - $lastActivity > 300) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                    'samesite' => $cookie['samesite'] ?? 'Lax',
                ]);
            }
            session_destroy();
            session_start();
            session_regenerate_id(true);
        } else {
            $_SESSION['last_activity'] = time();
        }
    }
}

function rememberCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function issueRememberToken(PDO $db, string $studentId): void
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60);
    $insert = $db->prepare(
        'INSERT INTO remember_tokens (selector, student_id, token_hash, expires_at)
         VALUES (:selector, :student_id, :token_hash, FROM_UNIXTIME(:expires_at))'
    );
    $insert->execute([
        'selector' => $selector,
        'student_id' => $studentId,
        'token_hash' => hash('sha256', $validator),
        'expires_at' => $expires,
    ]);
    setcookie('studenthub_remember', $selector . ':' . $validator, rememberCookieOptions($expires));
}

function clearRememberCookie(): void
{
    setcookie('studenthub_remember', '', rememberCookieOptions(time() - 42000));
}

function revokeRememberToken(PDO $db): void
{
    $cookieValue = $_COOKIE['studenthub_remember'] ?? '';
    if (is_string($cookieValue) &&
        preg_match('/^([a-f0-9]{24}):[a-f0-9]{64}$/D', $cookieValue, $matches)) {
        $db->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
            ->execute(['selector' => $matches[1]]);
    }
    clearRememberCookie();
}

function requireStudentRole(string $requiredRole): void
{
    header('Cache-Control: no-store, private');
    startStudentSession();

    $studentId = $_SESSION['student_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    if (!is_string($studentId) || !is_string($role)) {
        $cookieValue = $_COOKIE['studenthub_remember'] ?? '';
        if (is_string($cookieValue) &&
            preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/D', $cookieValue, $matches)) {
            $db = getDatabaseConnection();
            $lookup = $db->prepare(
                'SELECT r.student_id, r.token_hash, s.role
                 FROM remember_tokens r
                 JOIN students s ON s.student_id = r.student_id
                 WHERE r.selector = :selector AND r.expires_at > NOW() AND s.status = :status
                 LIMIT 1'
            );
            $lookup->execute(['selector' => $matches[1], 'status' => 'Active']);
            $token = $lookup->fetch();
            if ($token && hash_equals($token['token_hash'], hash('sha256', $matches[2]))) {
                $db->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
                    ->execute(['selector' => $matches[1]]);
                session_regenerate_id(true);
                $_SESSION['student_id'] = $token['student_id'];
                $_SESSION['role'] = $token['role'];
                $_SESSION['last_activity'] = time();
                issueRememberToken($db, $token['student_id']);
                $studentId = $token['student_id'];
                $role = $token['role'];
            } else {
                clearRememberCookie();
            }
        }
    }

    if (!is_string($studentId) || !is_string($role)) {
        header('Location: ../frontend/html/login.html', true, 302);
        exit;
    }

    $db = getDatabaseConnection();
    $accountQuery = $db->prepare(
        'SELECT role, status FROM students WHERE student_id = :student_id LIMIT 1'
    );
    $accountQuery->execute(['student_id' => $studentId]);
    $account = $accountQuery->fetch();
    if (!$account || $account['status'] !== 'Active') {
        $db->prepare('DELETE FROM remember_tokens WHERE student_id = :student_id')
            ->execute(['student_id' => $studentId]);
        $_SESSION = [];
        session_destroy();
        clearRememberCookie();
        header('Location: ../frontend/html/login.html', true, 302);
        exit;
    }
    $role = $account['role'];
    if (!in_array($role, ['student', 'admin'], true)) {
        $_SESSION = [];
        session_destroy();
        clearRememberCookie();
        header('Location: ../frontend/html/login.html', true, 302);
        exit;
    }
    $_SESSION['role'] = $role;
    if ($role !== $requiredRole) {
        header('Location: ' . ($role === 'admin' ? 'admin.php' : 'main_page.php'), true, 302);
        exit;
    }
}

function sendJson(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
