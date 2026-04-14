<?php
session_start();
require_once __DIR__ . '/../config.php';

$isLoggedIn = isset($_SESSION['user_id']);

// Paramètres de recherche
$q          = trim($_GET['q']          ?? '');
$categorie  = (int)($_GET['categorie'] ?? 0);
$ville      = trim($_GET['ville']      ?? '');
$payant     = $_GET['payant']          ?? '';    // '' | '0' | '1'
$date_min   = trim($_GET['date_min']   ?? '');
$date_max   = trim($_GET['date_max']   ?? '');
$sort       = $_GET['sort']            ?? 'date'; // date | popularite | places
$welcome    = isset($_GET['welcome']) && $_GET['welcome'] === '1';

// Catégories pour le filtre
try {
    $categories = $pdo->query("SELECT categorie_id, nom, icone FROM categorie ORDER BY nom")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Construction de la requête dynamique
$where  = ['a.statut = \'actif\''];
$params = [];

if ($q !== '') {
    $where[]        = "(LOWER(a.titre) LIKE LOWER(:q) OR LOWER(a.description) LIKE LOWER(:q))";
    $params[':q']   = '%' . $q . '%';
}
if ($categorie > 0) {
    $where[]              = "a.categorie_id = :cat";
    $params[':cat']       = $categorie;
}
if ($ville !== '') {
    $where[]              = "LOWER(l.ville) LIKE LOWER(:ville)";
    $params[':ville']     = '%' . $ville . '%';
}
if ($payant === '0') {
    $where[] = "a.est_payante = 0";
} elseif ($payant === '1') {
    $where[] = "a.est_payante > 0";
}
if ($date_min !== '') {
    $where[]              = "a.date >= :dmin";
    $params[':dmin']      = $date_min;
}
if ($date_max !== '') {
    $where[]              = "a.date <= :dmax";
    $params[':dmax']      = $date_max;
}

$orderBy = match($sort) {
    'popularite' => 'a.popularite DESC',
    'places'     => 'places_restantes DESC',
    default      => 'a.date ASC, a.heure_debut ASC',
};

$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

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
               COALESCE(l.ville, '')    AS ville,
               COALESCE(l.nom_lieu, '') AS nom_lieu,
               COALESCE(cat.nom, '')    AS categorie_nom,
               COALESCE(cat.icone, '') AS categorie_icone,
               u.prenom AS org_prenom, u.nom AS org_nom,
               SUM(CASE WHEN p.statut_inscription = 'inscrit' THEN 1 ELSE 0 END) AS nb_participants,
               GREATEST(0, COALESCE(a.nb_places_max, 9999) - SUM(CASE WHEN p.statut_inscription = 'inscrit' THEN 1 ELSE 0 END)) AS places_restantes
        FROM activite a
        LEFT JOIN lieu l         ON a.lieu_id      = l.lieu_id
        LEFT JOIN categorie cat  ON a.categorie_id = cat.categorie_id
        LEFT JOIN utilisateur u  ON a.createur_id  = u.user_id
        LEFT JOIN participation p ON a.activity_id = p.activity_id
        $whereSql
        GROUP BY a.activity_id, l.lieu_id, cat.categorie_id, u.user_id
        ORDER BY $orderBy
        LIMIT 60
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    $results = [];
}

