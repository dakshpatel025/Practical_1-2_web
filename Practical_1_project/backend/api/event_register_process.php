<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

if (($_POST['action'] ?? '') === 'delete') {
    $registrationId = $_POST['reg_id'] ?? '';
    if (!is_string($registrationId) || $registrationId === '') {
        sendJson(400, ['success' => false, 'message' => 'A valid registration ID is required.']);
    }
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $find = $db->prepare('SELECT event_id FROM registrations WHERE registration_id = :id FOR UPDATE');
        $find->execute(['id' => $registrationId]);
        $registration = $find->fetch();
        if (!$registration) {
            $db->rollBack();
            sendJson(404, ['success' => false, 'message' => 'Registration not found.']);
        }
        $delete = $db->prepare('DELETE FROM registrations WHERE registration_id = :id');
        $delete->execute(['id' => $registrationId]);
        $remaining = $db->prepare(
            'SELECT e.seat_capacity - COUNT(r.registration_id) AS seats_remaining
             FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id
             WHERE e.event_id = :event_id GROUP BY e.event_id'
        );
        $remaining->execute(['event_id' => $registration['event_id']]);
        $seatsRemaining = (int)$remaining->fetchColumn();
        $db->commit();
        sendJson(200, [
            'success' => true,
            'cancelled' => true,
            'reg_id' => $registrationId,
            'message' => 'Registration deleted. The available seat count has been restored.',
            'seats_remaining' => $seatsRemaining,
        ]);
    } catch (Throwable $error) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log('StudentHub registration cancellation failed: ' . $error->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Could not cancel the registration.']);
    }
}

function postValue(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$name = postValue('participant_name');
$email = strtolower(postValue('participant_email'));
$mobile = postValue('participant_mobile');
$studentId = postValue('student_id');
$eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
$department = postValue('department');
$year = postValue('year_of_study');
$shirtSize = postValue('t_shirt_size') ?: 'NA';
$note = postValue('special_note');
$errors = [];

if (!preg_match('/^[A-Za-z\s]{3,60}$/', $name)) {
    $errors['participant_name'] = 'Full name must be 3-60 letters only.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['participant_email'] = 'Enter a valid email address.';
}
if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    $errors['participant_mobile'] = 'Enter a valid 10-digit Indian mobile number.';
}
if (!preg_match('/^[A-Za-z0-9]{5,20}$/', $studentId)) {
    $errors['student_id'] = 'Enter a valid Student ID.';
}
if (!$eventId || $eventId < 1) {
    $errors['event_id'] = 'Select a valid event.';
}
if (!in_array($department, ['CSE', 'IT', 'EC', 'ME', 'CE', 'BCA', 'BBA', 'BSc.IT', 'Other'], true)) {
    $errors['department'] = 'Select a valid department.';
}
if (!in_array($year, ['1st Year', '2nd Year', '3rd Year', '4th Year', 'PG 1st Year', 'PG 2nd Year'], true)) {
    $errors['year_of_study'] = 'Select a valid year of study.';
}
if (!in_array($shirtSize, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'NA'], true)) {
    $errors['t_shirt_size'] = 'Select a valid T-shirt size.';
}
if (mb_strlen($note) > 300) {
    $errors['special_note'] = 'Special note cannot exceed 300 characters.';
}
if (!isset($_POST['terms'])) {
    $errors['terms'] = 'You must accept the event participation terms.';
}
if ($errors) {
    sendJson(422, ['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors]);
}

try {
    $db = getDatabaseConnection();
    $db->beginTransaction();
    $eventQuery = $db->prepare('SELECT title, seat_capacity FROM events WHERE event_id = :event_id FOR UPDATE');
    $eventQuery->execute(['event_id' => $eventId]);
    $event = $eventQuery->fetch();
    if (!$event) {
        $db->rollBack();
        sendJson(404, ['success' => false, 'message' => 'Event not found.']);
    }

    $studentQuery = $db->prepare(
        'SELECT name, email, mobile FROM students WHERE student_id = :student_id AND status = :status'
    );
    $studentQuery->execute(['student_id' => $studentId, 'status' => 'Active']);
    $student = $studentQuery->fetch();
    if (!$student || strcasecmp($student['name'], $name) !== 0 ||
        strcasecmp($student['email'], $email) !== 0 || $student['mobile'] !== $mobile) {
        $db->rollBack();
        sendJson(422, [
            'success' => false,
            'message' => 'Student ID and participant details must match an active student account.',
            'errors' => ['student_id' => 'Check the Student ID, name, email and mobile number.'],
        ]);
    }

    $count = $db->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = :event_id');
    $count->execute(['event_id' => $eventId]);
    $registered = (int)$count->fetchColumn();
    if ($registered >= (int)$event['seat_capacity']) {
        $db->rollBack();
        sendJson(409, ['success' => false, 'message' => 'This event is fully booked.']);
    }

    $registrationId = 'EVR' . strtoupper(bin2hex(random_bytes(8)));
    $insert = $db->prepare(
        'INSERT INTO registrations
            (registration_id, event_id, student_id, department, year_of_study, t_shirt_size, special_note)
         VALUES
            (:registration_id, :event_id, :student_id, :department, :year_of_study, :t_shirt_size, :special_note)'
    );
    $insert->execute([
        'registration_id' => $registrationId,
        'event_id' => $eventId,
        'student_id' => $studentId,
        'department' => $department,
        'year_of_study' => $year,
        't_shirt_size' => $shirtSize,
        'special_note' => $note !== '' ? $note : null,
    ]);
    $db->commit();

    $remaining = (int)$event['seat_capacity'] - $registered - 1;
    sendJson(201, [
        'success' => true,
        'message' => "Event registration successful! You are registered for \"{$event['title']}\".",
        'reg_id' => $registrationId,
        'participant_name' => $name,
        'event_title' => $event['title'],
        'seats_remaining' => $remaining,
    ]);
} catch (DatabaseConnectionException $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('StudentHub event registration failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (PDOException $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('StudentHub event registration failed: ' . $error->getMessage());
    if ($error->getCode() === '23000') {
        sendJson(409, [
            'success' => false,
            'message' => 'This student is already registered for this event.',
            'errors' => ['student_id' => 'Already registered for this event.'],
        ]);
    }
    sendJson(500, ['success' => false, 'message' => 'Could not save the event registration.']);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('StudentHub event registration failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Could not save the event registration.']);
}
