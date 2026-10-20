<?php
declare(strict_types=1);

function saveEventPoster(): ?string
{
    if (!isset($_FILES['poster']) || !is_array($_FILES['poster'])) {
        return null;
    }

    $upload = $_FILES['poster'];
    $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if (!is_int($error)) {
        throw new RuntimeException('The event poster upload was not valid.');
    }
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Event posters cannot exceed 10 MB. Check the PHP upload size settings if this file is smaller.');
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The event poster could not be uploaded. Please choose the file again.');
    }

    $temporaryPath = $upload['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The event poster upload was not valid.');
    }
    $actualSize = filesize($temporaryPath);
    if ($actualSize === false || $actualSize < 1 || $actualSize > 10 * 1024 * 1024) {
        throw new RuntimeException('Event posters must be smaller than or equal to 10 MB.');
    }

    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    $imageInfo = getimagesize($temporaryPath);
    if (!is_string($mimeType) || !isset($mimeTypes[$mimeType]) ||
        $imageInfo === false || ($imageInfo['mime'] ?? '') !== $mimeType) {
        throw new RuntimeException('Choose a valid JPEG, PNG, GIF, or WebP image for the event poster.');
    }

    $directory = __DIR__ . '/../frontend/uploads/event-posters';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The event poster folder could not be created. Check the server folder permissions.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $mimeTypes[$mimeType];
    if (!move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The event poster could not be saved. Check the server folder permissions.');
    }
    return $filename;
}

function removeEventPoster(?string $filename): void
{
    if ($filename === null || !preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|webp)$/D', $filename)) {
        return;
    }

    $path = __DIR__ . '/../frontend/uploads/event-posters/' . $filename;
    if (is_file($path) && !unlink($path)) {
        error_log('StudentHub could not remove event poster: ' . $filename);
    }
}
