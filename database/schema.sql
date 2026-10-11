CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;

CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    mobile VARCHAR(20) NULL,
    course VARCHAR(100) NOT NULL,
    year_of_study VARCHAR(20) NOT NULL,
    gender VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    city VARCHAR(80) NULL,
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    event_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(60) NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    venue VARCHAR(180) NOT NULL,
    seat_capacity INT UNSIGNED NOT NULL,
    organizer VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    badge VARCHAR(40) NOT NULL DEFAULT 'Open',
    PRIMARY KEY (event_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
    registration_id VARCHAR(32) NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    department VARCHAR(40) NOT NULL,
    year_of_study VARCHAR(20) NOT NULL,
    t_shirt_size VARCHAR(4) NOT NULL DEFAULT 'NA',
    special_note VARCHAR(300) NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (registration_id),
    UNIQUE KEY uq_registration_event_student (event_id, student_id),
    KEY idx_registrations_student (student_id),
    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id) REFERENCES events (event_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_registrations_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
