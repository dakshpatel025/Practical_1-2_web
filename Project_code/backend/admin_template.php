<?php
if (!defined('STUDENTHUB_ADMIN_PAGE')) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage StudentHub student records and campus events.">
    <base href="../frontend/html/">
    <title>Management | StudentHub Portal</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <a class="skip-link" href="#main">Skip to Content</a>
    <header id="text">
        <a class="brand" href="../index.html">
            <img src="../image/charusat_logo.png" alt="CHARUSAT logo">
            <span>Student Hub Portal</span>
        </a>
    </header>
    <div id="space"></div>
    <div id="nav"><nav aria-label="Main navigation">
        <a href="../index.html" class="input">Home</a>
        <a href="../../backend/main_page.php" class="input">Profile</a>
        <a href="events.html" class="input">Events</a>
        <a href="faq.html" class="input">FAQ</a>
        <a href="feedback.html" class="input">Feedback</a>
    </nav></div>
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../index.html">Home</a> / <span aria-current="page">Student Management</span>
    </nav>
    <main id="main" class="admin-page">
        <div class="admin-page-heading">
            <h1 id="title">Administration</h1>
            <a class="admin-secondary-button" href="../../backend/api/logout_process.php">Sign Out</a>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <p id="adminMessage" class="admin-message <?= $flashType === 'error' ? 'admin-message-error' : 'admin-message-success' ?>"
               role="<?= $flashType === 'error' ? 'alert' : 'status' ?>"><?= escapeHtml($flashMessage) ?></p>
        <?php else: ?>
            <p id="adminMessage" class="admin-message" role="status" aria-live="polite" hidden></p>
        <?php endif; ?>

        <section class="admin-panel" aria-labelledby="dashboard-title">
            <div class="admin-panel-heading">
                <h2 id="dashboard-title">Dashboard overview</h2>
            </div>
            <div class="dashboard-stat-grid">
                <article class="dashboard-stat">
                    <span>Total students</span>
                    <strong><?= $dashboardStats['students'] ?></strong>
                </article>
                <article class="dashboard-stat">
                    <span>Active students</span>
                    <strong><?= $dashboardStats['active_students'] ?></strong>
                </article>
                <article class="dashboard-stat">
                    <span>Active administrators</span>
                    <strong><?= $dashboardStats['admins'] ?></strong>
                </article>
                <article class="dashboard-stat">
                    <span>Events</span>
                    <strong><?= $dashboardStats['events'] ?></strong>
                </article>
                <article class="dashboard-stat">
                    <span>Registrations</span>
                    <strong><?= $dashboardStats['registrations'] ?></strong>
                </article>
            </div>
        </section>

        <section class="admin-panel" aria-labelledby="latest-registrations-title">
            <div class="admin-panel-heading">
                <h2 id="latest-registrations-title">Latest registrations</h2>
            </div>
            <div class="student-table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Event</th>
                            <th scope="col">Event date</th>
                            <th scope="col">Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($latestRegistrations === []): ?>
                            <tr><td colspan="4" class="empty-students">There are no registrations yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latestRegistrations as $registration): ?>
                                <tr>
                                    <td><?= escapeHtml((string)$registration['student_name']) ?>
                                        <span class="field-hint">(<?= escapeHtml((string)$registration['student_id']) ?>)</span>
                                    </td>
                                    <td><?= escapeHtml((string)$registration['event_title']) ?></td>
                                    <td><?= escapeHtml((string)$registration['event_date']) ?></td>
                                    <td><?= escapeHtml((string)$registration['registered_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel" aria-labelledby="audit-log-title">
            <div class="admin-panel-heading">
                <h2 id="audit-log-title">Recent administrator actions</h2>
            </div>
            <div class="student-table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Administrator</th>
                            <th scope="col">Action</th>
                            <th scope="col">Record</th>
                            <th scope="col">Details</th>
                            <th scope="col">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($latestAuditLogs === []): ?>
                            <tr><td colspan="5" class="empty-students">No administrator actions have been recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latestAuditLogs as $auditLog): ?>
                                <tr>
                                    <td><?= escapeHtml((string)$auditLog['actor_student_id']) ?></td>
                                    <td><?= escapeHtml(ucfirst((string)$auditLog['action'])) ?></td>
                                    <td><?= escapeHtml(ucfirst((string)$auditLog['entity_type'])) ?>
                                        #<?= escapeHtml((string)$auditLog['entity_id']) ?></td>
                                    <td><?= escapeHtml((string)$auditLog['details']) ?></td>
                                    <td><?= escapeHtml((string)$auditLog['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel" aria-labelledby="student-form-title">
            <div class="admin-panel-heading">
                <h2 id="student-form-title"><?= $editStudent === null ? 'Add a student' : 'Edit student' ?></h2>
                <?php if ($editStudent !== null): ?>
                    <a class="admin-secondary-button" href="../../backend/admin.php">Cancel edit</a>
                <?php endif; ?>
            </div>
            <form method="post" action="../../backend/admin.php" class="student-form">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= $editStudent === null ? 'create' : 'update' ?>">
                <div class="student-form-grid">
                    <label>
                        Student ID
                        <input type="text" name="student_id" maxlength="20" required
                               value="<?= escapeHtml((string)($editStudent['student_id'] ?? '')) ?>"
                               <?= $editStudent !== null ? 'readonly' : '' ?>>
                    </label>
                    <label>
                        Full name
                        <input type="text" name="name" maxlength="100" required
                               value="<?= escapeHtml((string)($editStudent['name'] ?? '')) ?>">
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" maxlength="254" required
                               value="<?= escapeHtml((string)($editStudent['email'] ?? '')) ?>">
                    </label>
                    <label>
                        Mobile
                        <input type="tel" name="mobile" maxlength="20"
                               value="<?= escapeHtml((string)($editStudent['mobile'] ?? '')) ?>">
                    </label>
                    <label>
                        Course
                        <input type="text" name="course" maxlength="100" required
                               value="<?= escapeHtml((string)($editStudent['course'] ?? '')) ?>">
                    </label>
                    <label>
                        Year of study
                        <input type="text" name="year_of_study" maxlength="20" required
                               value="<?= escapeHtml((string)($editStudent['year_of_study'] ?? '')) ?>">
                    </label>
                    <label>
                        Gender
                        <input type="text" name="gender" maxlength="20"
                               value="<?= escapeHtml((string)($editStudent['gender'] ?? '')) ?>">
                    </label>
                    <label>
                        Date of birth
                        <input type="date" name="date_of_birth"
                               value="<?= escapeHtml((string)($editStudent['date_of_birth'] ?? '')) ?>">
                    </label>
                    <label>
                        City
                        <input type="text" name="city" maxlength="80"
                               value="<?= escapeHtml((string)($editStudent['city'] ?? '')) ?>">
                    </label>
                    <label>
                        Status
                        <select name="status" required>
                            <?php foreach (['Active', 'Inactive'] as $statusOption): ?>
                                <option value="<?= escapeHtml($statusOption) ?>"
                                    <?= ($editStudent['status'] ?? 'Active') === $statusOption ? 'selected' : '' ?>>
                                    <?= escapeHtml($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <?= $editStudent === null ? 'Initial password' : 'New password (optional)' ?>
                        <input type="password" name="password" minlength="8" autocomplete="new-password"
                               <?= $editStudent === null ? 'required' : '' ?>>
                        <span class="field-hint">
                            <?= $editStudent === null ? 'At least 8 characters; only a secure hash is stored.' : 'Leave blank to keep the current password.' ?>
                        </span>
                    </label>
                </div>
                <button type="submit" class="admin-primary-button">
                    <?= $editStudent === null ? 'Add student' : 'Save changes' ?>
                </button>
            </form>
        </section>

        <section class="admin-panel" aria-labelledby="student-list-title">
            <div class="admin-panel-heading">
                <h2 id="student-list-title">Student records</h2>
                <span><?= count($students) ?> record<?= count($students) === 1 ? '' : 's' ?></span>
            </div>
            <form method="get" action="../../backend/admin.php" class="student-filters">
                <label>
                    Search
                    <input type="search" name="search" value="<?= escapeHtml($filters['search']) ?>"
                           placeholder="ID, name, email or course">
                </label>
                <label>
                    Course filter
                    <input type="text" name="course" value="<?= escapeHtml($filters['course']) ?>"
                           placeholder="Exact course">
                </label>
                <label>
                    Year filter
                    <input type="text" name="year" value="<?= escapeHtml($filters['year']) ?>"
                           placeholder="Exact year">
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['Active', 'Inactive'] as $statusOption): ?>
                            <option value="<?= escapeHtml($statusOption) ?>"
                                <?= $filters['status'] === $statusOption ? 'selected' : '' ?>>
                                <?= escapeHtml($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button type="submit" class="admin-primary-button">Search and filter</button>
                    <a class="admin-secondary-button" href="../../backend/admin.php">Clear</a>
                </div>
            </form>
            <div class="student-table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Student ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Mobile</th>
                            <th scope="col">Course</th>
                            <th scope="col">Year</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students === []): ?>
                            <tr><td colspan="8" class="empty-students">No student records match your search.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= escapeHtml((string)$student['student_id']) ?></td>
                                    <td><?= escapeHtml((string)$student['name']) ?></td>
                                    <td><?= escapeHtml((string)$student['email']) ?></td>
                                    <td><?= escapeHtml((string)($student['mobile'] ?? '')) ?></td>
                                    <td><?= escapeHtml((string)$student['course']) ?></td>
                                    <td><?= escapeHtml((string)$student['year_of_study']) ?></td>
                                    <td><span class="status-badge"><?= escapeHtml((string)$student['status']) ?></span></td>
                                    <td class="student-actions">
                                        <a class="admin-secondary-button"
                                           href="../../backend/admin.php?edit=<?= rawurlencode((string)$student['student_id']) ?>">Edit</a>
                                        <form method="post" action="../../backend/admin.php"
                                              onsubmit="return confirm('Delete this student record?');">
                                            <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="student_id"
                                                   value="<?= escapeHtml((string)$student['student_id']) ?>">
                                            <button type="submit" class="admin-danger-button">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel" id="events" aria-labelledby="event-form-title">
            <div class="admin-panel-heading">
                <h2 id="event-form-title"><?= $editEvent === null ? 'Add an event' : 'Edit event' ?></h2>
                <?php if ($editEvent !== null): ?>
                    <a class="admin-secondary-button" href="../../backend/admin.php#events">Cancel edit</a>
                <?php endif; ?>
            </div>
            <form id="eventForm" method="post" action="../../backend/api/admin_events.php" enctype="multipart/form-data" class="event-form">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                <input id="eventAction" type="hidden" name="action" value="<?= $editEvent === null ? 'create_event' : 'update_event' ?>">
                <input id="eventId" type="hidden" name="event_id" value="<?= (int)($editEvent['event_id'] ?? 0) ?>">
                <div class="student-form-grid">
                    <label>
                        Event title
                        <input type="text" name="event_title" maxlength="180" required
                               value="<?= escapeHtml((string)($editEvent['title'] ?? '')) ?>">
                    </label>
                    <label>
                        Category
                        <input type="text" name="event_category" maxlength="60" required
                               value="<?= escapeHtml((string)($editEvent['category'] ?? '')) ?>">
                    </label>
                    <label>
                        Event date
                        <input type="date" name="event_date" required
                               value="<?= escapeHtml((string)($editEvent['event_date'] ?? '')) ?>">
                    </label>
                    <label>
                        Start time
                        <input type="time" name="start_time" required
                               value="<?= escapeHtml((string)($editEvent['start_time'] ?? '')) ?>">
                    </label>
                    <label>
                        Venue
                        <input type="text" name="venue" maxlength="180" required
                               value="<?= escapeHtml((string)($editEvent['venue'] ?? '')) ?>">
                    </label>
                    <label>
                        Seat capacity
                        <input type="number" name="seat_capacity" min="1" max="4294967295" required
                               value="<?= escapeHtml((string)($editEvent['seat_capacity'] ?? '')) ?>">
                    </label>
                    <label>
                        Organizer
                        <input type="text" name="organizer" maxlength="180" required
                               value="<?= escapeHtml((string)($editEvent['organizer'] ?? '')) ?>">
                    </label>
                    <label>
                        Event status
                        <input type="text" name="badge" maxlength="40" required
                               value="<?= escapeHtml((string)($editEvent['badge'] ?? 'Open')) ?>">
                    </label>
                    <label class="event-description-field">
                        Description
                        <textarea name="description" rows="4" maxlength="65535" required><?= escapeHtml((string)($editEvent['description'] ?? '')) ?></textarea>
                    </label>
                    <label>
                        Poster (optional)
                        <input type="file" name="poster" accept="image/jpeg,image/png,image/gif,image/webp">
                        <span class="field-hint">JPEG, PNG, GIF, or WebP; maximum 10 MB.</span>
                    </label>
                    <div id="currentEventPosterArea" class="current-event-poster">
                    <?php if ($editEvent !== null && $editEvent['poster_filename'] !== null): ?>
                            <img src="../../frontend/uploads/event-posters/<?= rawurlencode((string)$editEvent['poster_filename']) ?>"
                                 alt="Current poster for <?= escapeHtml((string)$editEvent['title']) ?>">
                            <label class="poster-remove-option">
                                <input type="checkbox" name="remove_poster" value="1">
                                Remove current poster
                            </label>
                    <?php endif; ?>
                    </div>
                </div>
                <button id="eventSubmitButton" type="submit" class="admin-primary-button">
                    <?= $editEvent === null ? 'Add event' : 'Save event changes' ?>
                </button>
                <button id="cancelEventEdit" type="button" class="admin-secondary-button" hidden>Cancel edit</button>
            </form>
        </section>

        <section class="admin-panel" aria-labelledby="event-list-title">
            <div class="admin-panel-heading">
                <h2 id="event-list-title">Events</h2>
                <span id="eventCount"><?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?></span>
            </div>
            <div class="student-table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Poster</th>
                            <th scope="col">Event</th>
                            <th scope="col">Date &amp; time</th>
                            <th scope="col">Venue</th>
                            <th scope="col">Seats</th>
                            <th scope="col">Organizer</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="eventTableBody">
                        <?php if ($events === []): ?>
                            <tr><td colspan="8" class="empty-students">No events have been added.</td></tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <?php if ($event['poster_filename'] !== null): ?>
                                            <img class="event-poster-thumbnail"
                                                 src="../../frontend/uploads/event-posters/<?= rawurlencode((string)$event['poster_filename']) ?>"
                                                 alt="Poster for <?= escapeHtml((string)$event['title']) ?>">
                                        <?php else: ?>
                                            <span>No poster</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= escapeHtml((string)$event['title']) ?><br>
                                        <span class="field-hint"><?= escapeHtml((string)$event['category']) ?></span>
                                    </td>
                                    <td><?= escapeHtml((string)$event['event_date']) ?><br>
                                        <?= escapeHtml(substr((string)$event['start_time'], 0, 5)) ?>
                                    </td>
                                    <td><?= escapeHtml((string)$event['venue']) ?></td>
                                    <td><?= (int)$event['registered'] ?> / <?= (int)$event['seat_capacity'] ?></td>
                                    <td><?= escapeHtml((string)$event['organizer']) ?></td>
                                    <td><span class="status-badge"><?= escapeHtml((string)$event['badge']) ?></span></td>
                                    <td class="student-actions">
                                        <a class="admin-secondary-button event-edit-button"
                                           href="../../backend/admin.php?edit_event=<?= (int)$event['event_id'] ?>#events"
                                           data-event-id="<?= (int)$event['event_id'] ?>">Edit</a>
                                        <form class="event-delete-form" method="post" action="../../backend/api/admin_events.php">
                                            <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>">
                                            <button type="submit" class="admin-danger-button">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <footer class="fot">
        <p>CHARUSAT University &copy; 2018 - 2026. All Rights Reserved. | <a href="contact.html">Contact</a></p>
    </footer>
    <script src="../js/theme_nav.js"></script>
    <script src="../js/admin_events.js"></script>
</body>
</html>
