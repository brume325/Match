<?php
session_start();

// Protection : obligation d'être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$errors = [];
$success = false;
$new_badges = [];

$activity_id = isset($_GET['activity_id']) ? (int)$_GET['activity_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($activity_id <= 0) {
    $errors[] = 'Activité invalide.';
} else {
    try {
        // vérifier si l'activité existe
        $stmt = $pdo->prepare('SELECT activity_id FROM activite WHERE activity_id = :id');
        $stmt->execute([':id' => $activity_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Activité introuvable.';
        } else {
            // vérifier si déjà inscrit
            $stmt = $pdo->prepare('
                SELECT participation_id FROM participation
                WHERE user_id = :uid AND activity_id = :aid AND statut_inscription = \'inscrit\'
            ');
            $stmt->execute([
                ':uid' => $user_id,
                ':aid' => $activity_id,
            ]);
            if ($stmt->fetch()) {
                $errors[] = 'Tu es déjà inscrit à cette activité.';
            } else {
                // insérer la participation
                $stmt = $pdo->prepare('
                    INSERT INTO participation (user_id, activity_id, statut_inscription)
                    VALUES (:uid, :aid, \'inscrit\')
                ');
                $stmt->execute([
                    ':uid' => $user_id,
                    ':aid' => $activity_id,
                ]);

                // Ajouter points + vérifier badges
                require_once __DIR__ . '/../badges.php';
                ajouter_points($user_id, 10, $pdo); // 10 points par participation
                $new_badges = attribuer_badges($user_id, $pdo);

                $success = true;
            }
        }
    } catch (PDOException $e) {
        $errors[] = 'Erreur serveur.';
        $errors[] = $e->getMessage(); // debug
    }
}
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <title>Inscription activité</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo-match-moov.svg">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
      body { font-family: Arial, sans-serif; padding: 20px; background: #f3f4f6; }
      .message { background: white; padding: 20px; border-radius: 12px; max-width: 500px; margin: 20px auto; }
      .success { color: #15803d; }
      .error { color: #b91c1c; }
      a { color: #2fbf71; text-decoration: none; }
      a:hover { text-decoration: underline; }
    </style>
  </head>
  <body>
    <div class="message">
      <?php if ($success): ?>
        <h2 class="success">Inscription enregistrée !</h2>
        <p>Tu es maintenant inscrit à cette activité.</p>
        <p>Tu viens de gagner <strong>10 points</strong>.</p>
        <?php if (!empty($new_badges)): ?>
          <p><strong>Nouveau badge debloque :</strong> <?= htmlspecialchars(implode(', ', $new_badges)) ?></p>
        <?php endif; ?>
        <p><a href="activite.php?id=<?= (int)$activity_id ?>">← Retour à l'activité</a></p>
        <p><a href="profil.php">Voir mon profil</a></p>
      <?php else: ?>
        <h2 class="error">Inscription impossible</h2>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
        <p><a href="activite.php?id=<?= (int)$activity_id ?>">← Retour à l'activité</a></p>
      <?php endif; ?>
    </div>
  </body>
</html>
