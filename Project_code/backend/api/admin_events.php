<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin_audit.php';
require_once __DIR__ . '/../event_posters.php';

header('Cache-Control: no-store, private');
startStudentSession();

$studentId = $_SESSION['student_id'] ?? null;
if (!is_string($studentId) || $studentId === '') {
    sendJson(401, ['success' => false, 'message' => 'Sign in with an administrator account to manage events.']);
}

try {
    $db = getMysqliConnection();
    $roleQuery = $db->prepare('SELECT role, status FROM students WHERE student_id = ? LIMIT 1');
    $roleQuery->bind_param('s', $studentId);
    $roleQuery->execute();
    $account = $roleQuery->get_result()->fetch_assoc();
    $roleQuery->close();
    if (!$account || $account['role'] !== 'admin' || $account['status'] !== 'Active') {
        $db->close();
        sendJson(403, ['success' => false, 'message' => 'Administrator access is required to manage events.']);
    }
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    error_log('StudentHub event API authorization failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => 'Could not verify administrator access. Please try again.']);
}

function eventApiString(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    if (!is_string($value)) {
        sendJson(422, ['success' => false, 'message' => 'Invalid event form data was submitted.']);
    }
    return trim($value);
}

function eventApiRecord(mysqli $db, int $eventId): ?array
{
    $statement = $db->prepare(
        'SELECT e.event_id AS id, e.title, e.category,
                DATE_FORMAT(e.event_date, \'%Y-%m-%d\') AS date,
                DATE_FORMAT(e.start_time, \'%h:%i %p\') AS time,
                DATE_FORMAT(e.start_time, \'%H:%i\') AS start_time,
                e.venue, e.seat_capacity AS seats, COUNT(r.registration_id) AS registered,
                e.organizer, e.description, e.badge, e.poster_filename
         FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id
         WHERE e.event_id = ? GROUP BY e.event_id'
    );
    $statement->bind_param('i', $eventId);
    $statement->execute();
    $event = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();
    if ($event !== null) {
        $event['id'] = (int)$event['id'];
        $event['seats'] = (int)$event['seats'];
        $event['registered'] = (int)$event['registered'];
        $event['poster_url'] = $event['poster_filename'] === null
            ? null
            : '../../frontend/uploads/event-posters/' . rawurlencode($event['poster_filename']);
        unset($event['poster_filename']);
    }
    return $event;
}

$pendingPosterFilename = null;
$transactionStarted = false;
$db->close();

try {
    $db = getMysqliConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($eventId !== false && $eventId !== null) {
            if ($eventId < 1) {
                sendJson(400, ['success' => false, 'message' => 'A valid event ID is required.']);
            }
            $event = eventApiRecord($db, $eventId);
            $db->close();
            if ($event === null) {
                sendJson(404, ['success' => false, 'message' => 'Event not found.']);
            }
            sendJson(200, ['success' => true, 'event' => $event]);
        }

        $result = $db->query(
            "SELECT e.event_id AS id, e.title, e.category,
                    DATE_FORMAT(e.event_date, '%Y-%m-%d') AS date,
                    DATE_FORMAT(e.start_time, '%h:%i %p') AS time,
                    e.venue, e.seat_capacity AS seats, COUNT(r.registration_id) AS registered,
                    e.organizer, e.description, e.badge, e.poster_filename
             FROM events e LEFT JOIN registrations r ON r.event_id = e.event_id
             GROUP BY e.event_id ORDER BY e.event_date, e.start_time"
        );
        $events = [];
        while ($event = $result->fetch_assoc()) {
            $event['id'] = (int)$event['id'];
            $event['seats'] = (int)$event['seats'];
            $event['registered'] = (int)$event['registered'];
            $event['poster_url'] = $event['poster_filename'] === null
                ? null
                : '../../frontend/uploads/event-posters/' . rawurlencode($event['poster_filename']);
            unset($event['poster_filename']);
            $events[] = $event;
        }
        $db->close();
        sendJson(200, ['success' => true, 'events' => $events]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        $db->close();
        sendJson(405, ['success' => false, 'message' => 'Only GET and POST requests are accepted.']);
    }

    $submittedToken = eventApiString('csrf_token');
    $sessionToken = $_SESSION['admin_csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        $db->close();
        sendJson(403, ['success' => false, 'message' => 'Your session could not be verified. Reload the page and try again.']);
    }

    $action = eventApiString('action');
    $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'delete_event') {
        if ($eventId === false || $eventId === null || $eventId < 1) {
            $db->close();
            sendJson(422, ['success' => false, 'message' => 'Select a valid event to delete.']);
        }
        $lookup = $db->prepare('SELECT title, poster_filename FROM events WHERE event_id = ?');
        $lookup->bind_param('i', $eventId);
        $lookup->execute();
        $event = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if ($event === null) {
            $db->close();
            sendJson(404, ['success' => false, 'message' => 'Event not found; nothing was deleted.']);
        }
        $db->begin_transaction();
        $transactionStarted = true;
        $delete = $db->prepare('DELETE FROM events WHERE event_id = ?');
        $delete->bind_param('i', $eventId);
        $delete->execute();
        $delete->close();
        recordAdminAudit(
            $db,
            $studentId,
            'delete',
            'event',
            (string)$eventId,
            ['title' => $event['title']]
        );
        $db->commit();
        $transactionStarted = false;
        $db->close();
        removeEventPoster($event['poster_filename']);
        sendJson(200, ['success' => true, 'event_id' => $eventId, 'message' => 'Event deleted successfully.']);
    }

    if (!in_array($action, ['create_event', 'update_event'], true)) {
        $db->close();
        sendJson(422, ['success' => false, 'message' => 'Choose a valid event action.']);
    }
    if ($action === 'update_event' && ($eventId === false || $eventId === null || $eventId < 1)) {
        $db->close();
        sendJson(422, ['success' => false, 'message' => 'Select a valid event to update.']);
    }

    $title = eventApiString('event_title');
    $category = eventApiString('event_category');
    $eventDate = eventApiString('event_date');
    $startTime = eventApiString('start_time');
    $venue = eventApiString('venue');
    $capacity = filter_var($_POST['seat_capacity'] ?? null, FILTER_VALIDATE_INT);
    $organizer = eventApiString('organizer');
    $description = eventApiString('description');
    $badge = eventApiString('badge', 'Open');
    $removePoster = eventApiString('remove_poster') === '1';

    if ($title === '' || mb_strlen($title, 'UTF-8') > 180 ||
        $category === '' || mb_strlen($category, 'UTF-8') > 60 ||
        $venue === '' || mb_strlen($venue, 'UTF-8') > 180 ||
        $capacity === false || $capacity < 1 || $capacity > 4294967295 ||
        $organizer === '' || mb_strlen($organizer, 'UTF-8') > 180 ||
        $description === '' || strlen($description) > 65535 ||
        $badge === '' || mb_strlen($badge, 'UTF-8') > 40) {
        $db->close();
        sendJson(422, ['success' => false, 'message' => 'Complete the required event fields with valid values.']);
    }
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $eventDate);
    $dateErrors = DateTime::getLastErrors();
    if ($parsedDate === false ||
        ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
        $parsedDate->format('Y-m-d') !== $eventDate) {
        $db->close();
        sendJson(422, ['success' => false, 'message' => 'Enter a valid event date.']);
    }
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $startTime)) {
        $db->close();
        sendJson(422, ['success' => false, 'message' => 'Enter a valid event start time.']);
    }

    $oldPosterFilename = null;
    if ($action === 'update_event') {
        $lookup = $db->prepare('SELECT poster_filename FROM events WHERE event_id = ?');
        $lookup->bind_param('i', $eventId);
        $lookup->execute();
        $existingEvent = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if ($existingEvent === null) {
            $db->close();
            sendJson(404, ['success' => false, 'message' => 'Event not found; no changes were saved.']);
        }
        $oldPosterFilename = $existingEvent['poster_filename'];
        $count = $db->prepare('SELECT COUNT(*) AS registered FROM registrations WHERE event_id = ?');
        $count->bind_param('i', $eventId);
        $count->execute();
        $registered = (int)$count->get_result()->fetch_assoc()['registered'];
        $count->close();
        if ($capacity < $registered) {
            $db->close();
            sendJson(422, ['success' => false, 'message' => "Seat capacity cannot be lower than the {$registered} existing registration(s)."]);
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
    }

    recordAdminAudit(
        $db,
        $studentId,
        $action === 'create_event' ? 'create' : 'update',
        'event',
        (string)$eventId,
        [
            'title' => $title,
            'poster_uploaded' => $uploadedPosterFilename !== null,
            'poster_removed' => $removePoster && $uploadedPosterFilename === null,
        ]
    );
    $event = eventApiRecord($db, (int)$eventId);
    if ($event === null) {
        throw new RuntimeException('The event record could not be reloaded after saving.');
    }
    $db->commit();
    $transactionStarted = false;
    $db->close();
    $pendingPosterFilename = null;
    if ($uploadedPosterFilename !== null || $removePoster) {
        removeEventPoster($oldPosterFilename);
    }
    sendJson($action === 'create_event' ? 201 : 200, [
        'success' => true,
        'message' => $action === 'create_event' ? 'Event added successfully.' : 'Event updated successfully.',
        'event' => $event,
    ]);
} catch (DatabaseConnectionException $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    error_log('StudentHub event API database connection failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (mysqli_sql_exception $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    error_log('StudentHub event API operation failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'The event could not be saved. Please try again.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    sendJson(422, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli && $transactionStarted) {
        $db->rollback();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    removeEventPoster($pendingPosterFilename);
    error_log('StudentHub event API operation failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'The event service is unavailable. Please try again.']);
}
