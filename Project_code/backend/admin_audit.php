<?php
declare(strict_types=1);

function recordAdminAudit(
    mysqli $db,
    string $actorStudentId,
    string $action,
    string $entityType,
    string $entityId,
    array $details = []
): void {
    $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($detailsJson === false) {
        throw new RuntimeException('Could not encode the audit log details.');
    }

    $statement = $db->prepare(
        'INSERT INTO audit_logs (actor_student_id, action, entity_type, entity_id, details)
         VALUES (?, ?, ?, ?, ?)'
    );
    $statement->bind_param('sssss', $actorStudentId, $action, $entityType, $entityId, $detailsJson);
    $statement->execute();
    $statement->close();
}
