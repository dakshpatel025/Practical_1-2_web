<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, private');
startStudentSession();
$studentId = $_SESSION['student_id'] ?? null;
if (!is_string($studentId) || $studentId === '' || ($_SESSION['role'] ?? null) !== 'student') {
    sendJson(401, ['success' => false, 'message' => 'Please log in to view or update your profile.']);
}

function isValidProfileValue(mixed $value, int $depth = 0, string $key = ''): bool
{
    if ($depth > 4) {
        return false;
    }
    if (is_string($value)) {
        if ($key === 'photo') {
            return $value === '' || (
                strlen($value) <= 300000 &&
                preg_match('/^data:image\/jpeg;base64,[A-Za-z0-9+\/=]*$/D', $value) === 1
            );
        }
        return strlen($value) <= 20000;
    }
    if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
        return true;
    }
    if (!is_array($value) || count($value) > 100) {
        return false;
    }
    foreach ($value as $childKey => $item) {
        if ((!is_string($childKey) && !is_int($childKey)) ||
            !isValidProfileValue($item, $depth + 1, (string)$childKey)) {
            return false;
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only GET and POST requests are accepted.']);
}

try {
    $db = getDatabaseConnection();
    $query = $db->prepare(
        'SELECT s.student_id, s.name, s.username, s.email, s.mobile, s.course,
                s.year_of_study, s.gender, s.date_of_birth, s.city, s.status,
                s.registered_at, p.profile_data
         FROM students s
         LEFT JOIN student_profiles p ON p.student_id = s.student_id
         WHERE s.student_id = :student_id
         LIMIT 1'
    );
    $query->execute(['student_id' => $studentId]);
    $student = $query->fetch();
    if (!$student || $student['status'] !== 'Active') {
        $_SESSION = [];
        session_destroy();
        sendJson(401, ['success' => false, 'message' => 'Your account is unavailable. Please log in again.']);
    }

    $profileData = $student['profile_data'] === null
        ? []
        : json_decode($student['profile_data'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($profileData)) {
        throw new RuntimeException('Stored student profile data is invalid.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || strlen($rawBody) > 700000) {
            sendJson(413, ['success' => false, 'message' => 'The profile update is too large.']);
        }
        try {
            $updates = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            sendJson(400, ['success' => false, 'message' => 'The profile update is not valid JSON.']);
        }
        if (!is_array($updates) || $updates === [] ||
            array_diff(array_keys($updates), ['profile', 'family', 'hostel']) !== []) {
            sendJson(422, ['success' => false, 'message' => 'Choose a valid profile section to save.']);
        }
        foreach ($updates as $section => $sectionData) {
            if (!in_array($section, ['profile', 'family', 'hostel'], true) ||
                !is_array($sectionData) || !isValidProfileValue($sectionData)) {
                sendJson(422, ['success' => false, 'message' => 'The profile data contains invalid values.']);
            }
            $profileData[$section] = $sectionData;
        }

        if (isset($updates['profile'])) {
            $profile = $updates['profile'];
            foreach (['name', 'gender', 'birthDate', 'birthPlace', 'motherName'] as $field) {
                if (array_key_exists($field, $profile) && !is_string($profile[$field])) {
                    sendJson(422, ['success' => false, 'message' => 'The profile data contains invalid values.']);
                }
            }
            $name = trim((string)($profile['name'] ?? $student['name']));
            $gender = (string)($profile['gender'] ?? $student['gender']);
            $birthDate = (string)($profile['birthDate'] ?? $student['date_of_birth']);
            $city = trim((string)($profile['birthPlace'] ?? $student['city']));
            if (!preg_match("/^[\\p{L}\\s.'-]{2,100}$/u", $name)) {
                sendJson(422, ['success' => false, 'message' => 'Enter a valid full name.']);
            }
            if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
                sendJson(422, ['success' => false, 'message' => 'Select a valid gender.']);
            }
            if ($birthDate !== '') {
                $date = DateTime::createFromFormat('!Y-m-d', $birthDate);
                if (!$date || $date->format('Y-m-d') !== $birthDate) {
                    sendJson(422, ['success' => false, 'message' => 'Enter a valid birth date.']);
                }
                if ($date > new DateTime('today')) {
                    sendJson(422, ['success' => false, 'message' => 'Birth date cannot be in the future.']);
                }
            }
            if (mb_strlen($city, 'UTF-8') > 80) {
                sendJson(422, ['success' => false, 'message' => 'Birth place must be 80 characters or fewer.']);
            }
            if (array_key_exists('motherName', $profile) &&
                $profile['motherName'] !== '' &&
                !preg_match("/^[\\p{L}\\s.'-]{2,100}$/u", (string)$profile['motherName'])) {
                sendJson(422, ['success' => false, 'message' => 'Enter a valid mother name.']);
            }
            if (array_key_exists('motherName', $profile)) {
                if (!isset($profileData['family']['mother']) || !is_array($profileData['family']['mother'])) {
                    $profileData['family']['mother'] = [];
                }
                $profileData['family']['mother']['name'] = trim((string)$profile['motherName']);
            }
        }
        if (isset($updates['family'])) {
            foreach (['father', 'mother'] as $parent) {
                $details = $updates['family'][$parent] ?? null;
                if (!is_array($details) ||
                    !is_string($details['name'] ?? null) ||
                    !is_string($details['mobile'] ?? null) ||
                    !is_string($details['occupation'] ?? null) ||
                    !preg_match("/^[\\p{L}\\s.'-]{2,120}$/u", trim((string)($details['name'] ?? ''))) ||
                    !preg_match('/^[6-9]\d{9}$/', (string)($details['mobile'] ?? '')) ||
                    strlen((string)($details['occupation'] ?? '')) > 100) {
                    sendJson(422, [
                        'success' => false,
                        'message' => 'Enter valid parent names, 10-digit mobile numbers, and occupations.',
                    ]);
                }
            }
        }
        if (isset($updates['family']['mother']['name'])) {
            if (!isset($profileData['profile']) || !is_array($profileData['profile'])) {
                $profileData['profile'] = [];
            }
            $profileData['profile']['motherName'] = trim((string)$updates['family']['mother']['name']);
        }

        $db->beginTransaction();
        if (isset($updates['profile'])) {
            $updateStudent = $db->prepare(
                'UPDATE students
                 SET name = :name, gender = :gender, date_of_birth = :date_of_birth, city = :city
                 WHERE student_id = :student_id'
            );
            $updateStudent->execute([
                'name' => $name,
                'gender' => $gender,
                'date_of_birth' => $birthDate !== '' ? $birthDate : null,
                'city' => $city !== '' ? $city : null,
                'student_id' => $studentId,
            ]);
        }

        $encoded = json_encode($profileData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $save = $db->prepare(
            'INSERT INTO student_profiles (student_id, profile_data)
             VALUES (:student_id, :profile_data)
             ON DUPLICATE KEY UPDATE profile_data = VALUES(profile_data)'
        );
        $save->execute(['student_id' => $studentId, 'profile_data' => $encoded]);
        $db->commit();

        $query->execute(['student_id' => $studentId]);
        $student = $query->fetch();
        $profileData = json_decode($student['profile_data'], true, 512, JSON_THROW_ON_ERROR);
    }

    unset($student['profile_data']);
    $student['id'] = $student['student_id'];
    $student['year'] = $student['year_of_study'];
    $student['dob'] = $student['date_of_birth'];
    $student['birthDate'] = $student['date_of_birth'];
    $student['birthPlace'] = $student['city'];
    $student['profile'] = $profileData['profile'] ?? [];
    $student['family'] = $profileData['family'] ?? [];
    $student['hostel'] = $profileData['hostel'] ?? [];
    sendJson(200, [
        'success' => true,
        'message' => $_SERVER['REQUEST_METHOD'] === 'POST'
            ? 'Profile information saved.'
            : 'Profile information loaded.',
        'student' => $student,
    ]);
} catch (DatabaseConnectionException $error) {
    error_log('StudentHub profile request failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('StudentHub profile request failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Could not load or save profile information.']);
}
