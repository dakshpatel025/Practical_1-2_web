<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

$studentId = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
if (!is_string($studentId) || !is_string($password) ||
    !preg_match('/^[A-Za-z0-9]+$/', trim($studentId)) || $password === '') {
    sendJson(422, ['success' => false, 'message' => 'Enter a valid student ID and password.']);
}

try {
    $db = getDatabaseConnection();
    $query = $db->prepare(
        'SELECT student_id, name, email, mobile, course, year_of_study, gender,
                date_of_birth, city, password_hash, status
         FROM students
         WHERE student_id = :student_id
         LIMIT 1'
    );
    $query->execute(['student_id' => trim($studentId)]);
    $student = $query->fetch();

    $storedPassword = $student['password_hash'] ?? '';
    $passwordInfo = password_get_info($storedPassword);
    $validPassword = $student && (
        $passwordInfo['algo'] !== null
            ? password_verify($password, $storedPassword)
            : hash_equals($storedPassword, $password)
    );
    if (!$validPassword || $student['status'] !== 'Active') {
        sendJson(401, ['success' => false, 'message' => 'Invalid Student ID or password.']);
    }
    if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
        $update = $db->prepare('UPDATE students SET password_hash = :password_hash WHERE student_id = :student_id');
        $update->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'student_id' => $student['student_id'],
        ]);
    }

    unset($student['password_hash']);
    $student['id'] = $student['student_id'];
    $student['year'] = $student['year_of_study'];
    $student['dob'] = $student['date_of_birth'];
    sendJson(200, ['success' => true, 'student' => $student]);
} catch (DatabaseConnectionException $error) {
    error_log('StudentHub login failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('StudentHub login failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Login service is unavailable. Check the database setup.']);
}
