<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/admin_audit.php';
require_once __DIR__ . '/event_posters.php';
requireStudentRole('admin');

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function requestString(array $source, string $key, string $default = ''): string
{
    $value = $source[$key] ?? $default;
    if (!is_string($value)) {
        throw new RuntimeException('Invalid form data was submitted. Please review the fields and try again.');
    }
    return $value;
}

function bindMysqliParams(mysqli_stmt $statement, string $types, array &$params): void
{
    if ($types === '') {
        return;
    }

    $references = [];
    foreach ($params as $key => &$value) {
        $references[$key] = &$value;
    }
    $statement->bind_param($types, ...$references);
}

startStudentSession();
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_csrf_token'];
$flashMessage = '';
$flashType = 'success';
$students = [];
$editStudent = null;
$events = [];
$dashboardStats = ['students' => 0, 'active_students' => 0, 'admins' => 0, 'events' => 0, 'registrations' => 0];
$latestRegistrations = [];
$latestAuditLogs = [];
$editEvent = null;
$pendingPosterFilename = null;
$transactionStarted = false;
$currentAction = '';
$filters = [
    'search' => '',
    'course' => '',
    'year' => '',
    'status' => '',
];
$allowedStatuses = ['Active', 'Inactive'];

try {
    $filters = [
        'search' => trim(requestString($_GET, 'search')),
        'course' => trim(requestString($_GET, 'course')),
        'year' => trim(requestString($_GET, 'year')),
        'status' => trim(requestString($_GET, 'status')),
    ];
    if (strlen($filters['search']) > 254 || strlen($filters['course']) > 100 ||
        strlen($filters['year']) > 20) {
        throw new RuntimeException('Search and filter values are too long.');
    }

    $db = getMysqliConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = requestString($_POST, 'csrf_token');
        if (!hash_equals($csrfToken, $submittedToken)) {
            http_response_code(403);
            throw new RuntimeException('Your session could not be verified. Reload the page and try again.');
        }

        $action = requestString($_POST, 'action');
        $currentAction = $action;
        $studentId = trim(requestString($_POST, 'student_id', ''));

        if (in_array($action, ['create_event', 'update_event', 'delete_event'], true)) {
            if ($action === 'delete_event') {
                $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
                if ($eventId === false || $eventId < 1) {
                    throw new RuntimeException('Select a valid event to delete.');
                }
                $lookup = $db->prepare('SELECT title, poster_filename FROM events WHERE event_id = ?');
                $lookup->bind_param('i', $eventId);
                $lookup->execute();
                $event = $lookup->get_result()->fetch_assoc();
                $lookup->close();
                if ($event === null) {
                    throw new RuntimeException('Event not found; nothing was deleted.');
                }
                $db->begin_transaction();
                $transactionStarted = true;
                $statement = $db->prepare('DELETE FROM events WHERE event_id = ?');
                $statement->bind_param('i', $eventId);
                $statement->execute();
                $statement->close();
                recordAdminAudit(
                    $db,
                    (string)$_SESSION['student_id'],
                    'delete',
                    'event',
                    (string)$eventId,
                    ['title' => $event['title']]
                );
                $db->commit();
                $transactionStarted = false;
                removeEventPoster($event['poster_filename']);
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Event deleted successfully.'];
                header('Location: admin.php#events', true, 303);
                exit;
            }

            $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
            $title = trim(requestString($_POST, 'event_title'));
            $category = trim(requestString($_POST, 'event_category'));
            $eventDate = trim(requestString($_POST, 'event_date'));
            $startTime = trim(requestString($_POST, 'start_time'));
            $venue = trim(requestString($_POST, 'venue'));
            $capacity = filter_var($_POST['seat_capacity'] ?? null, FILTER_VALIDATE_INT);
            $organizer = trim(requestString($_POST, 'organizer'));
            $description = trim(requestString($_POST, 'description'));
            $badge = trim(requestString($_POST, 'badge', 'Open'));
            $removePoster = requestString($_POST, 'remove_poster') === '1';

            if (($action === 'update_event' && ($eventId === false || $eventId < 1)) ||
                $title === '' || mb_strlen($title, 'UTF-8') > 180 ||
                $category === '' || mb_strlen($category, 'UTF-8') > 60 ||
                $venue === '' || mb_strlen($venue, 'UTF-8') > 180 ||
                $capacity === false || $capacity < 1 || $capacity > 4294967295 ||
                $organizer === '' || mb_strlen($organizer, 'UTF-8') > 180 ||
                $description === '' || strlen($description) > 65535 ||
                $badge === '' || mb_strlen($badge, 'UTF-8') > 40) {
                throw new RuntimeException('Complete the required event fields with valid values.');
            }
            $parsedEventDate = DateTime::createFromFormat('!Y-m-d', $eventDate);
            $dateErrors = DateTime::getLastErrors();
            if ($parsedEventDate === false ||
                ($dateErrors !== false &&
                    ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
                $parsedEventDate->format('Y-m-d') !== $eventDate) {
                throw new RuntimeException('Enter a valid event date.');
            }
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $startTime)) {
                throw new RuntimeException('Enter a valid event start time.');
            }

            $oldPosterFilename = null;
            if ($action === 'update_event') {
                $lookup = $db->prepare('SELECT poster_filename FROM events WHERE event_id = ?');
                $lookup->bind_param('i', $eventId);
                $lookup->execute();
                $existingEvent = $lookup->get_result()->fetch_assoc();
                $lookup->close();
                if ($existingEvent === null) {
                    throw new RuntimeException('Event not found; no changes were saved.');
                }
                $oldPosterFilename = $existingEvent['poster_filename'];

                $registrationCount = $db->prepare('SELECT COUNT(*) AS registered FROM registrations WHERE event_id = ?');
                $registrationCount->bind_param('i', $eventId);
                $registrationCount->execute();
                $registered = (int)$registrationCount->get_result()->fetch_assoc()['registered'];
                $registrationCount->close();
                if ($capacity < $registered) {
                    throw new RuntimeException("Seat capacity cannot be lower than the {$registered} existing registration(s).");
                }
            }

            $uploadedPosterFilename = saveEventPoster();
            $pendingPosterFilename = $uploadedPosterFilename;
            $posterFilename = $uploadedPosterFilename ?? ($removePoster ? null : $oldPosterFilename);
            $db->begin_transaction();
            $transactionStarted = true;
            if ($action === 'create_event') {
                $statement = $db->prepare(
                    'INSERT INTO events
                        (title, category, event_date, start_time, venue, seat_capacity, organizer, description, badge, poster_filename)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->bind_param(
                    'sssssissss',
                    $title,
                    $category,
                    $eventDate,
                    $startTime,
                    $venue,
                    $capacity,
                    $organizer,
                    $description,
                    $badge,
                    $posterFilename
                );
                $statement->execute();
                $eventId = $statement->insert_id;
                $statement->close();
                recordAdminAudit(
                    $db,
                    (string)$_SESSION['student_id'],
                    'create',
                    'event',
                    (string)$eventId,
                    ['title' => $title, 'poster_uploaded' => $uploadedPosterFilename !== null]
                );
                $db->commit();
                $transactionStarted = false;
                $pendingPosterFilename = null;
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Event added successfully.'];
            } else {
                $statement = $db->prepare(
                    'UPDATE events
                     SET title = ?, category = ?, event_date = ?, start_time = ?, venue = ?, seat_capacity = ?,
                         organizer = ?, description = ?, badge = ?, poster_filename = ?
                     WHERE event_id = ?'
                );
                $statement->bind_param(
                    'sssssissssi',
                    $title,
                    $category,
                    $eventDate,
                    $startTime,
                    $venue,
                    $capacity,
                    $organizer,
                    $description,
                    $badge,
                    $posterFilename,
                    $eventId
                );
                $statement->execute();
                $statement->close();
                recordAdminAudit(
                    $db,
                    (string)$_SESSION['student_id'],
                    'update',
                    'event',
                    (string)$eventId,
                    [
                        'title' => $title,
                        'poster_uploaded' => $uploadedPosterFilename !== null,
                        'poster_removed' => $removePoster && $uploadedPosterFilename === null,
                    ]
                );
                $db->commit();
                $transactionStarted = false;
                $pendingPosterFilename = null;
                if ($uploadedPosterFilename !== null || $removePoster) {
                    removeEventPoster($oldPosterFilename);
                }
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Event updated successfully.'];
            }
            header('Location: admin.php#events', true, 303);
            exit;
        }

        if ($action === 'delete') {
            if ($studentId === '') {
                throw new RuntimeException('Select a student record to delete.');
            }
            if ($studentId === (string)($_SESSION['student_id'] ?? '')) {
                throw new RuntimeException('You cannot delete the account currently being used for administration.');
            }

            $statement = $db->prepare('DELETE FROM students WHERE student_id = ?');
            $db->begin_transaction();
            $transactionStarted = true;
            $statement->bind_param('s', $studentId);
            $statement->execute();
            if ($statement->affected_rows !== 1) {
                throw new RuntimeException('Student record was not found; nothing was deleted.');
            }
            $statement->close();
            recordAdminAudit(
                $db,
                (string)$_SESSION['student_id'],
                'delete',
                'student',
                $studentId
            );
            $db->commit();
            $transactionStarted = false;
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Student record deleted successfully.'];
            header('Location: admin.php', true, 303);
            exit;
        }

        if ($action === 'create' || $action === 'update') {
            $name = trim(requestString($_POST, 'name'));
            $email = trim(requestString($_POST, 'email'));
            $mobile = trim(requestString($_POST, 'mobile'));
            $course = trim(requestString($_POST, 'course'));
            $year = trim(requestString($_POST, 'year_of_study'));
            $gender = trim(requestString($_POST, 'gender'));
            $dateOfBirth = trim(requestString($_POST, 'date_of_birth'));
            $city = trim(requestString($_POST, 'city'));
            $status = trim(requestString($_POST, 'status', 'Active'));

            if ($studentId === '' || strlen($studentId) > 20 ||
                $name === '' || strlen($name) > 100 ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 ||
                $course === '' || strlen($course) > 100 ||
                $year === '' || strlen($year) > 20 ||
                strlen($mobile) > 20 || strlen($gender) > 20 ||
                strlen($city) > 80 || !in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Enter a valid student ID, name, email, course, year, and status. Field lengths must fit the form.');
            }
            if ($dateOfBirth !== '') {
                $parsedDate = DateTime::createFromFormat('!Y-m-d', $dateOfBirth);
                $dateErrors = DateTime::getLastErrors();
                if ($parsedDate === false ||
                    ($dateErrors !== false &&
                        ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
                    $parsedDate->format('Y-m-d') !== $dateOfBirth) {
                    throw new RuntimeException('Enter a valid date of birth.');
                }
            }
            $dateOfBirth = $dateOfBirth === '' ? null : $dateOfBirth;
            $mobile = $mobile === '' ? null : $mobile;
            $gender = $gender === '' ? null : $gender;
            $city = $city === '' ? null : $city;

            if ($action === 'create') {
                $password = requestString($_POST, 'password');
                if (strlen($password) < 8) {
                    throw new RuntimeException('Choose an initial password with at least 8 characters.');
                }

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $db->begin_transaction();
                $transactionStarted = true;
                $statement = $db->prepare(
                    'INSERT INTO students
                     (student_id, name, email, mobile, course, year_of_study, gender, date_of_birth, city, password_hash, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->bind_param(
                    'sssssssssss',
                    $studentId,
                    $name,
                    $email,
                    $mobile,
                    $course,
                    $year,
                    $gender,
                    $dateOfBirth,
                    $city,
                    $passwordHash,
                    $status
                );
                $statement->execute();
                $statement->close();
                recordAdminAudit(
                    $db,
                    (string)$_SESSION['student_id'],
                    'create',
                    'student',
                    $studentId
                );
                $db->commit();
                $transactionStarted = false;
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Student record added successfully.'];
            } else {
                if ($studentId === (string)($_SESSION['student_id'] ?? '') && $status !== 'Active') {
                    throw new RuntimeException('You cannot deactivate the account currently being used for administration.');
                }

                $password = requestString($_POST, 'password');
                if ($password !== '' && strlen($password) < 8) {
                    throw new RuntimeException('A new password must contain at least 8 characters.');
                }
                if ($password !== '') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $statement = $db->prepare(
                        'UPDATE students SET name = ?, email = ?, mobile = ?, course = ?, year_of_study = ?,
                         gender = ?, date_of_birth = ?, city = ?, status = ?, password_hash = ?
                         WHERE student_id = ?'
                    );
                    $statement->bind_param(
                        'sssssssssss',
                        $name,
                        $email,
                        $mobile,
                        $course,
                        $year,
                        $gender,
                        $dateOfBirth,
                        $city,
                        $status,
                        $passwordHash,
                        $studentId
                    );
                } else {
                    $statement = $db->prepare(
                        'UPDATE students SET name = ?, email = ?, mobile = ?, course = ?, year_of_study = ?,
                         gender = ?, date_of_birth = ?, city = ?, status = ?
                         WHERE student_id = ?'
                    );
                    $statement->bind_param(
                        'ssssssssss',
                        $name,
                        $email,
                        $mobile,
                        $course,
                        $year,
                        $gender,
                        $dateOfBirth,
                        $city,
                        $status,
                        $studentId
                    );
                }
                $db->begin_transaction();
                $transactionStarted = true;
                $statement->execute();
                if ($statement->affected_rows === 0) {
                    $exists = $db->prepare('SELECT student_id FROM students WHERE student_id = ?');
                    $exists->bind_param('s', $studentId);
                    $exists->execute();
                    $exists->store_result();
                    $recordExists = $exists->num_rows === 1;
                    $exists->close();
                    if (!$recordExists) {
                        throw new RuntimeException('Student record was not found; no changes were saved.');
                    }
                }
                $statement->close();
                recordAdminAudit(
                    $db,
                    (string)$_SESSION['student_id'],
                    'update',
                    'student',
                    $studentId
                );
                $db->commit();
                $transactionStarted = false;
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Student record updated successfully.'];
            }

            header('Location: admin.php', true, 303);
            exit;
        }

        throw new RuntimeException('The requested student action is not available.');
    }

    if (isset($_SESSION['admin_flash']) && is_array($_SESSION['admin_flash'])) {
        $flashType = $_SESSION['admin_flash']['type'] === 'error' ? 'error' : 'success';
        $flashMessage = (string)$_SESSION['admin_flash']['message'];
        unset($_SESSION['admin_flash']);
    }

    $editId = trim(requestString($_GET, 'edit'));
    if ($editId !== '') {
        $statement = $db->prepare(
            'SELECT student_id, name, email, mobile, course, year_of_study, gender, date_of_birth, city, status
             FROM students WHERE student_id = ? LIMIT 1'
        );
        $statement->bind_param('s', $editId);
        $statement->execute();
        $editStudent = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($editStudent === null) {
            $flashType = 'error';
            $flashMessage = 'The selected student record was not found.';
        }
    }

    $editEventId = filter_var($_GET['edit_event'] ?? null, FILTER_VALIDATE_INT);
    if ($editEventId !== false && $editEventId !== null && $editEventId > 0) {
        $statement = $db->prepare(
            'SELECT event_id, title, category, event_date, start_time, venue, seat_capacity, organizer,
                    description, badge, poster_filename
             FROM events WHERE event_id = ? LIMIT 1'
        );
        $statement->bind_param('i', $editEventId);
        $statement->execute();
        $editEvent = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($editEvent === null) {
            $flashType = 'error';
            $flashMessage = 'The selected event was not found.';
        } else {
            $editEvent['event_date'] = substr($editEvent['event_date'], 0, 10);
            $editEvent['start_time'] = substr($editEvent['start_time'], 0, 5);
        }
    }

    $dashboardResult = $db->query(
        "SELECT
            (SELECT COUNT(*) FROM students) AS students,
            (SELECT COUNT(*) FROM students WHERE status = 'Active') AS active_students,
            (SELECT COUNT(*) FROM students WHERE role = 'admin' AND status = 'Active') AS admins,
            (SELECT COUNT(*) FROM events) AS events,
            (SELECT COUNT(*) FROM registrations) AS registrations"
    );
    $dashboardStats = array_map('intval', $dashboardResult->fetch_assoc());

    $registrationResult = $db->query(
        'SELECT r.registration_id, r.registered_at, s.name AS student_name, s.student_id,
                e.title AS event_title, e.event_date
         FROM registrations r
         JOIN students s ON s.student_id = r.student_id
         JOIN events e ON e.event_id = r.event_id
         ORDER BY r.registered_at DESC, r.registration_id DESC
         LIMIT 8'
    );
    while ($registration = $registrationResult->fetch_assoc()) {
        $latestRegistrations[] = $registration;
    }

    $auditResult = $db->query(
        'SELECT actor_student_id, action, entity_type, entity_id, details, created_at
         FROM audit_logs ORDER BY created_at DESC, audit_id DESC LIMIT 10'
    );
    while ($auditLog = $auditResult->fetch_assoc()) {
        $latestAuditLogs[] = $auditLog;
    }

    $eventResult = $db->query(
        'SELECT e.event_id, e.title, e.category, e.event_date, e.start_time, e.venue, e.seat_capacity,
                e.organizer, e.badge, e.poster_filename, COUNT(r.registration_id) AS registered
         FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id
         GROUP BY e.event_id ORDER BY e.event_date, e.start_time'
    );
    while ($event = $eventResult->fetch_assoc()) {
        $events[] = $event;
    }

    $conditions = [];
    $params = [];
    $types = '';
    if ($filters['search'] !== '') {
        $conditions[] = '(student_id LIKE ? OR name LIKE ? OR email LIKE ? OR course LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
        $types .= 'ssss';
    }
    if ($filters['course'] !== '') {
        $conditions[] = 'course = ?';
        $params[] = $filters['course'];
        $types .= 's';
    }
    if ($filters['year'] !== '') {
        $conditions[] = 'year_of_study = ?';
        $params[] = $filters['year'];
        $types .= 's';
    }
    if ($filters['status'] !== '' && in_array($filters['status'], $allowedStatuses, true)) {
        $conditions[] = 'status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }

    $sql = 'SELECT student_id, name, email, mobile, course, year_of_study, status FROM students';
    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY name, student_id';
    $statement = $db->prepare($sql);
    bindMysqliParams($statement, $types, $params);
    $statement->execute();
    $result = $statement->get_result();
    while ($student = $result->fetch_assoc()) {
        $students[] = $student;
    }
    $statement->close();
    $db->close();
} catch (mysqli_sql_exception $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    error_log('StudentHub student management failed: ' . $error->getMessage());
    $flashType = 'error';
    if (in_array($currentAction, ['create_event', 'update_event', 'delete_event'], true)) {
        $flashMessage = 'The event could not be saved or loaded. Please check the event details and try again.';
    } else {
        $flashMessage = (int)$error->getCode() === 1062
            ? 'That student ID, username, or email address is already in use.'
            : 'The student record could not be saved or loaded. Please try again.';
    }
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    $flashType = 'error';
    $flashMessage = $error->getMessage();
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    error_log('StudentHub student management failed: ' . $error->getMessage());
    $flashType = 'error';
    $flashMessage = 'Student management is unavailable. Check the database setup and try again.';
}

define('STUDENTHUB_ADMIN_PAGE', true);
require __DIR__ . '/admin_template.php';
