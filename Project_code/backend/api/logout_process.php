<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, private');
startStudentSession();
$db = getDatabaseConnection();
revokeRememberToken($db);

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
header('Location: ../../frontend/html/login.html', true, 302);
exit;
