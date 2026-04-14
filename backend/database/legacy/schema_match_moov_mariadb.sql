-- ============================================================
-- MATCH MOOV - Schema MariaDB (SQL)
-- Stack cible: PHP + MariaDB
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    organisation VARCHAR(120) DEFAULT NULL,
    niveau VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_badges_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    user_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    obtenu_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_user_badges_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_badges_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activities (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_organisateur INT UNSIGNED NOT NULL,
    titre VARCHAR(180) NOT NULL,
    description TEXT,
    categorie VARCHAR(80) NOT NULL,
    lieu VARCHAR(180) NOT NULL,
    date_activite DATE NOT NULL,
    heure_activite TIME NOT NULL,
    duree_minutes INT UNSIGNED NOT NULL,
    nb_max_participants INT UNSIGNED DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    est_payante TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activities_organisateur (id_organisateur),
    KEY idx_activities_date (date_activite),
    KEY idx_activities_categorie (categorie),
    CONSTRAINT fk_activities_organisateur FOREIGN KEY (id_organisateur) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED NOT NULL,
    date_inscription TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registrations_user_activity (user_id, activity_id),
    KEY idx_registrations_activity (activity_id),
    CONSTRAINT fk_registrations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_expediteur INT UNSIGNED NOT NULL,
    id_destinataire INT UNSIGNED DEFAULT NULL,
    id_groupe_activite INT UNSIGNED DEFAULT NULL,
    contenu TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_expediteur (id_expediteur),
    KEY idx_messages_destinataire (id_destinataire),
    KEY idx_messages_groupe (id_groupe_activite),
    CONSTRAINT fk_messages_expediteur FOREIGN KEY (id_expediteur) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_destinataire FOREIGN KEY (id_destinataire) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_groupe FOREIGN KEY (id_groupe_activite) REFERENCES activities(id) ON DELETE CASCADE,
    CONSTRAINT chk_messages_target CHECK (
        (id_destinataire IS NOT NULL AND id_groupe_activite IS NULL)
        OR (id_destinataire IS NULL AND id_groupe_activite IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO badges (nom, description) VALUES
('Explorateur', 'Participe a plusieurs activites locales'),
('Organisateur', 'Cree et anime des activites pour la communaute');
