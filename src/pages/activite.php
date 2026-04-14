<?php
session_start();
require_once __DIR__ . '/../config.php';

$activityId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isLoggedIn = isset($_SESSION['user_id']);
$activity = null;
$errorMessage = '';

if ($activityId <= 0) {
    $errorMessage = 'Activite invalide.';
} else {
    try {
        $sql = "
            SELECT a.activity_id,
                   a.titre,
                   a.description,
                   a.date,
                   a.heure_debut,
                   a.heure_fin,
                   a.image_couverture,
                   a.nb_places_max,
                   a.est_payante,
                   a.prix,
                   a.popularite,
                   COALESCE(l.nom_lieu, '') AS nom_lieu,
                   COALESCE(l.adresse, '') AS adresse,
                   COALESCE(l.ville, '') AS ville,
                   COALESCE(cat.nom, '') AS categorie_nom,
                   u.user_id AS organisateur_id,
                   u.prenom AS org_prenom,
                   u.nom AS org_nom,
                   SUM(CASE WHEN p.statut_inscription = 'inscrit' THEN 1 ELSE 0 END) AS nb_participants,
                   GREATEST(0, COALESCE(a.nb_places_max, 9999) - SUM(CASE WHEN p.statut_inscription = 'inscrit' THEN 1 ELSE 0 END)) AS places_restantes
            FROM activite a
            LEFT JOIN lieu l ON a.lieu_id = l.lieu_id
            LEFT JOIN categorie cat ON a.categorie_id = cat.categorie_id
            LEFT JOIN utilisateur u ON a.createur_id = u.user_id
            LEFT JOIN participation p ON a.activity_id = p.activity_id
            WHERE a.activity_id = :activity_id
            GROUP BY a.activity_id, l.lieu_id, cat.categorie_id, u.user_id
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':activity_id' => $activityId]);
        $activity = $stmt->fetch();

        if (!$activity) {
            $errorMessage = 'Activite introuvable.';
        }
    } catch (PDOException $e) {
        $errorMessage = 'Erreur serveur lors du chargement de l activite.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Match Moov - Detail activite</title>
  <link rel="icon" type="image/svg+xml" href="/assets/img/logo-match-moov.svg">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    .activity-shell {
      max-width: 980px;
      margin: 0 auto;
      padding: 24px 18px 56px;
    }
    .activity-panel {
      background: #ffffff;
      border-radius: 18px;
      box-shadow: var(--mm-shadow-md);
      overflow: hidden;
    }
    .activity-cover {
      width: 100%;
      min-height: 280px;
      background: linear-gradient(135deg, var(--mm-blue) 0%, #59bf75 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-family: 'Montserrat', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      text-align: center;
      padding: 24px;
    }
    .activity-cover img {
      width: 100%;
      max-height: 360px;
      object-fit: cover;
    }
    .activity-content {
      padding: 28px;
      display: grid;
      gap: 18px;
    }
    .activity-meta-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
    }
    .activity-meta-card {
      background: var(--mm-grey-xlight);
      border: 1px solid var(--mm-grey-light);
      border-radius: 12px;
      padding: 14px;
    }
    .activity-meta-card strong {
      display: block;
      color: var(--mm-blue);
      margin-bottom: 6px;
    }
    .activity-actions-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .activity-description {
      white-space: pre-line;
    }
    .activity-error {
      max-width: 720px;
      margin: 40px auto;
      background: #ffffff;
      border: 1px solid #fecaca;
      color: #b91c1c;
      border-radius: 14px;
      padding: 20px;
      box-shadow: var(--mm-shadow-sm);
    }
  </style>
</head>
<body>
<?php require_once '_nav.php'; ?>

<?php if ($errorMessage !== ''): ?>
  <div class="container">
    <div class="activity-error">
      <h1>Activite indisponible</h1>
      <p><?= htmlspecialchars($errorMessage) ?></p>
      <p><a href="/recherche.php" class="btn btn-primary">Retour a la recherche</a></p>
    </div>
  </div>
<?php else: ?>
  <?php
    $dateLabel = $activity['date'] ? date('d/m/Y', strtotime($activity['date'])) : 'Date a confirmer';
    $startTime = $activity['heure_debut'] ? substr($activity['heure_debut'], 0, 5) : 'Horaire a confirmer';
    $endTime = $activity['heure_fin'] ? substr($activity['heure_fin'], 0, 5) : '';
    $locationLabel = trim(($activity['nom_lieu'] ?: '') . ($activity['ville'] ? ' - ' . $activity['ville'] : ''), ' -');
    $priceLabel = (int) $activity['est_payante'] === 1 ? number_format((float) $activity['prix'], 2) . ' EUR' : 'Gratuit';
    $isFull = $activity['nb_places_max'] && (int) $activity['places_restantes'] <= 0;
  ?>
  <main class="activity-shell">
    <article class="activity-panel">
      <div class="activity-cover">
        <?php if (!empty($activity['image_couverture'])): ?>
          <img src="<?= htmlspecialchars($activity['image_couverture']) ?>" alt="<?= htmlspecialchars($activity['titre']) ?>">
        <?php else: ?>
          <span><?= htmlspecialchars($activity['titre']) ?></span>
        <?php endif; ?>
      </div>

      <div class="activity-content">
        <div>
          <h1><?= htmlspecialchars($activity['titre']) ?></h1>
          <?php if (!empty($activity['categorie_nom'])): ?>
            <p class="activity-type-badge" style="margin-top:10px;display:inline-flex;"><?= htmlspecialchars($activity['categorie_nom']) ?></p>
          <?php endif; ?>
        </div>

        <div class="activity-meta-grid">
          <div class="activity-meta-card">
            <strong>Date</strong>
            <span><?= htmlspecialchars($dateLabel) ?></span>
          </div>
          <div class="activity-meta-card">
            <strong>Horaire</strong>
            <span><?= htmlspecialchars($startTime . ($endTime !== '' ? ' - ' . $endTime : '')) ?></span>
          </div>
          <div class="activity-meta-card">
            <strong>Lieu</strong>
            <span><?= htmlspecialchars($locationLabel !== '' ? $locationLabel : 'A preciser') ?></span>
          </div>
          <div class="activity-meta-card">
            <strong>Tarif</strong>
            <span><?= htmlspecialchars($priceLabel) ?></span>
          </div>
          <div class="activity-meta-card">
            <strong>Participants</strong>
            <span><?= (int) $activity['nb_participants'] ?> inscrit<?= (int) $activity['nb_participants'] > 1 ? 's' : '' ?></span>
          </div>
          <div class="activity-meta-card">
            <strong>Places restantes</strong>
            <span><?= $activity['nb_places_max'] ? (int) $activity['places_restantes'] . ' / ' . (int) $activity['nb_places_max'] : 'Non limitees' ?></span>
          </div>
        </div>

        <section>
          <h2>Description</h2>
          <p class="activity-description"><?= nl2br(htmlspecialchars($activity['description'] ?? 'Aucune description.')) ?></p>
        </section>

        <section>
          <h2>Organisation</h2>
          <p>Proposee par <?= htmlspecialchars(trim(($activity['org_prenom'] ?? '') . ' ' . ($activity['org_nom'] ?? ''))) ?></p>
          <?php if (!empty($activity['adresse'])): ?>
            <p>Adresse: <?= htmlspecialchars($activity['adresse']) ?></p>
          <?php endif; ?>
        </section>

        <div class="activity-actions-row">
          <a href="/recherche.php" class="btn btn-outline">Retour a la recherche</a>
          <?php if ($isLoggedIn && !$isFull): ?>
            <a href="/participer.php?activity_id=<?= (int) $activity['activity_id'] ?>" class="btn btn-primary">Participer</a>
          <?php elseif (!$isLoggedIn): ?>
            <a href="/login.php" class="btn btn-primary">Se connecter pour participer</a>
          <?php endif; ?>
        </div>
      </div>
    </article>
  </main>
<?php endif; ?>
</body>
</html>
