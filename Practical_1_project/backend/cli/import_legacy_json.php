<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$dataDirectory = __DIR__ . '/../../database/data';
$students = json_decode((string)file_get_contents($dataDirectory . '/students.json'), true);
$events = json_decode((string)file_get_contents($dataDirectory . '/events.json'), true);
if (!is_array($students) || !is_array($events)) {
    fwrite(STDERR, "Could not read legacy students.json or events.json.\n");
    exit(1);
}

try {
    $db = getDatabaseConnection();
    $db->beginTransaction();
    $studentInsert = $db->prepare(
        'INSERT INTO students
            (student_id, name, email, mobile, course, year_of_study, gender,
             date_of_birth, city, password_hash, status, registered_at)
         VALUES
            (:student_id, :name, :email, :mobile, :course, :year_of_study, :gender,
             :date_of_birth, :city, :password_hash, :status, :registered_at)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name), email = VALUES(email), mobile = VALUES(mobile),
            course = VALUES(course), year_of_study = VALUES(year_of_study),
            gender = VALUES(gender), date_of_birth = VALUES(date_of_birth),
            city = VALUES(city), password_hash = VALUES(password_hash), status = VALUES(status)'
    );
    foreach ($students as $student) {
        if (!is_array($student) || empty($student['id']) || empty($student['name']) ||
            empty($student['email']) || empty($student['password'])) {
            continue;
        }
        $studentInsert->execute([
            'student_id' => $student['id'],
            'name' => $student['name'],
            'email' => $student['email'],
            'mobile' => $student['mobile'] ?? null,
            'course' => $student['course'] ?? $student['degree'] ?? 'Other',
            'year_of_study' => $student['year'] ?? '1st Year',
            'gender' => $student['gender'] ?? null,
            'date_of_birth' => ($student['dob'] ?? $student['birthDate'] ?? '') ?: null,
            'city' => $student['city'] ?? null,
            'password_hash' => password_get_info($student['password'])['algo'] !== null
                ? $student['password']
                : password_hash($student['password'], PASSWORD_DEFAULT),
            'status' => $student['status'] ?? 'Active',
            'registered_at' => $student['registered_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    $eventInsert = $db->prepare(
        'INSERT INTO events
            (event_id, title, category, event_date, start_time, venue, seat_capacity,
             organizer, description, badge)
         VALUES
            (:event_id, :title, :category, :event_date, :start_time, :venue, :seat_capacity,
             :organizer, :description, :badge)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title), category = VALUES(category), event_date = VALUES(event_date),
            start_time = VALUES(start_time), venue = VALUES(venue),
            seat_capacity = VALUES(seat_capacity), organizer = VALUES(organizer),
            description = VALUES(description), badge = VALUES(badge)'
    );
    foreach ($events as $event) {
        if (!is_array($event) || empty($event['id']) || empty($event['title'])) {
            continue;
        }
        $time = date('H:i:s', strtotime($event['time'] ?? '12:00 PM'));
        $eventInsert->execute([
            'event_id' => $event['id'],
            'title' => $event['title'],
            'category' => $event['category'] ?? 'General',
            'event_date' => $event['date'] ?? date('Y-m-d'),
            'start_time' => $time,
            'venue' => $event['venue'] ?? 'To be announced',
            'seat_capacity' => max(1, (int)($event['seats'] ?? 1)),
            'organizer' => $event['organizer'] ?? 'StudentHub',
            'description' => $event['description'] ?? '',
            'badge' => $event['badge'] ?? 'Open',
        ]);
    }

    $db->commit();
    echo 'Imported ' . count($students) . ' student records and ' . count($events) . " events.\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('StudentHub JSON import failed: ' . $error->getMessage());
    fwrite(STDERR, "Import failed; check PHP error log.\n");
    exit(1);
}
