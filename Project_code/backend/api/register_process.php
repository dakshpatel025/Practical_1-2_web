<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, private');
startStudentSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

function input(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// Collect fields from the registration page
$name = input('name');
$rawUsername = input('username');
$username = strtolower($rawUsername);
$email = strtolower(input('email'));
$mobile = input('mobile');
$passwordValue = $_POST['password'] ?? '';
$confirmValue = $_POST['confirm_password'] ?? '';
$password = is_string($passwordValue) ? $passwordValue : '';
$confirm = is_string($confirmValue) ? $confirmValue : '';
$course = input('course');
$year = input('year');
$gender = input('gender');
$dob = input('dob');
$city = input('city');
$motherName = input('mother_name');
$errors = [];

// Frontend & Backend Input Validation for page fields
if (!preg_match('/^[A-Za-z\s]{3,60}$/', $name)) {
    $errors['name'] = 'Full name must be 3-60 letters only.';
}

if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
    $errors['username'] = 'Username must be 3-30 alphanumeric characters or underscores.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}

if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    $errors['mobile'] = 'Enter a valid 10-digit Indian mobile number.';
}

if (strlen($password) > 72 ||
    !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    $errors['password'] = 'Use 8-72 characters with uppercase, lowercase, digit, and symbol.';
}

if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

$allowedCourses = ['B.Tech Computer Science', 'B.Tech IT', 'BCA', 'BSc.IT', 'BBA'];
if (!in_array($course, $allowedCourses, true)) {
    $errors['course'] = 'Select a valid degree course.';
}

$allowedYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
if (!in_array($year, $allowedYears, true)) {
    $errors['year'] = 'Select a valid academic year.';
}

$allowedGenders = ['Male', 'Female', 'Other'];
if (!in_array($gender, $allowedGenders, true)) {
    $errors['gender'] = 'Select a valid gender.';
}

// Optional Date of Birth validation (if provided)
if ($dob !== '') {
    $birthDate = DateTime::createFromFormat('!Y-m-d', $dob);
    if (!$birthDate || $birthDate->format('Y-m-d') !== $dob) {
        $errors['dob'] = 'Enter a valid date of birth (YYYY-MM-DD).';
    } elseif ($birthDate > new DateTime('today')) {
        $errors['dob'] = 'Date of birth cannot be in the future.';
    } else {
        $age = (new DateTime())->diff($birthDate)->y;
        if ($age < 15 || $age > 50) {
            $errors['dob'] = 'Age must be between 15 and 50 years.';
        }
    }
}

// Optional City validation (if provided)
if ($city !== '' && !preg_match('/^[A-Za-z\s]{2,50}$/', $city)) {
    $errors['city'] = 'City must be 2-50 letters only.';
}

if ($motherName !== '' && !preg_match("/^[\\p{L}\\s.'-]{2,100}$/u", $motherName)) {
    $errors['mother_name'] = 'Enter a valid mother name.';
}

if (!isset($_POST['terms']) || !is_string($_POST['terms'])) {
    $errors['terms'] = 'You must accept the university terms.';
}

if (!empty($errors)) {
    sendJson(422, [
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'errors' => $errors
    ]);
}

