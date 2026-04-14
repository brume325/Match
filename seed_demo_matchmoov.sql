SET NAMES utf8mb4;

-- Utilisateurs de demo
INSERT INTO utilisateur (prenom, nom, email, mdp, age, classe, bio, points, niveau_gamification, est_admin, est_banni, email_verifie)
VALUES
('Alex', 'Martin', 'alex.demo@matchmoov.local', '$2y$10$UfXtjceXsE2z4fY.hWoM8O.UWTO8hofIVc0IC9SUXJj00WWe2kzIq', 25, 'M1', 'Adepte de sports collectifs et de sorties locales.', 180, 2, 0, 0, 1),
('Emma', 'Dubois', 'emma.demo@matchmoov.local', '$2y$10$UfXtjceXsE2z4fY.hWoM8O.UWTO8hofIVc0IC9SUXJj00WWe2kzIq', 22, 'B3', 'Passionnee de culture et d events creatifs.', 240, 3, 0, 0, 1)
ON DUPLICATE KEY UPDATE
prenom = VALUES(prenom),
nom = VALUES(nom),
age = VALUES(age),
classe = VALUES(classe),
bio = VALUES(bio),
points = VALUES(points),
niveau_gamification = VALUES(niveau_gamification),
est_banni = 0,
email_verifie = 1;

-- Lieux de demo
INSERT INTO lieu (nom_lieu, adresse, ville, code_postal)
VALUES
('Parc de la Pepiniere', '1 Place Stanislas', 'Nancy', '54000'),
('Maison des Associations', '12 Rue de la Republique', 'Nancy', '54000'),
('Quai des Artistes', '8 Quai Sainte-Catherine', 'Nancy', '54000')
ON DUPLICATE KEY UPDATE nom_lieu = VALUES(nom_lieu);

-- Activites de demo
INSERT INTO activite (titre, description, date, heure_debut, heure_fin, image_couverture, nb_places_max, est_payante, prix, popularite, statut, createur_id, lieu_id, categorie_id)
SELECT
    'Foot a 5 entre voisins',
    'Session detendue pour rencontrer du monde autour du sport.',
    DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    '18:30:00',
    '20:00:00',
    NULL,
    12,
    0,
    0,
    30,
    'actif',
    u.user_id,
    l.lieu_id,
    c.categorie_id
FROM utilisateur u
JOIN lieu l ON l.nom_lieu = 'Parc de la Pepiniere'
JOIN categorie c ON c.nom = 'Sport'
WHERE u.email = 'alex.demo@matchmoov.local'
AND NOT EXISTS (
    SELECT 1 FROM activite a
    WHERE a.titre = 'Foot a 5 entre voisins' AND a.createur_id = u.user_id
);

INSERT INTO activite (titre, description, date, heure_debut, heure_fin, image_couverture, nb_places_max, est_payante, prix, popularite, statut, createur_id, lieu_id, categorie_id)
SELECT
    'Atelier peinture urbaine',
    'Activite creative pour debutants et passionnes.',
    DATE_ADD(CURDATE(), INTERVAL 4 DAY),
    '19:00:00',
    '21:00:00',
    NULL,
    10,
    1,
    8.50,
    24,
    'actif',
    u.user_id,
    l.lieu_id,
    c.categorie_id
FROM utilisateur u
JOIN lieu l ON l.nom_lieu = 'Maison des Associations'
JOIN categorie c ON c.nom = 'Culture'
WHERE u.email = 'emma.demo@matchmoov.local'
AND NOT EXISTS (
    SELECT 1 FROM activite a
    WHERE a.titre = 'Atelier peinture urbaine' AND a.createur_id = u.user_id
);

INSERT INTO activite (titre, description, date, heure_debut, heure_fin, image_couverture, nb_places_max, est_payante, prix, popularite, statut, createur_id, lieu_id, categorie_id)
SELECT
    'Run du samedi matin',
    'Sortie running conviviale, tous niveaux.',
    DATE_ADD(CURDATE(), INTERVAL 6 DAY),
    '09:30:00',
    '11:00:00',
    NULL,
    20,
    0,
    0,
    36,
    'actif',
    u.user_id,
    l.lieu_id,
    c.categorie_id
FROM utilisateur u
JOIN lieu l ON l.nom_lieu = 'Quai des Artistes'
JOIN categorie c ON c.nom = 'Sport'
WHERE u.email = 'alex.demo@matchmoov.local'
AND NOT EXISTS (
    SELECT 1 FROM activite a
    WHERE a.titre = 'Run du samedi matin' AND a.createur_id = u.user_id
);

-- Participations de demo pour KPI/badges
INSERT IGNORE INTO participation (user_id, activity_id, statut_inscription)
SELECT u.user_id, a.activity_id, 'inscrit'
FROM utilisateur u
JOIN activite a ON a.titre IN ('Foot a 5 entre voisins', 'Atelier peinture urbaine', 'Run du samedi matin')
WHERE u.email = 'emma.demo@matchmoov.local';

INSERT IGNORE INTO participation (user_id, activity_id, statut_inscription)
SELECT u.user_id, a.activity_id, 'inscrit'
FROM utilisateur u
JOIN activite a ON a.titre IN ('Foot a 5 entre voisins', 'Atelier peinture urbaine', 'Run du samedi matin')
WHERE u.email = 'alex.demo@matchmoov.local';

-- Badges de demo visibles immediatement
INSERT IGNORE INTO utilisateur_badge (user_id, badge_id)
SELECT u.user_id, b.badge_id
FROM utilisateur u
JOIN badge b ON b.nom IN ('Explorateur', 'Organisateur')
WHERE u.email IN ('alex.demo@matchmoov.local', 'emma.demo@matchmoov.local');
