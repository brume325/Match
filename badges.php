<?php
/**
 * badges.php — Attribution automatique des badges selon les actions utilisateur.
 * Utilise le schéma unifié (tables: users, badges, user_badges, registrations, activities, ami).
 */
function attribuer_badges(int $user_id, PDO $pdo): array {
    $nouveaux = [];
    try {
        $s = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE user_id=:uid');
        $s->execute([':uid' => $user_id]);
        $nb_part = (int)$s->fetchColumn();

        $s = $pdo->prepare('SELECT COUNT(*) FROM activities WHERE id_organisateur=:uid');
        $s->execute([':uid' => $user_id]);
        $nb_crea = (int)$s->fetchColumn();

        $s = $pdo->prepare("SELECT COUNT(*) FROM ami WHERE (demandeur_id=:uid OR recepteur_id=:uid2) AND statut='accepte'");
        $s->execute([':uid' => $user_id, ':uid2' => $user_id]);
        $nb_amis = (int)$s->fetchColumn();

        $regles = [
            'Bienvenu'     => true,
            'Explorateur'  => $nb_part >= 3,
            'Organisateur' => $nb_crea >= 1,
            'Fidèle'       => $nb_part >= 10,
            'Social'       => $nb_amis >= 5,
        ];

        foreach ($regles as $nom => $condition) {
            if (!$condition) continue;
            $s = $pdo->prepare('SELECT id FROM badges WHERE nom=:nom');
            $s->execute([':nom' => $nom]);
            $badge = $s->fetch();
            if (!$badge) continue;
            $bid = (int)$badge['id'];
            $s = $pdo->prepare('SELECT 1 FROM user_badges WHERE user_id=:uid AND badge_id=:bid');
            $s->execute([':uid' => $user_id, ':bid' => $bid]);
            if ($s->fetchColumn()) continue;
            $pdo->prepare('INSERT INTO user_badges (user_id,badge_id) VALUES (:uid,:bid)')
                ->execute([':uid' => $user_id, ':bid' => $bid]);
            $nouveaux[] = $nom;
        }
    } catch (PDOException $e) { /* silencieux */ }
    return $nouveaux;
}

function ajouter_points(int $user_id, int $points, PDO $pdo): void {
    try {
        $pdo->prepare('UPDATE users SET points=points+:p WHERE id=:uid')
            ->execute([':p' => $points, ':uid' => $user_id]);
    } catch (PDOException $e) { /* silencieux */ }
}