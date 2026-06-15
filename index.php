<?php
session_start();
require_once 'config.php';

// Activités populaires (top 3 par participants)
$stmt = $pdo->query("
    SELECT a.*, u.prenom, u.nom,
           COUNT(r.id) AS nb_participants
    FROM activities a
    JOIN users u ON u.id = a.id_organisateur
    LEFT JOIN registrations r ON r.activity_id = a.id
    WHERE a.statut='actif' AND a.date_activite >= CURDATE()
    GROUP BY a.id
    ORDER BY nb_participants DESC
    LIMIT 3
");
$populaires = $stmt->fetchAll();

// Activités à venir (les prochaines)
$stmt = $pdo->query("
    SELECT a.*, u.prenom, u.nom,
           COUNT(r.id) AS nb_participants
    FROM activities a
    JOIN users u ON u.id = a.id_organisateur
    LEFT JOIN registrations r ON r.activity_id = a.id
    WHERE a.statut='actif' AND a.date_activite >= CURDATE()
    GROUP BY a.id
    ORDER BY a.date_activite ASC
    LIMIT 6
");
$a_venir = $stmt->fetchAll();

// Suggestions (aléatoire ou basées sur historique si connecté)
$suggestions = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom, u.nom,
               COUNT(r.id) AS nb_participants,
               (SELECT COUNT(*) FROM registrations WHERE activity_id=a.id AND user_id=:uid) AS deja_inscrit
        FROM activities a
        JOIN users u ON u.id = a.id_organisateur
        LEFT JOIN registrations r ON r.activity_id = a.id
        WHERE a.statut='actif' AND a.date_activite >= CURDATE()
          AND a.id_organisateur != :uid2
        GROUP BY a.id
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->execute([':uid' => $_SESSION['user_id'], ':uid2' => $_SESSION['user_id']]);
    $suggestions = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("
        SELECT a.*, u.prenom, u.nom, COUNT(r.id) AS nb_participants
        FROM activities a JOIN users u ON u.id=a.id_organisateur
        LEFT JOIN registrations r ON r.activity_id=a.id
        WHERE a.statut='actif' AND a.date_activite >= CURDATE()
        GROUP BY a.id ORDER BY RAND() LIMIT 3
    ");
    $suggestions = $stmt->fetchAll();
}

$CATS = ['Sport'=>'⚽','Culture'=>'🎭','Musique'=>'🎵','Jeux'=>'🎮','Nature'=>'🌿','Sorties'=>'🎉','Food'=>'🍕','Autre'=>'🔖'];

function carte_activite(array $a, array $CATS, bool $show_join = true): string {
    $cat   = htmlspecialchars($a['categorie'] ?? 'Autre');
    $ico   = $CATS[$a['categorie']] ?? '🔖';
    $titre = htmlspecialchars($a['titre']);
    $lieu  = htmlspecialchars($a['lieu']);
    $date  = date('d/m/Y', strtotime($a['date_activite']));
    $heure = substr($a['heure_debut'] ?? '', 0, 5);
    $org   = htmlspecialchars($a['prenom'].' '.$a['nom']);
    $nb    = (int)($a['nb_participants'] ?? 0);
    $max   = $a['nb_max_participants'] ? (int)$a['nb_max_participants'] : null;
    $full  = $max && $nb >= $max;
    $img   = $a['image_url'] ? htmlspecialchars($a['image_url']) : '';
    $id    = (int)$a['id'];

    $badge = $full ? '<span class="badge badge-danger">Complet</span>' : '';
    $imgHtml = $img ? "<img src=\"$img\" alt=\"\" class=\"card-img\">" : "<div class=\"card-img-placeholder\">$ico</div>";

    return "
    <div class='act-card'>
      <a href='activite.php?id=$id'>$imgHtml</a>
      <div class='act-card-body'>
        <div class='act-card-meta'><span class='cat-badge'>$ico $cat</span> $badge</div>
        <h3><a href='activite.php?id=$id'>$titre</a></h3>
        <p class='act-card-info'>📍 $lieu &nbsp;·&nbsp; 📅 $date $heure</p>
        <p class='act-card-org'>par $org</p>
        <div class='act-card-footer'>
          <span>👥 $nb" . ($max ? "/$max" : '') . "</span>
          " . ($show_join && !$full ? "<a href='activite.php?id=$id' class='btn btn-sm btn-primary'>Voir</a>" : '') . "
        </div>
      </div>
    </div>";
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Match Moov – Accueil</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>

<?php if (isset($_GET['welcome'])): ?>
<div class="toast toast-success">🎉 Bienvenue sur Match Moov ! Explore les activités près de toi.</div>
<?php endif; ?>

<div class="container">
  <main>

    <!-- HERO -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <section class="hero">
      <div class="hero-content">
        <h1>Trouve des activités, <span class="hero-accent">rencontre des gens.</span></h1>
        <p>Match Moov connecte les étudiants autour d'activités locales : sport, culture, sorties et bien plus.</p>
        <div class="hero-cta">
          <a href="register.php" class="btn btn-primary btn-lg">Rejoindre gratuitement</a>
          <a href="recherche.php" class="btn btn-outline btn-lg">Explorer les activités</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- SUGGESTIONS -->
    <?php if (!empty($suggestions)): ?>
    <section class="home-section">
      <div class="section-header">
        <h2>✨ Suggestions pour toi</h2>
        <a href="recherche.php" class="section-more">Voir tout →</a>
      </div>
      <div class="cards-grid">
        <?php foreach ($suggestions as $a): echo carte_activite($a, $CATS); endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- POPULAIRES -->
    <?php if (!empty($populaires)): ?>
    <section class="home-section">
      <div class="section-header">
        <h2>🔥 Populaires en ce moment</h2>
        <a href="recherche.php?sort=popularite" class="section-more">Voir tout →</a>
      </div>
      <div class="cards-grid">
        <?php foreach ($populaires as $a): echo carte_activite($a, $CATS); endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- À VENIR -->
    <?php if (!empty($a_venir)): ?>
    <section class="home-section">
      <div class="section-header">
        <h2>📅 À venir</h2>
        <a href="recherche.php?sort=date" class="section-more">Voir tout →</a>
      </div>
      <div class="cards-grid">
        <?php foreach ($a_venir as $a): echo carte_activite($a, $CATS); endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if (empty($populaires) && empty($a_venir)): ?>
    <section class="home-section" style="text-align:center;padding:60px 20px;">
      <p style="font-size:3rem;">🎯</p>
      <h2>Sois le premier à créer une activité !</h2>
      <p style="margin:16px 0;color:var(--mm-grey);">Aucune activité pour l'instant. Lance la communauté.</p>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="cree_activite.php" class="btn btn-primary btn-lg">Créer une activité</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-primary btn-lg">S'inscrire</a>
      <?php endif; ?>
    </section>
    <?php endif; ?>

  </main>
</div>

<style>
.hero{background:linear-gradient(135deg,var(--mm-blue) 0%,var(--mm-blue-xdark) 100%);color:#fff;padding:64px 24px;text-align:center;border-radius:0 0 var(--mm-radius) var(--mm-radius);}
.hero h1{color:#fff;font-size:clamp(1.8rem,4vw,2.8rem);}
.hero-accent{color:var(--mm-yellow);}
.hero p{margin:16px 0 32px;opacity:.9;font-size:1.1rem;}
.hero-cta{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
.home-section{margin:40px 0;}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.section-more{color:var(--mm-blue);font-size:.9rem;}
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;}
.act-card{background:#fff;border-radius:var(--mm-radius);box-shadow:var(--mm-shadow);overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;}
.act-card:hover{transform:translateY(-3px);box-shadow:var(--mm-shadow-md);}
.card-img{width:100%;height:160px;object-fit:cover;}
.card-img-placeholder{width:100%;height:160px;background:var(--mm-blue-light);display:flex;align-items:center;justify-content:center;font-size:3rem;}
.act-card-body{padding:16px;flex:1;display:flex;flex-direction:column;gap:8px;}
.act-card-meta{display:flex;gap:8px;align-items:center;}
.cat-badge{background:var(--mm-blue-light);color:var(--mm-blue-dark);padding:2px 10px;border-radius:999px;font-size:.8rem;font-weight:600;}
.badge-danger{background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:700;}
.act-card-body h3{font-size:1rem;margin:0;}
.act-card-body h3 a{color:var(--mm-dark);text-decoration:none;}
.act-card-body h3 a:hover{color:var(--mm-blue);}
.act-card-info{font-size:.83rem;color:var(--mm-grey);}
.act-card-org{font-size:.8rem;color:var(--mm-grey);font-style:italic;}
.act-card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:8px;border-top:1px solid var(--mm-grey-light);}
.btn-sm{padding:5px 14px;font-size:.83rem;}
.toast{position:fixed;top:80px;right:20px;background:var(--mm-success);color:#fff;padding:12px 24px;border-radius:var(--mm-radius-sm);box-shadow:var(--mm-shadow-md);z-index:999;animation:fadeIn .3s;}
.toast-success{background:var(--mm-success);}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:none}}
</style>
<script>
const t=document.querySelector('.toast');
if(t)setTimeout(()=>t.style.display='none',4000);
</script>
</body>
</html>