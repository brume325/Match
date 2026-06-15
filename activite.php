<?php
session_start();
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: recherche.php'); exit; }

// Charger l'activité
$stmt = $pdo->prepare("
    SELECT a.*, u.prenom, u.nom, u.avatar,
           COUNT(DISTINCT r.id) AS nb_participants
    FROM activities a
    JOIN users u ON u.id = a.id_organisateur
    LEFT JOIN registrations r ON r.activity_id = a.id
    WHERE a.id = :id
    GROUP BY a.id
");
$stmt->execute([':id' => $id]);
$act = $stmt->fetch();
if (!$act) { header('Location: recherche.php'); exit; }

$logged    = isset($_SESSION['user_id']);
$uid       = $logged ? (int)$_SESSION['user_id'] : 0;
$est_orga  = $logged && $uid === (int)$act['id_organisateur'];
$nb        = (int)$act['nb_participants'];
$max       = $act['nb_max_participants'] ? (int)$act['nb_max_participants'] : null;
$complet   = $max && $nb >= $max;

$inscrit = false;
$favori  = false;
if ($logged) {
    $s = $pdo->prepare('SELECT 1 FROM registrations WHERE user_id=:uid AND activity_id=:aid');
    $s->execute([':uid' => $uid, ':aid' => $id]);
    $inscrit = (bool)$s->fetchColumn();

    $s = $pdo->prepare('SELECT 1 FROM favori WHERE user_id=:uid AND activity_id=:aid');
    $s->execute([':uid' => $uid, ':aid' => $id]);
    $favori = (bool)$s->fetchColumn();
}

// Participants
$stmt = $pdo->prepare("SELECT u.id, u.prenom, u.nom, u.avatar FROM registrations r JOIN users u ON u.id=r.user_id WHERE r.activity_id=:aid ORDER BY r.date_inscription ASC LIMIT 20");
$stmt->execute([':aid' => $id]);
$participants = $stmt->fetchAll();

// Commentaires
$stmt = $pdo->prepare("SELECT c.*, u.prenom, u.nom, u.avatar FROM commentaire c JOIN users u ON u.id=c.user_id WHERE c.activity_id=:aid ORDER BY c.created_at ASC");
$stmt->execute([':aid' => $id]);
$commentaires = $stmt->fetchAll();

// Messages du groupe activité
$stmt = $pdo->prepare("SELECT m.*, u.prenom, u.nom FROM messages m JOIN users u ON u.id=m.id_expediteur WHERE m.id_groupe_activite=:aid ORDER BY m.created_at ASC LIMIT 50");
$stmt->execute([':aid' => $id]);
$msgs = $stmt->fetchAll();

$CATS = ['Sport'=>'⚽','Culture'=>'🎭','Musique'=>'🎵','Jeux'=>'🎮','Nature'=>'🌿','Sorties'=>'🎉','Food'=>'🍕','Autre'=>'🔖'];
$ico = $CATS[$act['categorie']] ?? '🔖';
$date = date('d/m/Y', strtotime($act['date_activite']));
$heure = substr($act['heure_debut'], 0, 5);
$msg_flash = $_GET['msg'] ?? '';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($act['titre']) ?> – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <?php if ($msg_flash): ?>
    <div class="toast toast-success"><?= htmlspecialchars($msg_flash) ?></div>
  <?php endif; ?>

  <main class="activite-layout">
    <!-- GAUCHE : Détails -->
    <section class="activite-main">
      <?php if ($act['image_url']): ?>
        <img src="<?= htmlspecialchars($act['image_url']) ?>" alt="" class="activite-cover">
      <?php else: ?>
        <div class="activite-cover-placeholder"><?= $ico ?></div>
      <?php endif; ?>

      <div class="activite-header">
        <span class="cat-badge"><?= $ico ?> <?= htmlspecialchars($act['categorie']) ?></span>
        <?php if ($complet): ?><span class="badge-danger">Complet</span><?php endif; ?>
        <?php if ($act['est_payante']): ?><span class="badge-paid">💰 Payant</span><?php else: ?><span class="badge-free">✅ Gratuit</span><?php endif; ?>
      </div>

      <h1><?= htmlspecialchars($act['titre']) ?></h1>

      <div class="activite-meta-grid">
        <div class="meta-item">📅 <strong><?= $date ?></strong> à <strong><?= $heure ?></strong><?= $act['heure_fin'] ? '–'.substr($act['heure_fin'],0,5) : '' ?></div>
        <div class="meta-item">📍 <?= htmlspecialchars($act['lieu']) ?><?= $act['ville'] ? ', '.$act['ville'] : '' ?></div>
        <div class="meta-item">👥 <?= $nb ?><?= $max ? "/$max places" : ' participants' ?></div>
        <div class="meta-item">🧑‍💼 Organisé par <strong><?= htmlspecialchars($act['prenom'].' '.$act['nom']) ?></strong></div>
      </div>

      <?php if ($act['description']): ?>
      <div class="activite-desc">
        <h2>À propos</h2>
        <p><?= nl2br(htmlspecialchars($act['description'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- ACTIONS -->
      <div class="activite-actions">
        <?php if ($logged): ?>
          <?php if ($inscrit): ?>
            <a href="desinscrire.php?id=<?= $id ?>" class="btn btn-outline" onclick="return confirm('Se désinscrire ?')">Se désinscrire</a>
          <?php elseif (!$complet): ?>
            <a href="participer.php?id=<?= $id ?>" class="btn btn-primary">✋ Participer</a>
          <?php endif; ?>
          <a href="<?= $favori ? 'remove_favori.php' : 'favori.php' ?>?id=<?= $id ?>" class="btn btn-outline">
            <?= $favori ? '💛 Retirer des favoris' : '🤍 Ajouter aux favoris' ?>
          </a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary">Se connecter pour participer</a>
        <?php endif; ?>
        <button onclick="navigator.share ? navigator.share({title:'<?= addslashes($act['titre']) ?>',url:location.href}) : (navigator.clipboard.writeText(location.href),alert('Lien copié !'))"
                class="btn btn-outline">🔗 Partager</button>
      </div>

      <!-- PARTICIPANTS -->
      <?php if (!empty($participants)): ?>
      <div class="activite-participants">
        <h2>Participants (<?= $nb ?>)</h2>
        <div class="participants-list">
          <?php foreach ($participants as $p): ?>
            <a href="profil.php?id=<?= (int)$p['id'] ?>" class="participant-chip" title="<?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?>">
              <?php if ($p['avatar']): ?>
                <img src="<?= htmlspecialchars($p['avatar']) ?>" alt="">
              <?php else: ?>
                <span class="avatar-initials"><?= strtoupper($p['prenom'][0].$p['nom'][0]) ?></span>
              <?php endif; ?>
              <span><?= htmlspecialchars($p['prenom']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- COMMENTAIRES -->
      <div class="activite-comments">
        <h2>Commentaires</h2>
        <?php foreach ($commentaires as $c): ?>
          <div class="comment">
            <strong><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></strong>
            <span class="comment-date"><?= date('d/m H:i', strtotime($c['created_at'])) ?></span>
            <?php if ($c['note']): ?><span class="comment-note"><?= str_repeat('⭐', (int)$c['note']) ?></span><?php endif; ?>
            <p><?= nl2br(htmlspecialchars($c['contenu'])) ?></p>
          </div>
        <?php endforeach; ?>
        <?php if ($logged): ?>
          <form method="post" action="add_comment.php">
            <input type="hidden" name="activity_id" value="<?= $id ?>">
            <div style="display:flex;gap:8px;margin-top:12px;">
              <select name="note" class="filter-select" style="width:auto;">
                <option value="">Note</option>
                <?php for ($i=1;$i<=5;$i++): ?><option value="<?= $i ?>"><?= str_repeat('⭐',$i) ?></option><?php endfor; ?>
              </select>
              <input type="text" name="contenu" class="filter-select" placeholder="Ton commentaire…" required style="flex:1;">
              <button class="btn btn-primary btn-sm">Envoyer</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <!-- DROITE : Chat groupe -->
    <aside class="activite-chat">
      <h2>💬 Discussion</h2>
      <div class="chat-msgs" id="chatMsgs">
        <?php foreach ($msgs as $m): ?>
          <div class="chat-msg <?= $m['id_expediteur'] === $uid ? 'mine' : '' ?>">
            <strong><?= htmlspecialchars($m['prenom']) ?></strong>
            <p><?= nl2br(htmlspecialchars($m['contenu'])) ?></p>
            <span class="chat-ts"><?= date('d/m H:i', strtotime($m['created_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($logged && $inscrit): ?>
        <form method="post" action="send_message.php" class="chat-form">
          <input type="hidden" name="groupe_id" value="<?= $id ?>">
          <input type="text" name="contenu" placeholder="Écrire un message…" required>
          <button class="btn btn-primary btn-sm">→</button>
        </form>
      <?php elseif ($logged): ?>
        <p style="font-size:.8rem;color:var(--mm-grey);margin-top:8px;">Inscris-toi pour rejoindre la discussion.</p>
      <?php endif; ?>
    </aside>
  </main>
</div>

<style>
.activite-layout{display:grid;grid-template-columns:1fr 340px;gap:28px;margin-top:24px;}
@media(max-width:860px){.activite-layout{grid-template-columns:1fr;}}
.activite-main{display:flex;flex-direction:column;gap:20px;}
.activite-cover{width:100%;height:280px;object-fit:cover;border-radius:var(--mm-radius);}
.activite-cover-placeholder{width:100%;height:200px;background:var(--mm-blue-light);border-radius:var(--mm-radius);display:flex;align-items:center;justify-content:center;font-size:5rem;}
.activite-header{display:flex;gap:8px;flex-wrap:wrap;}
.cat-badge,.badge-danger,.badge-free,.badge-paid{padding:3px 12px;border-radius:999px;font-size:.8rem;font-weight:700;}
.cat-badge{background:var(--mm-blue-light);color:var(--mm-blue);}
.badge-danger{background:#fee2e2;color:#dc2626;}
.badge-free{background:#dcfce7;color:#15803d;}
.badge-paid{background:#fef9c3;color:#854d0e;}
.activite-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;background:var(--mm-grey-xlight);padding:16px;border-radius:var(--mm-radius-sm);}
.meta-item{font-size:.9rem;}
.activite-desc h2,.activite-participants h2,.activite-comments h2{font-size:1.1rem;margin-bottom:12px;}
.activite-actions{display:flex;gap:10px;flex-wrap:wrap;}
.participants-list{display:flex;flex-wrap:wrap;gap:8px;}
.participant-chip{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid var(--mm-grey-light);border-radius:999px;padding:4px 12px 4px 4px;font-size:.83rem;text-decoration:none;color:var(--mm-dark);}
.participant-chip img{width:28px;height:28px;border-radius:50%;object-fit:cover;}
.avatar-initials{width:28px;height:28px;border-radius:50%;background:var(--mm-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;}
.comment{background:#fff;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-sm);padding:12px;margin-bottom:10px;}
.comment-date{font-size:.75rem;color:var(--mm-grey);margin-left:8px;}
.comment-note{margin-left:8px;}
.activite-chat{background:#fff;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius);padding:16px;display:flex;flex-direction:column;height:fit-content;position:sticky;top:80px;}
.activite-chat h2{font-size:1rem;margin-bottom:12px;}
.chat-msgs{display:flex;flex-direction:column;gap:8px;max-height:400px;overflow-y:auto;margin-bottom:12px;}
.chat-msg{background:var(--mm-grey-xlight);border-radius:var(--mm-radius-sm);padding:8px 12px;max-width:90%;}
.chat-msg.mine{background:var(--mm-blue-light);align-self:flex-end;}
.chat-msg p{margin:4px 0 2px;font-size:.88rem;}
.chat-ts{font-size:.72rem;color:var(--mm-grey);}
.chat-form{display:flex;gap:6px;}
.chat-form input{flex:1;padding:8px 12px;border:1px solid var(--mm-grey-light);border-radius:var(--mm-radius-xs);}
.toast{position:fixed;top:80px;right:20px;background:var(--mm-success);color:#fff;padding:12px 24px;border-radius:var(--mm-radius-sm);box-shadow:var(--mm-shadow-md);z-index:999;}
</style>
<script>
const t=document.querySelector('.toast');if(t)setTimeout(()=>t.style.display='none',4000);
const c=document.getElementById('chatMsgs');if(c)c.scrollTop=c.scrollHeight;
</script>
</body>
</html>