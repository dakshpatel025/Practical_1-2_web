<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, private');
startStudentSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

$identifierValue = $_POST['username'] ?? '';
$identifier = is_string($identifierValue) ? trim($identifierValue) : '';
$password = $_POST['password'] ?? '';
if (!is_string($password) || $identifier === '' || $password === '' ||
    (filter_var($identifier, FILTER_VALIDATE_EMAIL) === false &&
        !preg_match('/^[A-Za-z0-9_.-]{1,50}$/', $identifier))) {
    sendJson(422, ['success' => false, 'message' => 'Enter a valid Student ID, username, or email and password.']);
}

try {
    $db = getDatabaseConnection();
    $query = $db->prepare(
        'SELECT student_id, name, username, email, mobile, course, year_of_study, gender,
                date_of_birth, city, password_hash, status, role
         FROM students
         WHERE student_id = :sid OR username = :uname OR email = :mail
         LIMIT 1'
    );
    $query->execute([
        'sid' => $identifier,
        'uname' => $identifier,
        'mail' => $identifier,
    ]);
    $student = $query->fetch();

    $storedPassword = $student['password_hash'] ?? '';
    $validPassword = $student && password_verify($password, $storedPassword);
    if (!$validPassword || $student['status'] !== 'Active' ||
        !in_array($student['role'], ['student', 'admin'], true)) {
        sendJson(401, ['success' => false, 'message' => 'Invalid Student ID or password.']);
    }
    if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
        $update = $db->prepare('UPDATE students SET password_hash = :password_hash WHERE student_id = :student_id');
        $update->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'student_id' => $student['student_id'],
        ]);
    }

    session_regenerate_id(true);
    $_SESSION['student_id'] = $student['student_id'];
    $_SESSION['role'] = $student['role'];
    $_SESSION['last_activity'] = time();
    revokeRememberToken($db);
    if (isset($_POST['remember_me']) && $_POST['remember_me'] === '1') {
        issueRememberToken($db, $student['student_id']);
    }

    unset($student['password_hash']);
    $student['id'] = $student['student_id'];
    $student['year'] = $student['year_of_study'];
    $student['dob'] = $student['date_of_birth'];
    $student['birthDate'] = $student['date_of_birth'];
    sendJson(200, [
        'success' => true,
        'student' => $student,
        'redirect' => $student['role'] === 'admin'
            ? '../../backend/admin.php'
            : '../../backend/main_page.php',
    ]);
} catch (DatabaseConnectionException $error) {
    error_log('StudentHub login failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('StudentHub login failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Login service is unavailable. Check the database setup.']);
}