$nb_results = count($results);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Match&Move – Rechercher des activités</title>
  <link rel="icon" type="image/svg+xml" href="/assets/img/logo-match-moov.svg">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    .search-hero {
      background: linear-gradient(135deg, var(--mm-blue) 0%, #42b36b 100%);
      padding: 36px 16px;
      text-align: center;
      color: var(--mm-white);
      margin-bottom: 0;
    }
    .search-hero h1 { color: var(--mm-white); margin-bottom: 8px; }
    .search-hero p  { opacity: .85; margin-bottom: 20px; }

    .search-bar {
      display: flex;
      max-width: 600px;
      margin: 0 auto;
      gap: 0;
      border-radius: 50px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,.2);
    }
    .search-bar input {
      flex: 1; padding: 14px 20px; border: 0; font-size: 1rem;
      font-family: 'Open Sans', sans-serif;
    }
    .search-bar input:focus { outline: none; }
    .search-bar button {
      background: var(--mm-yellow); color: var(--mm-dark);
      padding: 14px 24px; border: 0; font-weight: 700;
      cursor: pointer; font-size: 1rem; transition: background .15s;
    }
    .search-bar button:hover { background: var(--mm-yellow-dark); }

    .filters-bar {
      background: var(--mm-white);
      border-bottom: 1px solid var(--mm-grey-light);
      padding: 14px 16px;
      display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    }
    .filter-select {
      padding: 8px 12px; border: 2px solid var(--mm-grey-light);
      border-radius: 8px; font-size: .85rem; background: var(--mm-white);
      color: var(--mm-dark); cursor: pointer;
    }
    .filter-select:focus { outline: none; border-color: var(--mm-blue); }

    .results-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 16px; flex-wrap: wrap; gap: 8px;
    }
    .results-count { color: var(--mm-grey); font-size: .88rem; }

    .activity-type-badge {
      display: inline-flex; align-items: center; gap: 4px;
      background: var(--mm-blue-light); color: var(--mm-blue);
      padding: 2px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600;
    }
    .activity-type-badge.payant { background: #fef9c3; color: #854d0e; }
    .activity-type-badge.gratuit { background: #dcfce7; color: #15803d; }

    .no-results {
      text-align: center; padding: 60px 20px; color: var(--mm-grey);
    }
    .no-results span { font-size: 3rem; display: block; margin-bottom: 12px; }
  </style>
</head>
<body>
<?php require_once '_nav.php'; ?>

<!-- Hero de recherche -->
<div class="search-hero">
  <h1>Trouver une activite</h1>
  <p>Explore les événements près de chez toi et rejoins ta communauté</p>
  <form method="GET" action="recherche.php" class="search-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Sport, culture, jeux…">
    <?php if ($categorie): ?><input type="hidden" name="categorie" value="<?= $categorie ?>"><?php endif; ?>
    <?php if ($ville): ?><input type="hidden" name="ville" value="<?= htmlspecialchars($ville) ?>"><?php endif; ?>
    <button type="submit">Rechercher</button>
  </form>
</div>

<!-- Filtres -->
<div class="filters-bar">
  <form method="GET" action="recherche.php" id="filterForm" style="display:contents;">
    <?php if ($q): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>

    <select name="categorie" class="filter-select" onchange="document.getElementById('filterForm').submit()">
      <option value="">Toutes catégories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['categorie_id'] ?>" <?= $categorie === (int)$cat['categorie_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input type="text" name="ville" class="filter-select"
           placeholder="Ville..." value="<?= htmlspecialchars($ville) ?>"
           style="min-width:120px;">

    <select name="payant" class="filter-select" onchange="document.getElementById('filterForm').submit()">
      <option value=""    <?= $payant === ''  ? 'selected' : '' ?>>Gratuit/Payant</option>
      <option value="0"   <?= $payant === '0' ? 'selected' : '' ?>>Gratuit uniquement</option>
      <option value="1"   <?= $payant === '1' ? 'selected' : '' ?>>Payant uniquement</option>
    </select>

    <input type="date" name="date_min" class="filter-select"
           value="<?= htmlspecialchars($date_min) ?>" title="À partir du">

    <input type="date" name="date_max" class="filter-select"
           value="<?= htmlspecialchars($date_max) ?>" title="Jusqu'au">

    <select name="sort" class="filter-select" onchange="document.getElementById('filterForm').submit()">
      <option value="date"       <?= $sort === 'date'       ? 'selected' : '' ?>>Trier : Date</option>
      <option value="popularite" <?= $sort === 'popularite' ? 'selected' : '' ?>>Trier : Popularité</option>
      <option value="places"     <?= $sort === 'places'     ? 'selected' : '' ?>>Trier : Places dispo</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
    <a href="recherche.php" class="btn btn-outline btn-sm">Réinitialiser</a>
  </form>
</div>

<!-- Résultats -->
<div class="container">
  <?php if ($welcome): ?>
    <div style="background:var(--mm-blue-light);border:1px solid var(--mm-blue-mid);color:var(--mm-dark);padding:12px 14px;border-radius:10px;margin-bottom:14px;">
      Compte cree avec succes. Clique sur <strong>Participer</strong> sur une carte pour rejoindre ta premiere sortie.
    </div>
  <?php endif; ?>

  <div class="results-header">
    <h2><?= $nb_results ?> activité<?= $nb_results !== 1 ? 's' : '' ?> trouvée<?= $nb_results !== 1 ? 's' : '' ?></h2>
    <?php if ($q || $categorie || $ville): ?>
      <span class="results-count">
        Filtres actifs :
        <?php if ($q): ?> "<strong><?= htmlspecialchars($q) ?></strong>"<?php endif; ?>
        <?php if ($categorie): ?>
          <?php $catName = array_column($categories, 'nom', 'categorie_id')[$categorie] ?? ''; ?>
          · <?= htmlspecialchars($catName) ?>
        <?php endif; ?>
        <?php if ($ville): ?> · <?= htmlspecialchars($ville) ?><?php endif; ?>
      </span>
    <?php endif; ?>
  </div>

  <?php if (empty($results)): ?>
    <div class="no-results">
      <span>Resultats</span>
      <h3>Aucune activité trouvée</h3>
      <p>Essaie d'autres mots-clés ou modifie tes filtres.</p>
      <?php if ($isLoggedIn): ?>
        <a href="cree_activite.php" class="btn btn-primary" style="margin-top:16px;">Créer la première !</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="activities">
      <?php foreach ($results as $act):
        $date   = $act['date']       ? date('d/m/Y', strtotime($act['date'])) : '';
        $hdeb   = $act['heure_debut'] ? substr($act['heure_debut'], 0, 5) : '';
        $hfin   = $act['heure_fin']   ? substr($act['heure_fin'],  0, 5) : '';
        $lieu   = $act['nom_lieu'] ?: $act['ville'];
        $catNom   = $act['categorie_nom']   ?? '';
        $catIcone = $act['categorie_icone'] ?? '';
        $places   = (int)$act['places_restantes'];
        $complet  = $act['nb_places_max'] && $places <= 0;
      ?>
        <article class="activity-card <?= $complet ? 'opacity-60' : '' ?>">
          <?php if ($act['image_couverture']): ?>
            <img src="<?= htmlspecialchars($act['image_couverture']) ?>"
                 alt="<?= htmlspecialchars($act['titre']) ?>"
                 class="activity-image"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="activity-image-placeholder" data-cat="<?= htmlspecialchars($catNom) ?>" style="display:none;">
              <span class="placeholder-emoji"><?= $catIcone ?></span>
              <span class="placeholder-label"><?= htmlspecialchars($catNom ?: 'Activité') ?></span>
            </div>
          <?php else: ?>
            <div class="activity-image-placeholder" data-cat="<?= htmlspecialchars($catNom) ?>">
              <span class="placeholder-emoji"><?= $catIcone ?></span>
              <span class="placeholder-label"><?= htmlspecialchars($catNom ?: 'Activité') ?></span>
            </div>
          <?php endif; ?>
          <div class="activity-body">
            <h2 class="activity-title"><?= htmlspecialchars($act['titre']) ?></h2>

            <div class="activity-tags">
              <?php if ($act['categorie_nom']): ?>
                <span class="activity-type-badge"><?= htmlspecialchars($act['categorie_nom']) ?></span>
              <?php endif; ?>
              <?php if ($act['est_payante']): ?>
                <span class="activity-type-badge payant"><?= number_format($act['prix'], 2) ?> EUR</span>
              <?php else: ?>
                <span class="activity-type-badge gratuit">Gratuit</span>
              <?php endif; ?>
              <?php if ($complet): ?>
                <span class="tag" style="background:#fee2e2;color:#dc2626;">Complet</span>
              <?php endif; ?>
            </div>

            <p class="activity-meta">
              <?php if ($lieu): ?><?= htmlspecialchars($lieu) ?><?php endif; ?>
              <?php if ($date): ?> • <?= $date ?><?php endif; ?>
              <?php if ($hdeb): ?> • <?= $hdeb ?><?php if ($hfin): ?>–<?= $hfin ?><?php endif; ?><?php endif; ?>
            </p>

            <p class="activity-participants">
              <?= (int)$act['nb_participants'] ?> participant<?= $act['nb_participants'] > 1 ? 's' : '' ?>
              <?php if ($act['nb_places_max']): ?> / <?= (int)$act['nb_places_max'] ?> places<?php endif; ?>
            </p>

            <p class="activity-desc"><?= htmlspecialchars($act['description']) ?></p>

            <p style="font-size:.78rem;color:var(--mm-grey);">
              Organisé par <?= htmlspecialchars($act['org_prenom'] . ' ' . $act['org_nom']) ?>
            </p>

            <div class="activity-actions">
              <a href="activite.php?id=<?= (int)$act['activity_id'] ?>" class="btn btn-primary btn-sm">
                Voir l'activité
              </a>
              <?php if ($isLoggedIn && !$complet): ?>
                <a href="participer.php?activity_id=<?= (int)$act['activity_id'] ?>" class="btn btn-secondary btn-sm">
                  Participer
                </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
