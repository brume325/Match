SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    organization VARCHAR(120) DEFAULT NULL,
    level_name VARCHAR(50) DEFAULT NULL,
    rgpd_consent TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activities (
    activity_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    organizer_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT,
    category_name VARCHAR(80) NOT NULL,
    location_name VARCHAR(180) NOT NULL,
    activity_date DATE NOT NULL,
    activity_time TIME NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL,
    max_participants INT UNSIGNED DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    is_paid TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (activity_id),
    KEY idx_activities_organizer (organizer_id),
    KEY idx_activities_date (activity_date),
    KEY idx_activities_category (category_name),
    CONSTRAINT fk_activities_user FOREIGN KEY (organizer_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registrations (
    registration_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (registration_id),
    UNIQUE KEY uq_registrations_user_activity (user_id, activity_id),
    KEY idx_registrations_activity (activity_id),
    CONSTRAINT fk_registrations_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_activity FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
    badge_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    badge_name VARCHAR(120) NOT NULL,
    badge_description VARCHAR(255) DEFAULT NULL,
    threshold_value INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (badge_id),
    UNIQUE KEY uq_badges_name (badge_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    user_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    awarded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    message_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED DEFAULT NULL,
    group_activity_id INT UNSIGNED DEFAULT NULL,
    message_body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_messages_sender (sender_id),
    KEY idx_messages_receiver (receiver_id),
    KEY idx_messages_activity (group_activity_id),
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_activity FOREIGN KEY (group_activity_id) REFERENCES activities(activity_id) ON DELETE CASCADE,
    CONSTRAINT chk_messages_target CHECK (
        (receiver_id IS NOT NULL AND group_activity_id IS NULL)
        OR (receiver_id IS NULL AND group_activity_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
    report_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reporter_id INT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED DEFAULT NULL,
    reported_user_id INT UNSIGNED DEFAULT NULL,
    report_reason TEXT NOT NULL,
    report_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (report_id),
    KEY idx_reports_status (report_status),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_activity FOREIGN KEY (activity_id) REFERENCES activities(activity_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_user FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    audit_log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT UNSIGNED DEFAULT NULL,
    event_name VARCHAR(80) NOT NULL,
    event_payload JSON DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_log_id),
    KEY idx_audit_logs_event (event_name),
    KEY idx_audit_logs_actor (actor_user_id),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO badges (badge_name, badge_description, threshold_value) VALUES
('explorer', 'Awarded after five distinct registrations', 5),
('organizer', 'Awarded after three created activities', 3);
