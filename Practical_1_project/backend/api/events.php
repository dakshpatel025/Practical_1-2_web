<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(405, ['success' => false, 'message' => 'Only GET requests are accepted.']);
}

try {
    $db = getDatabaseConnection();
    $query = $db->query(
        "SELECT e.event_id AS id, e.title, e.category,
                DATE_FORMAT(e.event_date, '%Y-%m-%d') AS date,
                DATE_FORMAT(e.start_time, '%h:%i %p') AS time,
                e.venue, e.seat_capacity AS seats, COUNT(r.registration_id) AS registered,
                e.organizer, e.description, e.badge
         FROM events e
         LEFT JOIN registrations r ON r.event_id = e.event_id
         GROUP BY e.event_id
         ORDER BY e.event_date, e.start_time"
    );
    $events = array_map(static function (array $event): array {
        $event['id'] = (int)$event['id'];
        $event['seats'] = (int)$event['seats'];
        $event['registered'] = (int)$event['registered'];
        return $event;
    }, $query->fetchAll());
    sendJson(200, ['success' => true, 'events' => $events]);
} catch (DatabaseConnectionException $error) {
    error_log('StudentHub event list failed: ' . $error->getMessage());
    sendJson(503, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('StudentHub event list failed: ' . $error->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Could not load events from the database.']);
}
