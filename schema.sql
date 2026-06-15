-- ============================================================
-- MATCH MOOV — Schéma unifié MariaDB
-- Toutes les tables nécessaires aux 9 fonctionnalités
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS signalement;
DROP TABLE IF EXISTS favori;
DROP TABLE IF EXISTS commentaire;
DROP TABLE IF EXISTS notification;
DROP TABLE IF EXISTS ami;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    prenom          VARCHAR(100)    NOT NULL,
    nom             VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL,
    mdp             VARCHAR(255)    NOT NULL,
    age             TINYINT UNSIGNED DEFAULT NULL,
    classe          VARCHAR(20)     DEFAULT NULL,
    organisation    VARCHAR(120)    DEFAULT NULL,
    avatar          VARCHAR(500)    DEFAULT NULL,
    points          INT UNSIGNED    NOT NULL DEFAULT 0,
    email_verifie   TINYINT(1)      NOT NULL DEFAULT 0,
    token_verif     VARCHAR(64)     DEFAULT NULL,
    token_reset     VARCHAR(64)     DEFAULT NULL,
    token_reset_exp DATETIME        DEFAULT NULL,
    est_admin       TINYINT(1)      NOT NULL DEFAULT 0,
    actif           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE badges (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nom             VARCHAR(120)    NOT NULL,
    description     VARCHAR(255)    DEFAULT NULL,
    icone           VARCHAR(10)     DEFAULT '🏅',
    seuil_points    INT UNSIGNED    DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_badges_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    user_id         INT UNSIGNED    NOT NULL,
    badge_id        INT UNSIGNED    NOT NULL,
    obtenu_le       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_ub_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activities (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_organisateur     INT UNSIGNED    NOT NULL,
    titre               VARCHAR(180)    NOT NULL,
    description         TEXT,
    categorie           VARCHAR(80)     NOT NULL DEFAULT 'Autre',
    lieu                VARCHAR(180)    NOT NULL,
    ville               VARCHAR(100)    DEFAULT NULL,
    date_activite       DATE            NOT NULL,
    heure_debut         TIME            NOT NULL,
    heure_fin           TIME            DEFAULT NULL,
    nb_max_participants INT UNSIGNED    DEFAULT NULL,
    image_url           VARCHAR(500)    DEFAULT NULL,
    est_payante         TINYINT(1)      NOT NULL DEFAULT 0,
    statut              ENUM('actif','annule','termine') NOT NULL DEFAULT 'actif',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_act_date (date_activite),
    KEY idx_act_cat  (categorie),
    KEY idx_act_org  (id_organisateur),
    CONSTRAINT fk_act_org FOREIGN KEY (id_organisateur) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registrations (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    activity_id     INT UNSIGNED    NOT NULL,
    date_inscription TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reg (user_id, activity_id),
    CONSTRAINT fk_reg_user FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_reg_act  FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_expediteur       INT UNSIGNED    NOT NULL,
    id_destinataire     INT UNSIGNED    DEFAULT NULL,
    id_groupe_activite  INT UNSIGNED    DEFAULT NULL,
    contenu             TEXT            NOT NULL,
    lu                  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_exp  (id_expediteur),
    KEY idx_msg_dest (id_destinataire),
    KEY idx_msg_grp  (id_groupe_activite),
    CONSTRAINT fk_msg_exp  FOREIGN KEY (id_expediteur)      REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_msg_dest FOREIGN KEY (id_destinataire)    REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_msg_grp  FOREIGN KEY (id_groupe_activite) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ami (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    demandeur_id    INT UNSIGNED    NOT NULL,
    recepteur_id    INT UNSIGNED    NOT NULL,
    statut          ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ami (demandeur_id, recepteur_id),
    CONSTRAINT fk_ami_dem FOREIGN KEY (demandeur_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ami_rec FOREIGN KEY (recepteur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    type        VARCHAR(50)     NOT NULL,
    message     TEXT            NOT NULL,
    lien        VARCHAR(300)    DEFAULT NULL,
    est_lue     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commentaire (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    activity_id INT UNSIGNED    NOT NULL,
    contenu     TEXT            NOT NULL,
    note        TINYINT UNSIGNED DEFAULT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_com_user FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_com_act  FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favori (
    user_id     INT UNSIGNED    NOT NULL,
    activity_id INT UNSIGNED    NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, activity_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_fav_act  FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE signalement (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    type_cible  ENUM('activite','utilisateur','commentaire') NOT NULL,
    cible_id    INT UNSIGNED    NOT NULL,
    raison      TEXT            NOT NULL,
    statut      ENUM('en_attente','traite','rejete') NOT NULL DEFAULT 'en_attente',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_sig_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Badges de départ
INSERT INTO badges (nom, description, icone, seuil_points) VALUES
('Bienvenu',     'Compte créé avec succès',               '👋', 0),
('Explorateur',  'A participé à 3 activités ou plus',     '🔍', 30),
('Organisateur', 'A créé au moins une activité',          '🎯', 20),
('Fidèle',       'A participé à 10 activités ou plus',    '⭐', 100),
('Social',       'A au moins 5 amis',                     '🤝', 50),
('Populaire',    'Activité avec 10+ participants',        '🔥', 80);
