<?php
session_start();
require_once 'config.php';

$logged = isset($_SESSION['user_id']);
$uid    = (int)($_GET['id'] ?? ($logged ? $_SESSION['user_id'] : 0));
if (!$uid) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare('SELECT id,prenom,nom,email,age,classe,organisation,avatar,points,created_at FROM users WHERE id=:uid AND actif=1');
$stmt->execute([':uid' => $uid]);
$user = $stmt->fetch();
if (!$user) { header('Location: index.php'); exit; }

// Activités créées
$stmt = $pdo->prepare("SELECT a.*, COUNT(r.id) AS nb_p FROM activities a LEFT JOIN registrations r ON r.activity_id=a.id WHERE a.id_organisateur=:uid GROUP BY a.id ORDER BY a.date_activite DESC LIMIT 10");
$stmt->execute([':uid' => $uid]);
$creees = $stmt->fetchAll();

// Activités participées
$stmt = $pdo->prepare("SELECT a.*, u.prenom, u.nom FROM registrations r JOIN activities a ON a.id=r.activity_id JOIN users u ON u.id=a.id_organisateur WHERE r.user_id=:uid ORDER BY a.date_activite DESC LIMIT 10");
$stmt->execute([':uid' => $uid]);
$participees = $stmt->fetchAll();

// Badges
$stmt = $pdo->prepare("SELECT b.* FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=:uid");
$stmt->execute([':uid' => $uid]);
$badges = $stmt->fetchAll();

$is_own = $logged && (int)$_SESSION['user_id'] === $uid;