$transactionStarted = false;
try {
    // Database connection using MYSQLi
    $mysqli = getMysqliConnection();

    // Check duplicate email and duplicate username using MySQLi prepared statements
    $checkStmt = $mysqli->prepare('SELECT username, email FROM students WHERE email = ? OR username = ? LIMIT 1');
    if (!$checkStmt) {
        throw new RuntimeException('Failed to prepare duplicate check statement: ' . $mysqli->error);
    }
    $checkStmt->bind_param('ss', $email, $username);

    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($existingRow = $checkResult->fetch_assoc()) {
        $checkStmt->close();
        $duplicateErrors = [];
        if (strcasecmp($existingRow['email'] ?? '', $email) === 0) {
            $duplicateErrors['email'] = 'This email address is already registered.';
        }
        if (strcasecmp($existingRow['username'] ?? '', $username) === 0) {
            $duplicateErrors['username'] = 'This username is already taken. Please choose another.';
        }
        sendJson(409, [
            'success' => false,
            'message' => !empty($duplicateErrors['email'])
                ? 'This email address is already registered.'
                : 'An account with this email or username already exists.',
            'errors' => $duplicateErrors
        ]);
    }
    $checkStmt->close();

    // Password Hashing using password_hash()
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Generate unique Student ID
    $courseCode = substr(strtoupper((string)preg_replace('/[^A-Z0-9]/i', '', $course)), 0, 3) ?: 'STU';
    $studentId = $courseCode . date('ymd') . random_int(1000, 9999);
    $registeredAt = date('Y-m-d H:i:s');
    $status = 'Active';

    $mysqli->begin_transaction();
    $transactionStarted = true;

    // Insert user details into MySQL using MySQLi prepared statement
    $insertStmt = $mysqli->prepare(
        'INSERT INTO students
            (student_id, name, username, email, mobile, course, year_of_study, gender,
             date_of_birth, city, password_hash, status, registered_at)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$insertStmt) {
        throw new RuntimeException('Failed to prepare insert statement: ' . $mysqli->error);
    }

    $dobValue = $dob !== '' ? $dob : null;
    $cityValue = $city !== '' ? $city : null;

    $insertStmt->bind_param(
        'sssssssssssss',
        $studentId,
        $name,
        $username,
        $email,
        $mobile,
        $course,
        $year,
        $gender,
        $dobValue,
        $cityValue,
        $passwordHash,
        $status,
        $registeredAt
    );

    $insertStmt->execute();
    $insertStmt->close();
    if ($motherName !== '') {
        $profileData = json_encode([
            'profile' => ['motherName' => $motherName],
            'family' => ['mother' => ['name' => $motherName]],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $profileStmt = $mysqli->prepare(
            'INSERT INTO student_profiles (student_id, profile_data) VALUES (?, ?)'
        );
        $profileStmt->bind_param('ss', $studentId, $profileData);
        $profileStmt->execute();
        $profileStmt->close();
    }
    $mysqli->commit();
    $transactionStarted = false;
    $mysqli->close();

    session_regenerate_id(true);
    $_SESSION['student_id'] = $studentId;
    $_SESSION['role'] = 'student';
    $_SESSION['last_activity'] = time();

    sendJson(201, [
        'success' => true,
        'message' => "Registration successful! Your Student ID is {$studentId}.",
        'student_id' => $studentId,
        'username' => $username,
        'name' => $name,
        'email' => $email,
        'student' => [
            'id' => $studentId,
            'student_id' => $studentId,
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'course' => $course,
            'year' => $year,
            'year_of_study' => $year,
            'gender' => $gender,
            'dob' => $dobValue,
            'birthDate' => $dobValue,
            'date_of_birth' => $dobValue,
            'city' => $cityValue,
            'birthPlace' => $cityValue,
            'status' => $status,
            'role' => 'student',
        ],
    ]);
} catch (DatabaseConnectionException $error) {
    if (isset($mysqli) && $transactionStarted) {
        $mysqli->rollback();
    }
    error_log('StudentHub registration error: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (mysqli_sql_exception $error) {
    if (isset($mysqli) && $transactionStarted) {
        $mysqli->rollback();
    }
    error_log('StudentHub registration MySQLi error: ' . $error->getMessage());
    if ($error->getCode() === 1062) {
        $errorMessage = $error->getMessage();
        $dupField = 'email';
        if (stripos($errorMessage, 'uq_students_username') !== false) {
            $dupField = 'username';
        } elseif (stripos($errorMessage, 'PRIMARY') !== false) {
            sendJson(409, ['success' => false, 'message' => 'Could not create a unique Student ID. Please submit again.']);
        }
        sendJson(409, [
            'success' => false,
            'message' => $dupField === 'username' ? 'This username is already taken.' : 'This email address is already registered.',
            'errors' => [$dupField => $dupField === 'username' ? 'Username already in use.' : 'Email already in use.']
        ]);
    }
    sendJson(500, ['success' => false, 'message' => 'Database error while saving registration.']);
} catch (Throwable $error) {
    if (isset($mysqli) && $transactionStarted) {
        $mysqli->rollback();
    }
    error_log('StudentHub registration error: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'An unexpected error occurred during registration.']);
}
