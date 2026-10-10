<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

function input(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$name = input('name');
$email = strtolower(input('email'));
$mobile = input('mobile');
$password = input('password');
$confirm = input('confirm_password');
$course = input('course');
$year = input('year');
$gender = input('gender');
$dob = input('dob');
$city = input('city');
$errors = [];

if (!preg_match('/^[A-Za-z\s]{3,60}$/', $name)) {
    $errors['name'] = 'Full name must be 3-60 letters only.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    $errors['mobile'] = 'Enter a valid 10-digit Indian mobile number.';
}
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    $errors['password'] = 'Use at least 8 characters with uppercase, lowercase, digit and symbol.';
}
if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}
if (!in_array($course, ['B.Tech Computer Science', 'B.Tech IT', 'BCA', 'BSc.IT', 'BBA'], true)) {
    $errors['course'] = 'Select a valid degree course.';
}
if (!in_array($year, ['1st Year', '2nd Year', '3rd Year', '4th Year'], true)) {
    $errors['year'] = 'Select a valid academic year.';
}
if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
    $errors['gender'] = 'Select a valid gender.';
}
if ($dob !== '') {
    $birthDate = DateTime::createFromFormat('!Y-m-d', $dob);
    if (!$birthDate || $birthDate->format('Y-m-d') !== $dob ||
        (new DateTime())->diff($birthDate)->y < 15 || (new DateTime())->diff($birthDate)->y > 35) {
        $errors['dob'] = 'Enter a valid date of birth; age must be 15-35.';
    }
}
if ($city !== '' && !preg_match('/^[A-Za-z\s]{2,40}$/', $city)) {
    $errors['city'] = 'City must be 2-40 letters only.';
}
if (!isset($_POST['terms'])) {
    $errors['terms'] = 'You must accept the university terms.';
}
if ($errors) {
    sendJson(422, ['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors]);
}

try {
    $db = getDatabaseConnection();
    $prefix = substr(strtoupper((string)preg_replace('/[^A-Z0-9]/i', '', $course)), 0, 3) ?: 'STU';
    $studentId = $prefix . date('ymd') . random_int(1000, 9999);
    $registeredAt = date('Y-m-d H:i:s');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insert = $db->prepare(
        'INSERT INTO students
            (student_id, name, email, mobile, course, year_of_study, gender,
             date_of_birth, city, password_hash, status, registered_at)
         VALUES
            (:student_id, :name, :email, :mobile, :course, :year_of_study, :gender,
             :date_of_birth, :city, :password_hash, :status, :registered_at)'
    );
    $insert->execute([
        'student_id' => $studentId,
        'name' => $name,
        'email' => $email,
        'mobile' => $mobile,
        'course' => $course,
        'year_of_study' => $year,
        'gender' => $gender,
        'date_of_birth' => $dob !== '' ? $dob : null,
        'city' => $city,
        'password_hash' => $passwordHash,
        'status' => 'Active',
        'registered_at' => $registeredAt,
    ]);

    $student = [
        'id' => $studentId,
        'name' => $name,
        'email' => $email,
        'mobile' => $mobile,
        'course' => $course,
        'year' => $year,
        'gender' => $gender,
        'dob' => $dob,
        'city' => $city,
        'password' => $passwordHash,
        'status' => 'Active',
        'registered_at' => $registeredAt,
    ];
    $jsonPath = __DIR__ . '/../../database/data/students.json';
    $jsonOk = false;
    $jsonFile = fopen($jsonPath, 'c+');
    if ($jsonFile && flock($jsonFile, LOCK_EX)) {
        rewind($jsonFile);
        $contents = stream_get_contents($jsonFile);
        $records = $contents === '' ? [] : json_decode((string)$contents, true);
        if (is_array($records)) {
            $records[] = $student;
            $encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false && rewind($jsonFile) && ftruncate($jsonFile, 0)) {
                $jsonOk = fwrite($jsonFile, $encoded) === strlen($encoded) && fflush($jsonFile);
            }
        }
        flock($jsonFile, LOCK_UN);
        fclose($jsonFile);
    } elseif ($jsonFile) {
        fclose($jsonFile);
    }

    $csvOk = false;
    $csvPath = __DIR__ . '/../../database/data/students_registered.csv';
    $csvFile = fopen($csvPath, 'a+');
    if ($csvFile && flock($csvFile, LOCK_EX)) {
        $csvOk = (filesize($csvPath) > 0 || fputcsv($csvFile, [
            'ID', 'Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'DOB',
            'City', 'Status', 'Registered At'
        ]) !== false) && fputcsv($csvFile, [
            $studentId, $name, $email, $mobile, $course, $year, $gender, $dob,
            $city, 'Active', $registeredAt
        ]) !== false;
        fflush($csvFile);
        flock($csvFile, LOCK_UN);
        fclose($csvFile);
    } elseif ($csvFile) {
        fclose($csvFile);
    }

    $warning = (!$jsonOk || !$csvOk)
        ? 'The account was saved to the database, but one or more legacy export files could not be updated.'
        : null;
    if ($warning !== null) {
        error_log("StudentHub registration {$studentId} could not update all JSON/CSV exports.");
    }
    sendJson(201, [
        'success' => true,
        'message' => "Registration successful! Your Student ID is {$studentId}.",
        'student_id' => $studentId,
        'warning' => $warning,
    ]);
} catch (DatabaseConnectionException $error) {
    error_log('StudentHub student registration failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (PDOException $error) {
    error_log('StudentHub student registration failed: ' . $error->getMessage());
    if ($error->getCode() === '23000') {
        sendJson(409, [
            'success' => false,
            'message' => 'This email address is already registered.',
            'errors' => ['email' => 'Email already exists in our records.'],
        ]);
    }
    sendJson(500, ['success' => false, 'message' => 'Could not save the registration. Check the database setup.']);
} catch (Throwable $error) {
    error_log('StudentHub student registration failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Could not save the registration. Check the database setup.']);
}
