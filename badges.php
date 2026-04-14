<?php
// badges.php - Système d'attribution automatique des badges et points

function attribuer_badges($user_id, $pdo) {
    $new_badges = [];
    try {
        // Récupérer tous les badges disponibles
        $stmt = $pdo->query("SELECT nom, badge_id FROM badge");
        $badges = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // BADGE BIENVENU : attribue des l'inscription
        if (isset($badges['Bienvenu'])) {
            $stmt = $pdo->prepare("SELECT 1 FROM utilisateur_badge WHERE user_id = :uid AND badge_id = :bid");
            $stmt->execute([':uid' => $user_id, ':bid' => $badges['Bienvenu']]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO utilisateur_badge (user_id, badge_id) VALUES (:uid, :bid)");
                $stmt->execute([':uid' => $user_id, ':bid' => $badges['Bienvenu']]);
                $new_badges[] = 'Bienvenu';
            }
        }

        // BADGE ORGANISATEUR : 3 activités créées
        if (isset($badges['Organisateur'])) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as nb
                FROM activite
                WHERE createur_id = :uid
            ");
            $stmt->execute([':uid' => $user_id]);
            $result = $stmt->fetch();
            
            if ($result['nb'] >= 3) {
                $stmt = $pdo->prepare("
                    SELECT 1 FROM utilisateur_badge
                    WHERE user_id = :uid AND badge_id = :bid
                ");
                $stmt->execute([':uid' => $user_id, ':bid' => $badges['Organisateur']]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO utilisateur_badge (user_id, badge_id)
                        VALUES (:uid, :bid)
                    ");
                    $stmt->execute([':uid' => $user_id, ':bid' => $badges['Organisateur']]);
                    $new_badges[] = 'Organisateur';
                }
            }
        }

        // BADGE EXPLORATEUR : 5 activités différentes
        if (isset($badges['Explorateur'])) {
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT activity_id) as nb
                FROM participation
                WHERE user_id = :uid AND statut_inscription = 'inscrit'
            ");
            $stmt->execute([':uid' => $user_id]);
            $result = $stmt->fetch();
            
            if ($result['nb'] >= 5) {
                $stmt = $pdo->prepare("
                    SELECT 1 FROM utilisateur_badge
                    WHERE user_id = :uid AND badge_id = :bid
                ");
                $stmt->execute([':uid' => $user_id, ':bid' => $badges['Explorateur']]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO utilisateur_badge (user_id, badge_id)
                        VALUES (:uid, :bid)
                    ");
                    $stmt->execute([':uid' => $user_id, ':bid' => $badges['Explorateur']]);
                    $new_badges[] = 'Explorateur';
                }
            }
        }

        // BADGE FIDÈLE : 5 participations
        if (isset($badges['Fidèle'])) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as nb
                FROM participation
                WHERE user_id = :uid AND statut_inscription = 'inscrit'
            ");
            $stmt->execute([':uid' => $user_id]);
            $result = $stmt->fetch();
            
            if ($result['nb'] >= 5) {
                $stmt = $pdo->prepare("
                    SELECT 1 FROM utilisateur_badge
                    WHERE user_id = :uid AND badge_id = :bid
                ");
                $stmt->execute([':uid' => $user_id, ':bid' => $badges['Fidèle']]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO utilisateur_badge (user_id, badge_id)
                        VALUES (:uid, :bid)
                    ");
                    $stmt->execute([':uid' => $user_id, ':bid' => $badges['Fidèle']]);
                    $new_badges[] = 'Fidele';
                }
            }
        }

    } catch (PDOException $e) {
        // Log erreur en silence
    }

    return $new_badges;
}

function ajouter_points($user_id, $points, $pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE utilisateur
            SET points = points + :points
            WHERE user_id = :uid
        ");
        $stmt->execute([
            ':points' => $points,
            ':uid' => $user_id
        ]);
    } catch (PDOException $e) {
        // Log erreur en silence
    }
}
?>