<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid = (int)$_SESSION['user_id'];

// Traitement envoi message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenu = trim($_POST['contenu'] ?? '');
    $dest_id = (int)($_POST['dest_id'] ?? 0);
    if ($contenu !== '' && $dest_id > 0) {
        $pdo->prepare('INSERT INTO messages (id_expediteur,id_destinataire,contenu) VALUES (:exp,:dest,:c)')
            ->execute([':exp'=>$uid,':dest'=>$dest_id,':c'=>$contenu]);
        // Marquer comme lu les messages de l'autre vers moi
        $pdo->prepare('UPDATE messages SET lu=1 WHERE id_expediteur=:dest AND id_destinataire=:uid AND lu=0')
            ->execute([':dest'=>$dest_id,':uid'=>$uid]);
    }
    header('Location: messagerie.php?user='.$dest_id);
    exit;
}

// Conversation sélectionnée
$with = (int)($_GET['user'] ?? 0);
$conv_user = null;
if ($with > 0) {
    $s = $pdo->prepare('SELECT id,prenom,nom,avatar FROM users WHERE id=:id');
    $s->execute([':id'=>$with]);
    $conv_user = $s->fetch();
    // Marquer comme lus
    $pdo->prepare('UPDATE messages SET lu=1 WHERE id_expediteur=:dest AND id_destinataire=:uid AND lu=0')
        ->execute([':dest'=>$with,':uid'=>$uid]);
}

