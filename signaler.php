<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$success = false;
$type   = $_GET['type'] ?? $_POST['type'] ?? 'activite';
$cible  = (int)($_GET['id'] ?? $_POST['cible_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raison = trim($_POST['raison'] ?? '');
    $type   = $_POST['type'] ?? 'activite';
    $cible  = (int)($_POST['cible_id'] ?? 0);
    if ($raison !== '' && $cible > 0 && in_array($type, ['activite','utilisateur','commentaire'])) {
        try {
            $pdo->prepare('INSERT INTO signalement (user_id,type_cible,cible_id,raison) VALUES (:uid,:t,:c,:r)')
                ->execute([':uid'=>$uid,':t'=>$type,':c'=>$cible,':r'=>$raison]);
            $success = true;
        } catch (PDOException $e) {}
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Signaler – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main style="max-width:500px;margin:40px auto;">
    <div class="auth-card" style="padding:28px;">
      <?php if ($success): ?>
        <h2>✅ Signalement envoyé</h2>
        <p style="margin:16px 0;color:var(--mm-grey);">Merci. Notre équipe examinera ce contenu.</p>
        <a href="javascript:history.back()" class="btn btn-outline">← Retour</a>
      <?php else: ?>
        <h2>🚨 Signaler un contenu</h2>
        <form method="post" class="auth-form" style="margin-top:16px;">
          <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
          <input type="hidden" name="cible_id" value="<?= $cible ?>">
          <div class="auth-field">
            <label>Type de signalement</label>
            <p style="font-size:.85rem;color:var(--mm-grey);">
              <?= htmlspecialchars(ucfirst($type)) ?> #<?= $cible ?>
            </p>
          </div>
          <div class="auth-field">
            <label for="raison">Raison du signalement</label>
            <select name="raison" id="raison" required>
              <option value="">-- Choisir --</option>
              <option>Contenu inapproprié</option>
              <option>Spam ou publicité</option>
              <option>Harcèlement</option>
              <option>Fausses informations</option>
              <option>Autre</option>
            </select>
          </div>
          <button type="submit" class="btn btn-danger auth-submit">Envoyer le signalement</button>
          <p class="auth-footer"><a href="javascript:history.back()">← Annuler</a></p>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>