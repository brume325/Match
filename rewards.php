<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT points FROM users WHERE id=:uid');
$stmt->execute([':uid'=>$uid]);
$points = (int)$stmt->fetchColumn();

// Tous les badges
$stmt = $pdo->query('SELECT * FROM badges ORDER BY seuil_points ASC');
$all_badges = $stmt->fetchAll();

// Badges obtenus
$stmt = $pdo->prepare('SELECT badge_id, obtenu_le FROM user_badges WHERE user_id=:uid');
$stmt->execute([':uid'=>$uid]);
$obtained = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE user_id=:uid');
$stmt->execute([':uid'=>$uid]); $nb_part = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM activities WHERE id_organisateur=:uid');
$stmt->execute([':uid'=>$uid]); $nb_crea = (int)$stmt->fetchColumn();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Récompenses – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>
    <h1 style="margin:24px 0 8px;">🏆 Mes récompenses</h1>

    <!-- Points -->
    <div class="rewards-hero">
      <div class="points-display">
        <span class="points-val"><?= $points ?></span>
        <span class="points-lbl">points</span>
      </div>
      <div class="rewards-stats">
        <div><strong><?= $nb_part ?></strong><br><small>participations</small></div>
        <div><strong><?= $nb_crea ?></strong><br><small>activités créées</small></div>
        <div><strong><?= count($obtained) ?></strong><br><small>badges</small></div>
      </div>
    </div>

    <!-- Barres de progression -->
    <section class="rewards-section">
      <h2>📈 Progression</h2>
      <div class="progress-list">
        <div class="progress-item">
          <span>Participation (<?= $nb_part ?>/10 pour Fidèle)</span>
          <div class="progress-bar"><div style="width:<?= min(100,($nb_part/10)*100) ?>%"></div></div>
        </div>
        <div class="progress-item">
          <span>Création (<?= $nb_crea ?>/1 pour Organisateur)</span>
          <div class="progress-bar"><div style="width:<?= min(100,($nb_crea/1)*100) ?>%"></div></div>
        </div>
        <div class="progress-item">
          <span>Points (<?= $points ?>/100 pour Fidèle)</span>
          <div class="progress-bar"><div style="width:<?= min(100,($points/100)*100) ?>%"></div></div>
        </div>
      </div>
    </section>

    <!-- Grille des badges -->
    <section class="rewards-section">
      <h2>🏅 Tous les badges</h2>
      <div class="badges-full-grid">
        <?php foreach ($all_badges as $b): ?>
          <?php $have = isset($obtained[$b['id']]); ?>
          <div class="badge-card <?= $have ? 'unlocked' : 'locked' ?>">
            <div class="badge-ico"><?= htmlspecialchars($b['icone']) ?></div>
            <div class="badge-nom"><?= htmlspecialchars($b['nom']) ?></div>
            <div class="badge-desc"><?= htmlspecialchars($b['description']) ?></div>
            <?php if ($have): ?>
              <div class="badge-date">Obtenu le <?= date('d/m/Y', strtotime($obtained[$b['id']])) ?></div>
            <?php else: ?>
              <div class="badge-date locked-lbl">🔒 <?= $b['seuil_points'] ?> pts requis</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Comment gagner des points -->
    <section class="rewards-section">
      <h2>💡 Comment gagner des points ?</h2>
      <div class="points-guide">
        <div class="points-guide-item"><span>➕ Créer une activité</span><strong>+20 pts</strong></div>
        <div class="points-guide-item"><span>✋ Participer à une activité</span><strong>+10 pts</strong></div>
        <div class="points-guide-item"><span>👥 Ajouter un ami</span><strong>+5 pts</strong></div>
      </div>
    </section>
  </main>
</div>
<style>
.rewards-hero{background:linear-gradient(135deg,var(--mm-blue),var(--mm-blue-xdark));border-radius:var(--mm-radius);padding:28px;display:flex;align-items:center;gap:32px;flex-wrap:wrap;margin-bottom:28px;}
.points-display{display:flex;flex-direction:column;align-items:center;}
.points-val{font-size:3.5rem;font-weight:800;color:#fff;line-height:1;}
.points-lbl{font-size:1rem;color:var(--mm-yellow);font-weight:600;text-transform:uppercase;}
.rewards-stats{display:flex;gap:24px;color:#fff;text-align:center;}
.rewards-stats div{background:rgba(255,255,255,.15);padding:12px 20px;border-radius:var(--mm-radius-sm);}
.rewards-section{margin-bottom:28px;}
.rewards-section h2{font-size:1.1rem;margin-bottom:14px;}
.progress-list{display:flex;flex-direction:column;gap:12px;}
.progress-item{font-size:.85rem;}
.progress-bar{background:var(--mm-grey-light);border-radius:999px;height:8px;margin-top:4px;}
.progress-bar div{background:var(--mm-blue);height:8px;border-radius:999px;transition:width .5s;}
.badges-full-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;}
.badge-card{background:#fff;border-radius:var(--mm-radius-sm);box-shadow:var(--mm-shadow-sm);padding:20px;text-align:center;border:2px solid transparent;}
.badge-card.unlocked{border-color:var(--mm-blue);background:var(--mm-blue-light);}
.badge-card.locked{opacity:.55;filter:grayscale(60%);}
.badge-ico{font-size:2.5rem;margin-bottom:8px;}
.badge-nom{font-weight:700;margin-bottom:4px;}
.badge-desc{font-size:.78rem;color:var(--mm-grey);margin-bottom:8px;}
.badge-date{font-size:.75rem;color:var(--mm-grey);}
.locked-lbl{color:#9ca3af;}
.points-guide{display:flex;flex-direction:column;gap:8px;}
.points-guide-item{background:#fff;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-xs);padding:12px 16px;display:flex;justify-content:space-between;align-items:center;}
</style>
</body>
</html>