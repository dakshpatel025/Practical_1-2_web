# StudentHub database setup

## 1. Create the database and tables

Start **Apache** and **MySQL** in XAMPP. From the project folder in PowerShell run:

```powershell
& "D:\System\XAMPP\php\php.exe" "backend\cli\setup_database.php"
```

This applies `database/schema.sql` using XAMPP's local default MySQL account (`root` with no password), and upgrades existing tables for the current student and event features. Rerun the setup script after pulling schema changes. You can also import that SQL file using phpMyAdmin for a fresh database.

The project uses this XAMPP default automatically for `localhost` so it works in a typical coursework setup. This is for local development only; do not use the MySQL root account in production.

## 2. (Recommended) Create an application database user

For better local account separation, run this in phpMyAdmin's SQL tab and replace the example password:

```sql
CREATE USER 'studenthub_app'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON studenthub.* TO 'studenthub_app'@'localhost';
```

Add these Apache environment settings (for example, to `apache/conf/extra/httpd-xampp.conf`) and restart Apache to use that account:

```apache
SetEnv STUDENTHUB_DB_HOST "localhost"
SetEnv STUDENTHUB_DB_NAME "studenthub"
SetEnv STUDENTHUB_DB_USER "studenthub_app"
SetEnv STUDENTHUB_DB_PASSWORD "choose-a-strong-password"
```

## 3. Import the current JSON records once

Run the importer from the project folder in PowerShell. Set the same credentials in that PowerShell session first:

```powershell
$env:STUDENTHUB_DB_USER = "studenthub_app"
$env:STUDENTHUB_DB_PASSWORD = "choose-a-strong-password"
$env:STUDENTHUB_DB_HOST = "localhost"
$env:STUDENTHUB_DB_NAME = "studenthub"
& "D:\System\XAMPP\php\php.exe" "backend\cli\import_legacy_json.php"
```

To use XAMPP's default root account for this one-time import, omit the first two environment-variable lines.

It imports students from `database/data/students.json` and events from `database/data/events.json`. You can run it again safely to refresh student/event details.

## Tables and relationships

- `students`: one row per account; the password column stores a password hash.
- `remember_tokens`: hashed, expiring remember-me credentials linked to student accounts.
- `student_profiles`: authenticated profile, family, and hostel form data stored as JSON for each student.
- `events`: one row per event; capacity is stored once, and current registrations are counted from the registration table.
- `registrations`: joins students to events and stores event choices plus the student's department and study year at booking time.
- Primary/foreign keys keep references valid; `(event_id, student_id)` is unique so a student cannot register twice for one event.

This design avoids copying student/event names or seat counts into every registration. Registration inserts accounts using MySQLi prepared statements, checks unique email and username, and stores only password hashes. Login verifies passwords with `password_verify()`, regenerates the session ID, and routes by the account's `role` (`student` by default; set approved staff accounts to `admin` in the database). Student profile access is guarded server-side, and sessions expire after 5 minutes of inactivity, requiring the user to log in again. The optional remember-me token is stored hashed and expires after 30 days; logout revokes the current token. Profile, family (including parent details and compressed photos), and hostel updates are saved in `student_profiles` and reloaded on the next visit. Apply the updated schema by rerunning the setup script before using these profile features.

Student feedback remains a separate JSON/CSV file-based submission flow in `database/data/`.

Administrators can manage student records at `backend/admin.php` after signing in with an active admin account. The page uses prepared MySQLi statements against the existing `students` table for adding, viewing, editing, deleting, searching, and filtering records; adding an account stores only a password hash.

The administrator dashboard displays live student, event, and registration totals with the latest registrations. It is restricted to active admin accounts, and the navigation only shows the Admin link to accounts whose stored role is admin. Student and event create, update, and delete operations are recorded in `audit_logs`; audit inserts share a transaction with the related database change. Rerun the database setup script to create this table in an existing database.

The same admin page supports event creation, editing, listing, and deletion. Its event form uses `backend/api/admin_events.php` with Fetch so event changes appear without reloading the page; the endpoint returns JSON and requires an active administrator session and CSRF token. Optional event posters accept JPEG, PNG, GIF, or WebP images up to 10 MB; uploads are checked by their image content and stored with generated filenames under `frontend/uploads/event-posters/`. Rerun the database setup script to add the poster filename column to an existing database.

Apache blocks direct web access to the legacy student JSON and CSV exports in `database/data`; the PHP application accesses them locally for the existing export behavior.
