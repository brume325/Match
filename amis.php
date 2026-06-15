<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

// Actions
$action = $_GET['action'] ?? '';
$cible  = (int)($_GET['id'] ?? 0);

if ($action === 'ajouter' && $cible > 0 && $cible !== $uid) {
    try {
        $pdo->prepare("INSERT IGNORE INTO ami (demandeur_id,recepteur_id) VALUES (:d,:r)")
            ->execute([':d'=>$uid,':r'=>$cible]);
        // Notification
        $pdo->prepare("INSERT INTO notification (user_id,type,message,lien) VALUES (:uid,'ami','Nouvelle demande d\'ami reçue.','amis.php')")
            ->execute([':uid'=>$cible]);
    } catch (PDOException $e) {}
}
if ($action === 'accepter' && $cible > 0) {
    $pdo->prepare("UPDATE ami SET statut='accepte' WHERE demandeur_id=:d AND recepteur_id=:r")
        ->execute([':d'=>$cible,':r'=>$uid]);
    ajouter_points($uid, 5, $pdo);
    require_once 'badges.php'; attribuer_badges($uid, $pdo);
}
if ($action === 'refuser' && $cible > 0) {
    $pdo->prepare("UPDATE ami SET statut='refuse' WHERE demandeur_id=:d AND recepteur_id=:r")
        ->execute([':d'=>$cible,':r'=>$uid]);
}
if ($action === 'supprimer' && $cible > 0) {
    $pdo->prepare("DELETE FROM ami WHERE (demandeur_id=:a AND recepteur_id=:b) OR (demandeur_id=:b2 AND recepteur_id=:a2)")
        ->execute([':a'=>$uid,':b'=>$cible,':b2'=>$cible,':a2'=>$uid]);
}
if (in_array($action,['ajouter','accepter','refuser','supprimer'])) {
    header('Location: amis.php'); exit;
}

// Mes amis
$stmt = $pdo->prepare("
    SELECT u.id,u.prenom,u.nom,u.avatar,u.classe,a.statut,a.demandeur_id
    FROM ami a
    JOIN users u ON u.id = IF(a.demandeur_id=:uid, a.recepteur_id, a.demandeur_id)
    WHERE (a.demandeur_id=:uid2 OR a.recepteur_id=:uid3)
      AND a.statut IN ('accepte','en_attente')
    ORDER BY a.statut DESC, u.prenom ASC
");
$stmt->execute([':uid'=>$uid,':uid2'=>$uid,':uid3'=>$uid]);
$amis = $stmt->fetchAll();

// Recherche
$search = trim($_GET['search'] ?? '');
$trouvés = [];
if ($search !== '') {
    $s = $pdo->prepare("SELECT id,prenom,nom,avatar,classe FROM users WHERE (prenom LIKE :q OR nom LIKE :q2) AND id!=:uid AND actif=1 LIMIT 10");
    $s->execute([':q'=>"%$search%",':q2'=>"%$search%",':uid'=>$uid]);
    $trouvés = $s->fetchAll();
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Amis – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>
    <h1 style="margin:24px 0 20px;">👥 Mes amis</h1>

    <!-- Recherche -->
    <section class="home-section">
      <h2>Trouver quelqu'un</h2>
      <form method="get" action="amis.php" style="display:flex;gap:8px;margin-bottom:16px;">
        <input type="text" name="search" placeholder="Prénom ou nom…" value="<?= htmlspecialchars($search) ?>" class="filter-select" style="flex:1;">
        <button class="btn btn-primary">Chercher</button>
      </form>
      <?php if (!empty($trouvés)): ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php foreach ($trouvés as $u): ?>
            <div class="user-row">
              <?php if ($u['avatar']): ?><img src="<?= htmlspecialchars($u['avatar']) ?>" class="conv-avatar" alt=""><?php else: ?><div class="conv-init"><?= strtoupper($u['prenom'][0].$u['nom'][0]) ?></div><?php endif; ?>
              <div style="flex:1;">
                <a href="profil.php?id=<?= $u['id'] ?>"><strong><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></strong></a>
                <?php if ($u['classe']): ?><span style="font-size:.8rem;color:var(--mm-grey);"> · <?= htmlspecialchars($u['classe']) ?></span><?php endif; ?>
              </div>
              <a href="amis.php?action=ajouter&id=<?= $u['id'] ?>" class="btn btn-primary btn-sm">Ajouter</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($search): ?>
        <p style="color:var(--mm-grey);">Aucun résultat pour "<?= htmlspecialchars($search) ?>".</p>
      <?php endif; ?>
    </section>

    <!-- Demandes reçues -->
    <?php $demandes = array_filter($amis, fn($a) => $a['statut']==='en_attente' && $a['demandeur_id'] !== $uid); ?>
    <?php if (!empty($demandes)): ?>
    <section class="home-section">
      <h2>⏳ Demandes reçues (<?= count($demandes) ?>)</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($demandes as $a): ?>
          <div class="user-row">
            <?php if ($a['avatar']): ?><img src="<?= htmlspecialchars($a['avatar']) ?>" class="conv-avatar" alt=""><?php else: ?><div class="conv-init"><?= strtoupper($a['prenom'][0].$a['nom'][0]) ?></div><?php endif; ?>
            <div style="flex:1;"><a href="profil.php?id=<?= $a['id'] ?>"><strong><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></strong></a></div>
            <a href="amis.php?action=accepter&id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">Accepter</a>
            <a href="amis.php?action=refuser&id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">Refuser</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Liste amis -->
    <?php $accepted = array_filter($amis, fn($a) => $a['statut']==='accepte'); ?>
    <section class="home-section">
      <h2>✅ Mes amis (<?= count($accepted) ?>)</h2>
      <?php if (empty($accepted)): ?>
        <p style="color:var(--mm-grey);">Tu n'as pas encore d'amis. Utilise la recherche ci-dessus !</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php foreach ($accepted as $a): ?>
            <div class="user-row">
              <?php if ($a['avatar']): ?><img src="<?= htmlspecialchars($a['avatar']) ?>" class="conv-avatar" alt=""><?php else: ?><div class="conv-init"><?= strtoupper($a['prenom'][0].$a['nom'][0]) ?></div><?php endif; ?>
              <div style="flex:1;"><a href="profil.php?id=<?= $a['id'] ?>"><strong><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></strong></a></div>
              <a href="messagerie.php?user=<?= $a['id'] ?>" class="btn btn-outline btn-sm">💬</a>
              <a href="amis.php?action=supprimer&id=<?= $a['id'] ?>" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;" onclick="return confirm('Supprimer cet ami ?')">×</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>
<style>
.user-row{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-xs);padding:10px 14px;}
.conv-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;}
.conv-init{width:40px;height:40px;border-radius:50%;background:var(--mm-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
</style>
</body>
</html>