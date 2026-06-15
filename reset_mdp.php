<?php
session_start();
require_once 'config.php';

$token  = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$done   = false;
$user   = null;

if ($token !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token_reset=:tok AND token_reset_exp > NOW() AND actif=1');
        $stmt->execute([':tok' => $token]);
        $user = $stmt->fetch();
    } catch (PDOException $e) { $errors[] = 'Erreur serveur.'; }
}

if (!$user && empty($errors)) { $errors[] = 'Lien invalide ou expiré.'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && empty($errors)) {
    $mdp  = $_POST['mdp']         ?? '';
    $conf = $_POST['mdp_confirm'] ?? '';
    if (strlen($mdp) < 8)           $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
    if ($mdp !== $conf)             $errors[] = 'Les mots de passe ne correspondent pas.';
    if (empty($errors)) {
        try {
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET mdp=:h, token_reset=NULL, token_reset_exp=NULL WHERE id=:uid')
                ->execute([':h' => $hash, ':uid' => $user['id']]);
            $done = true;
        } catch (PDOException $e) { $errors[] = 'Erreur serveur.'; }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MatchMove – Nouveau mot de passe</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>
    <section class="auth-wrapper">
      <div class="auth-header"><h1>Nouveau mot de passe</h1></div>
      <section class="auth-card">
        <?php if ($done): ?>
          <div class="auth-success"><p>Mot de passe mis à jour avec succès !</p></div>
          <p class="auth-footer" style="text-align:center;margin-top:20px;"><a href="login.php" class="btn btn-primary">Se connecter</a></p>
        <?php elseif (!empty($errors) && !$user): ?>
          <div class="auth-error"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
          <p class="auth-footer" style="text-align:center;margin-top:20px;"><a href="mot_de_passe_oublie.php">Demander un nouveau lien</a></p>
        <?php else: ?>
          <?php if (!empty($errors)): ?><div class="auth-error"><?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div><?php endif; ?>
          <form class="auth-form" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="auth-field">
              <label for="mdp">Nouveau mot de passe</label>
              <input type="password" id="mdp" name="mdp" required minlength="8">
            </div>
            <div class="auth-field">
              <label for="mdp_confirm">Confirmer</label>
              <input type="password" id="mdp_confirm" name="mdp_confirm" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary auth-submit">Enregistrer</button>
          </form>
        <?php endif; ?>
      </section>
    </section>
  </main>
</div>
</body>
</html>