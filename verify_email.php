<?php
session_start();
require_once 'config.php';

$token = trim($_GET['token'] ?? '');
$msg   = '';
$ok    = false;

if ($token !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token_verif = :tok AND email_verifie = 0');
        $stmt->execute([':tok' => $token]);
        $user = $stmt->fetch();
        if ($user) {
            $pdo->prepare('UPDATE users SET email_verifie=1, token_verif=NULL WHERE id=:uid')
                ->execute([':uid' => $user['id']]);
            $ok  = true;
            $msg = 'Ton adresse e-mail est confirmée. Tu peux maintenant te connecter.';
        } else {
            $msg = 'Lien de vérification invalide ou déjà utilisé.';
        }
    } catch (PDOException $e) {
        $msg = 'Erreur serveur.';
    }
} else {
    $msg = 'Aucun token fourni.';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MatchMove – Vérification e-mail</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main style="max-width:480px;margin:60px auto;text-align:center;">
    <div class="auth-card" style="padding:40px;">
      <?php if ($ok): ?>
        <div style="font-size:3rem;margin-bottom:16px;">✅</div>
        <h2>E-mail vérifié !</h2>
        <p style="margin:16px 0;"><?= htmlspecialchars($msg) ?></p>
        <a href="login.php" class="btn btn-primary">Se connecter</a>
      <?php else: ?>
        <div style="font-size:3rem;margin-bottom:16px;">❌</div>
        <h2>Lien invalide</h2>
        <p style="margin:16px 0;"><?= htmlspecialchars($msg) ?></p>
        <a href="register.php" class="btn btn-primary">Créer un compte</a>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>