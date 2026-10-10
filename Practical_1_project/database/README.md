# StudentHub database setup

## 1. Create the database and tables

Start **Apache** and **MySQL** in XAMPP. From the project folder in PowerShell run:

```powershell
& "D:\System\XAMPP\php\php.exe" "backend\cli\setup_database.php"
```

This applies `database/schema.sql` using XAMPP's local default MySQL account (`root` with no password). You can also import that SQL file using phpMyAdmin.

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
- `events`: one row per event; capacity is stored once, and current registrations are counted from the registration table.
- `registrations`: joins students to events and stores event choices plus the student's department and study year at booking time.
- Primary/foreign keys keep references valid; `(event_id, student_id)` is unique so a student cannot register twice for one event.

This design avoids copying student/event names or seat counts into every registration. Student feedback remains a separate JSON/CSV file-based submission flow in `database/data/`. Registration and login use PDO prepared statements.

Apache blocks direct web access to the legacy student JSON and CSV exports in `database/data`; the PHP application accesses them locally for the existing export behavior.
