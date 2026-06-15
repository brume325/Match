<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if ($email === '' || $mdp === '') {
        $errors[] = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, prenom, nom, mdp, est_admin, actif FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($mdp, $user['mdp'])) {
                $errors[] = 'Identifiants incorrects.';
            } elseif (!(int)$user['actif']) {
                $errors[] = 'Ce compte est désactivé.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['prenom']    = $user['prenom'];
                $_SESSION['nom']       = $user['nom'];
                $_SESSION['email']     = $email;
                $_SESSION['est_admin'] = (bool)$user['est_admin'];
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur serveur, réessayez plus tard.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MatchMove – Connexion</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main>
    <section class="auth-wrapper">
      <div class="auth-header">
        <h1>Bon retour !</h1>
        <p>Connecte-toi pour retrouver tes activités.</p>
      </div>
      <section class="auth-card">
        <form class="auth-form" method="post" action="login.php">
          <?php if (!empty($errors)): ?>
            <div class="auth-error">
              <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="auth-field">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
          </div>
          <div class="auth-field">
            <label for="mdp">Mot de passe</label>
            <input type="password" id="mdp" name="mdp" required>
          </div>
          <button type="submit" class="btn btn-primary auth-submit">Se connecter</button>
          <p class="auth-footer">
            <a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
            &nbsp;·&nbsp;
            Pas encore de compte ? <a href="register.php">S'inscrire</a>
          </p>
        </form>
      </section>
    </section>
  </main>
</div>
</body>
</html>