// Statut ami
$statut_ami = null;
if ($logged && !$is_own) {
    $myid = (int)$_SESSION['user_id'];
    $s = $pdo->prepare("SELECT statut, demandeur_id FROM ami WHERE (demandeur_id=:a AND recepteur_id=:b) OR (demandeur_id=:b2 AND recepteur_id=:a2)");
    $s->execute([':a'=>$myid,':b'=>$uid,':b2'=>$uid,':a2'=>$myid]);
    $row = $s->fetch();
    if ($row) $statut_ami = $row['statut'];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?> – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>

    <!-- CARTE PROFIL -->
    <div class="profil-card">
      <?php if ($user['avatar']): ?>
        <img src="<?= htmlspecialchars($user['avatar']) ?>" class="profil-avatar" alt="Avatar">
      <?php else: ?>
        <div class="profil-avatar-init"><?= strtoupper($user['prenom'][0].$user['nom'][0]) ?></div>
      <?php endif; ?>
      <div class="profil-info">
        <h1><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></h1>
        <?php if ($user['classe']): ?><p class="profil-sub">📚 <?= htmlspecialchars($user['classe']) ?><?= $user['organisation'] ? ' · '.$user['organisation'] : '' ?></p><?php endif; ?>
        <?php if ($user['age']): ?><p class="profil-sub">🎂 <?= (int)$user['age'] ?> ans</p><?php endif; ?>
        <p class="profil-sub">🏆 <strong><?= (int)$user['points'] ?> points</strong> · Membre depuis <?= date('M Y', strtotime($user['created_at'])) ?></p>
      </div>
      <div class="profil-actions">
        <?php if ($is_own): ?>
          <a href="parametres.php" class="btn btn-outline">⚙️ Modifier</a>
        <?php elseif ($logged): ?>
          <?php if ($statut_ami === 'accepte'): ?>
            <span class="btn btn-outline">✅ Ami</span>
            <a href="messagerie.php?user=<?= $uid ?>" class="btn btn-primary">💬 Message</a>
          <?php elseif ($statut_ami === 'en_attente'): ?>
            <span class="btn btn-outline">⏳ Demande envoyée</span>
          <?php else: ?>
            <a href="amis.php?action=ajouter&id=<?= $uid ?>" class="btn btn-primary">👥 Ajouter</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- BADGES -->
    <?php if (!empty($badges)): ?>
    <section class="profil-section">
      <h2>🏅 Badges (<?= count($badges) ?>)</h2>
      <div class="badges-grid">
        <?php foreach ($badges as $b): ?>
          <div class="badge-chip" title="<?= htmlspecialchars($b['description']) ?>">
            <span><?= htmlspecialchars($b['icone']) ?></span>
            <span><?= htmlspecialchars($b['nom']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- KPI -->
    <section class="profil-section">
      <h2>📊 Statistiques</h2>
      <div class="kpi-grid">
        <div class="kpi-card"><div class="kpi-val"><?= count($creees) ?></div><div class="kpi-lbl">Activités créées</div></div>
        <div class="kpi-card"><div class="kpi-val"><?= count($participees) ?></div><div class="kpi-lbl">Participations</div></div>
        <div class="kpi-card"><div class="kpi-val"><?= (int)$user['points'] ?></div><div class="kpi-lbl">Points</div></div>
        <div class="kpi-card"><div class="kpi-val"><?= count($badges) ?></div><div class="kpi-lbl">Badges</div></div>
      </div>
    </section>

    <!-- ACTIVITÉS CRÉÉES -->
    <?php if (!empty($creees)): ?>
    <section class="profil-section">
      <h2>🎯 Activités organisées</h2>
      <div class="history-list">
        <?php foreach ($creees as $a): ?>
          <a href="activite.php?id=<?= $a['id'] ?>" class="history-item">
            <strong><?= htmlspecialchars($a['titre']) ?></strong>
            <span><?= date('d/m/Y', strtotime($a['date_activite'])) ?> · <?= $a['nb_p'] ?> participants</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- HISTORIQUE PARTICIPATIONS -->
    <?php if (!empty($participees)): ?>
    <section class="profil-section">
      <h2>🏃 Historique des participations</h2>
      <div class="history-list">
        <?php foreach ($participees as $a): ?>
          <a href="activite.php?id=<?= $a['id'] ?>" class="history-item">
            <strong><?= htmlspecialchars($a['titre']) ?></strong>
            <span><?= date('d/m/Y', strtotime($a['date_activite'])) ?> · par <?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </main>
</div>
<style>
.profil-card{background:#fff;border-radius:var(--mm-radius);box-shadow:var(--mm-shadow);padding:28px;display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-top:24px;}
.profil-avatar{width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--mm-blue-light);}
.profil-avatar-init{width:96px;height:96px;border-radius:50%;background:var(--mm-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;flex-shrink:0;}
.profil-info{flex:1;min-width:180px;}
.profil-info h1{margin-bottom:8px;}
.profil-sub{font-size:.88rem;color:var(--mm-grey);margin-top:4px;}
.profil-actions{display:flex;flex-direction:column;gap:8px;}
.profil-section{margin-top:28px;}
.profil-section h2{font-size:1.1rem;margin-bottom:14px;}
.badges-grid{display:flex;flex-wrap:wrap;gap:10px;}
.badge-chip{background:var(--mm-blue-light);border:1px solid var(--mm-blue-mid);border-radius:999px;padding:6px 14px;display:flex;gap:6px;align-items:center;font-size:.85rem;font-weight:600;color:var(--mm-blue-dark);}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:14px;}
.kpi-card{background:#fff;border-radius:var(--mm-radius-sm);box-shadow:var(--mm-shadow-sm);padding:16px;text-align:center;}
.kpi-val{font-size:1.8rem;font-weight:800;color:var(--mm-blue);}
.kpi-lbl{font-size:.78rem;color:var(--mm-grey);margin-top:4px;}
.history-list{display:flex;flex-direction:column;gap:8px;}
.history-item{background:#fff;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-xs);padding:12px 16px;display:flex;justify-content:space-between;align-items:center;text-decoration:none;color:var(--mm-dark);}
.history-item:hover{border-color:var(--mm-blue);text-decoration:none;}
.history-item span{font-size:.8rem;color:var(--mm-grey);}
</style>
</body>
</html>