// Liste des conversations (amis et personnes avec qui j'ai échangé)
$convs = $pdo->prepare("
    SELECT u.id, u.prenom, u.nom, u.avatar,
           (SELECT contenu FROM messages
            WHERE (id_expediteur=:uid AND id_destinataire=u.id)
               OR (id_expediteur=u.id AND id_destinataire=:uid2)
            ORDER BY created_at DESC LIMIT 1) AS last_msg,
           (SELECT COUNT(*) FROM messages
            WHERE id_expediteur=u.id AND id_destinataire=:uid3 AND lu=0) AS unread
    FROM users u
    WHERE u.id != :uid4
      AND u.actif = 1
      AND EXISTS (
        SELECT 1 FROM messages
        WHERE (id_expediteur=:uid5 AND id_destinataire=u.id)
           OR (id_expediteur=u.id AND id_destinataire=:uid6)
      )
    ORDER BY (SELECT created_at FROM messages
              WHERE (id_expediteur=:uid7 AND id_destinataire=u.id)
                 OR (id_expediteur=u.id AND id_destinataire=:uid8)
              ORDER BY created_at DESC LIMIT 1) DESC
");
$convs->execute([':uid'=>$uid,':uid2'=>$uid,':uid3'=>$uid,':uid4'=>$uid,':uid5'=>$uid,':uid6'=>$uid,':uid7'=>$uid,':uid8'=>$uid]);
$conversations = $convs->fetchAll();

// Messages de la conversation
$messages = [];
if ($with > 0) {
    $s = $pdo->prepare("SELECT m.*,u.prenom,u.nom FROM messages m JOIN users u ON u.id=m.id_expediteur
                         WHERE (id_expediteur=:uid AND id_destinataire=:dest)
                            OR (id_expediteur=:dest2 AND id_destinataire=:uid2)
                         ORDER BY m.created_at ASC LIMIT 100");
    $s->execute([':uid'=>$uid,':dest'=>$with,':dest2'=>$with,':uid2'=>$uid]);
    $messages = $s->fetchAll();
}

// Chercher un utilisateur
$search = trim($_GET['search'] ?? '');
$search_results = [];
if ($search !== '') {
    $s = $pdo->prepare("SELECT id,prenom,nom,avatar FROM users WHERE (prenom LIKE :q OR nom LIKE :q2 OR email LIKE :q3) AND id!=:uid AND actif=1 LIMIT 10");
    $s->execute([':q'=>"%$search%",':q2'=>"%$search%",':q3'=>"%$search%",':uid'=>$uid]);
    $search_results = $s->fetchAll();
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Messagerie – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="msg-layout">

  <!-- PANNEAU GAUCHE : conversations -->
  <aside class="msg-sidebar">
    <div class="msg-sidebar-header">
      <h2>💬 Messages</h2>
      <form method="get" action="messagerie.php" style="display:flex;gap:6px;margin-top:10px;">
        <input type="text" name="search" placeholder="Chercher un utilisateur…" value="<?= htmlspecialchars($search) ?>" class="filter-select" style="flex:1;">
        <button class="btn btn-primary btn-sm">🔍</button>
      </form>
    </div>

    <?php if (!empty($search_results)): ?>
      <div class="conv-list">
        <p style="font-size:.75rem;color:var(--mm-grey);padding:8px 16px;">Résultats :</p>
        <?php foreach ($search_results as $u): ?>
          <a href="messagerie.php?user=<?= $u['id'] ?>" class="conv-item<?= $with===$u['id']?' active':'' ?>">
            <?php if ($u['avatar']): ?><img src="<?= htmlspecialchars($u['avatar']) ?>" class="conv-avatar" alt=""><?php else: ?><div class="conv-init"><?= strtoupper($u['prenom'][0].$u['nom'][0]) ?></div><?php endif; ?>
            <div><strong><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></strong></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php elseif (!empty($conversations)): ?>
      <div class="conv-list">
        <?php foreach ($conversations as $c): ?>
          <a href="messagerie.php?user=<?= $c['id'] ?>" class="conv-item<?= $with===$c['id']?' active':'' ?>">
            <?php if ($c['avatar']): ?><img src="<?= htmlspecialchars($c['avatar']) ?>" class="conv-avatar" alt=""><?php else: ?><div class="conv-init"><?= strtoupper($c['prenom'][0].$c['nom'][0]) ?></div><?php endif; ?>
            <div style="flex:1;min-width:0;">
              <div style="display:flex;justify-content:space-between;">
                <strong><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></strong>
                <?php if ($c['unread'] > 0): ?><span class="notif-badge"><?= $c['unread'] ?></span><?php endif; ?>
              </div>
              <?php if ($c['last_msg']): ?><p class="conv-preview"><?= htmlspecialchars(mb_substr($c['last_msg'],0,40)) ?>…</p><?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="padding:20px;color:var(--mm-grey);font-size:.85rem;">Aucune conversation. Cherche un utilisateur pour lui écrire.</p>
    <?php endif; ?>
  </aside>

  <!-- PANNEAU DROIT : chat -->
  <main class="msg-main">
    <?php if ($conv_user): ?>
      <div class="msg-header">
        <?php if ($conv_user['avatar']): ?><img src="<?= htmlspecialchars($conv_user['avatar']) ?>" class="conv-avatar" alt=""><?php else: ?><div class="conv-init"><?= strtoupper($conv_user['prenom'][0].$conv_user['nom'][0]) ?></div><?php endif; ?>
        <div>
          <strong><?= htmlspecialchars($conv_user['prenom'].' '.$conv_user['nom']) ?></strong><br>
          <a href="profil.php?id=<?= $with ?>" style="font-size:.78rem;">Voir le profil</a>
        </div>
      </div>
      <div class="msg-body" id="msgBody">
        <?php foreach ($messages as $m): ?>
          <div class="msg-bubble <?= $m['id_expediteur']===$uid?'mine':'' ?>">
            <p><?= nl2br(htmlspecialchars($m['contenu'])) ?></p>
            <span class="msg-ts"><?= date('d/m H:i', strtotime($m['created_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" action="messagerie.php" class="msg-form">
        <input type="hidden" name="dest_id" value="<?= $with ?>">
        <textarea name="contenu" placeholder="Écrire un message…" required rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
        <button class="btn btn-primary">Envoyer</button>
      </form>
    <?php else: ?>
      <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--mm-grey);">
        <div style="text-align:center;"><p style="font-size:3rem;">💬</p><p>Sélectionne une conversation ou cherche quelqu'un.</p></div>
      </div>
    <?php endif; ?>
  </main>
</div>
<style>
.msg-layout{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - var(--topbar-h));overflow:hidden;}
@media(max-width:680px){.msg-layout{grid-template-columns:1fr;}<?= $with?'.msg-sidebar{display:none;}':'.msg-main{display:none;}' ?>}
.msg-sidebar{border-right:1px solid var(--mm-grey-light);display:flex;flex-direction:column;overflow:hidden;}
.msg-sidebar-header{padding:16px;border-bottom:1px solid var(--mm-grey-light);}
.msg-sidebar-header h2{font-size:1rem;margin-bottom:0;}
.conv-list{overflow-y:auto;flex:1;}
.conv-item{display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:var(--mm-dark);border-bottom:1px solid var(--mm-grey-light);}
.conv-item:hover,.conv-item.active{background:var(--mm-blue-light);}
.conv-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;}
.conv-init{width:40px;height:40px;border-radius:50%;background:var(--mm-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
.conv-preview{font-size:.78rem;color:var(--mm-grey);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.msg-main{display:flex;flex-direction:column;overflow:hidden;}
.msg-header{padding:12px 16px;border-bottom:1px solid var(--mm-grey-light);display:flex;align-items:center;gap:10px;background:#fff;}
.msg-body{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px;}
.msg-bubble{max-width:70%;background:var(--mm-grey-xlight);border-radius:var(--mm-radius-sm);padding:10px 14px;}
.msg-bubble.mine{align-self:flex-end;background:var(--mm-blue-light);}
.msg-bubble p{margin:0;font-size:.9rem;}
.msg-ts{font-size:.7rem;color:var(--mm-grey);display:block;margin-top:4px;}
.msg-form{padding:12px 16px;border-top:1px solid var(--mm-grey-light);display:flex;gap:8px;background:#fff;}
.msg-form textarea{flex:1;padding:8px 12px;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-xs);resize:none;font-family:inherit;}
</style>
<script>const b=document.getElementById('msgBody');if(b)b.scrollTop=b.scrollHeight;</script>
</body>
</html>