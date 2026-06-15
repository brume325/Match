<?php
session_start();
require_once 'config.php';

$msg   = '';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email=:email AND actif=1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expiry  = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare('UPDATE users SET token_reset=:tok, token_reset_exp=:exp WHERE id=:uid')
                    ->execute([':tok' => $token, ':exp' => $expiry, ':uid' => $user['id']]);

                $app_url = getenv('APP_URL') ?: 'http://localhost';
                $link    = $app_url . '/reset_mdp.php?token=' . $token;
                $subject = 'Match Moov – Réinitialisation de mot de passe';
                $body    = "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe (valable 1 heure) :\n$link\n\nSi vous n'avez pas fait cette demande, ignorez cet e-mail.";
                @mail($email, $subject, $body, 'From: no-reply@matchmoov.fr');
            }
            // Toujours afficher le même message (sécurité anti-énumération)
            $msg = 'Si cette adresse est enregistrée, un lien de réinitialisation vient d\'être envoyé.';
        } catch (PDOException $e) {
            $error = 'Erreur serveur.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MatchMove – Mot de passe oublié</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>
    <section class="auth-wrapper">
      <div class="auth-header">
        <h1>Mot de passe oublié</h1>
        <p>Saisis ton adresse e-mail, on t'envoie un lien de réinitialisation.</p>
      </div>
      <section class="auth-card">
        <?php if ($msg): ?><div class="auth-success"><p><?= htmlspecialchars($msg) ?></p></div><?php endif; ?>
        <?php if ($error): ?><div class="auth-error"><p><?= htmlspecialchars($error) ?></p></div><?php endif; ?>
        <?php if (!$msg): ?>
        <form class="auth-form" method="post">
          <div class="auth-field">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
          </div>
          <button type="submit" class="btn btn-primary auth-submit">Envoyer le lien</button>
          <p class="auth-footer"><a href="login.php">← Retour à la connexion</a></p>
        </form>
        <?php else: ?>
        <p class="auth-footer" style="text-align:center;margin-top:20px;"><a href="login.php">← Retour à la connexion</a></p>
        <?php endif; ?>
      </section>
    </section>
  </main>
</div>
</body>
</html>