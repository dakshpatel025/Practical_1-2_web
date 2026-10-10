<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Only POST requests are accepted.']);
}

$studentId = $_POST['student_id'] ?? '';
$category = $_POST['category'] ?? '';
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$comments = $_POST['comments'] ?? '';
if (!is_string($studentId) || !preg_match('/^[A-Za-z0-9]{5,20}$/', trim($studentId))) {
    sendJson(422, ['success' => false, 'message' => 'Enter a valid student ID.', 'errors' => ['student_id' => 'Use 5-20 letters or numbers.']]);
}
if (!is_string($category) || !in_array($category, ['Academic', 'Hostel', 'Transport', 'Canteen', 'Laboratory', 'Library', 'Portal'], true)) {
    sendJson(422, ['success' => false, 'message' => 'Select a valid feedback category.', 'errors' => ['category' => 'Select an option from the list.']]);
}
if ($rating === false || $rating < 1 || $rating > 5) {
    sendJson(422, ['success' => false, 'message' => 'Select a rating from 1 to 5.', 'errors' => ['rating' => 'Select a rating from 1 to 5.']]);
}
if (!is_string($comments) || trim($comments) === '' || mb_strlen(trim($comments)) > 500) {
    sendJson(422, ['success' => false, 'message' => 'Feedback must be between 1 and 500 characters.', 'errors' => ['comments' => 'Feedback must be between 1 and 500 characters.']]);
}

$feedback = [
    'student_id' => trim($studentId),
    'category' => $category,
    'rating' => $rating,
    'comments' => trim($comments),
    'submitted_at' => date('Y-m-d H:i:s'),
];
$jsonPath = __DIR__ . '/../../database/data/feedback_submissions.json';
$jsonFile = fopen($jsonPath, 'c+');
if (!$jsonFile || !flock($jsonFile, LOCK_EX)) {
    if ($jsonFile) {
        fclose($jsonFile);
    }
    error_log('StudentHub could not lock feedback JSON storage.');
    sendJson(500, ['success' => false, 'message' => 'Feedback could not be saved. Please try again later.']);
}

rewind($jsonFile);
$contents = stream_get_contents($jsonFile);
$records = $contents === '' ? [] : json_decode((string)$contents, true);
if (!is_array($records)) {
    flock($jsonFile, LOCK_UN);
    fclose($jsonFile);
    error_log('StudentHub feedback JSON storage is invalid.');
    sendJson(500, ['success' => false, 'message' => 'Feedback storage is unavailable.']);
}
$records[] = $feedback;
$encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$jsonOk = $encoded !== false && rewind($jsonFile) && ftruncate($jsonFile, 0) &&
    fwrite($jsonFile, $encoded) === strlen($encoded) && fflush($jsonFile);
flock($jsonFile, LOCK_UN);
fclose($jsonFile);
if (!$jsonOk) {
    error_log('StudentHub could not write feedback JSON storage.');
    sendJson(500, ['success' => false, 'message' => 'Feedback could not be saved. Please try again later.']);
}

$csvPath = __DIR__ . '/../../database/data/feedback_submissions.csv';
$csvFile = fopen($csvPath, 'a+');
if (!$csvFile || !flock($csvFile, LOCK_EX)) {
    if ($csvFile) {
        fclose($csvFile);
    }
    error_log('StudentHub could not lock feedback CSV storage.');
    sendJson(500, ['success' => false, 'message' => 'Feedback was saved, but its CSV export could not be updated.']);
}
$hasHeader = filesize($csvPath) > 0;
$csvOk = ($hasHeader || fputcsv($csvFile, ['Student ID', 'Category', 'Rating', 'Comments', 'Submitted At']) !== false);
$safeCsvValue = static function (string $value): string {
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
};
$csvOk = $csvOk && fputcsv($csvFile, [
    $safeCsvValue($feedback['student_id']),
    $safeCsvValue($feedback['category']),
    $feedback['rating'],
    $safeCsvValue($feedback['comments']),
    $feedback['submitted_at'],
]) !== false && fflush($csvFile);
flock($csvFile, LOCK_UN);
fclose($csvFile);
if (!$csvOk) {
    error_log('StudentHub could not write feedback CSV storage.');
    sendJson(500, ['success' => false, 'message' => 'Feedback was saved, but its CSV export could not be updated.']);
}

sendJson(201, ['success' => true, 'message' => 'Thank you. Your feedback has been submitted.']);
