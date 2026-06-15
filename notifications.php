<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

// Marquer tout comme lu
if ($_GET['action'] ?? '' === 'tout_lire') {
    $pdo->prepare('UPDATE notification SET est_lue=1 WHERE user_id=:uid')->execute([':uid'=>$uid]);
    header('Location: notifications.php'); exit;
}

$stmt = $pdo->prepare('SELECT * FROM notification WHERE user_id=:uid ORDER BY created_at DESC LIMIT 50');
$stmt->execute([':uid'=>$uid]);
$notifs = $stmt->fetchAll();

$nb_non_lues = count(array_filter($notifs, fn($n) => !$n['est_lue']));

// Marquer celles affichées comme lues
$pdo->prepare('UPDATE notification SET est_lue=1 WHERE user_id=:uid AND est_lue=0')->execute([':uid'=>$uid]);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notifications – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>
    <div style="display:flex;align-items:center;justify-content:space-between;margin:24px 0 20px;">
      <h1>🔔 Notifications <?php if ($nb_non_lues): ?><span style="background:var(--mm-blue);color:#fff;border-radius:999px;padding:2px 10px;font-size:.7rem;"><?= $nb_non_lues ?></span><?php endif; ?></h1>
    </div>
    <?php if (empty($notifs)): ?>
      <p style="color:var(--mm-grey);text-align:center;padding:40px 0;">Aucune notification pour l'instant.</p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($notifs as $n): ?>
          <div class="notif-item <?= !$n['est_lue'] ? 'unread' : '' ?>">
            <div style="flex:1;">
              <p><?= htmlspecialchars($n['message']) ?></p>
              <small><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
            </div>
            <?php if ($n['lien']): ?><a href="<?= htmlspecialchars($n['lien']) ?>" class="btn btn-outline btn-sm">Voir</a><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
<style>
.notif-item{background:#fff;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-xs);padding:14px 16px;display:flex;align-items:center;gap:12px;}
.notif-item.unread{background:var(--mm-blue-light);border-color:var(--mm-blue-mid);}
.notif-item p{margin:0;font-size:.9rem;}
.notif-item small{font-size:.75rem;color:var(--mm-grey);}
</style>
</body>
</html>