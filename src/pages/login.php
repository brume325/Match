<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /recherche.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mdp'] ?? '';

    if ($email === '' || $mdp === '') {
        $errors[] = 'Veuillez renseigner votre adresse e-mail et votre mot de passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT user_id, prenom, nom, email, mdp, est_admin FROM utilisateur WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($mdp, (string)$user['mdp'])) {
                $errors[] = 'Identifiants invalides.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['prenom'] = (string)$user['prenom'];
                $_SESSION['nom'] = (string)$user['nom'];
                $_SESSION['email'] = (string)$user['email'];
                $_SESSION['est_admin'] = (bool)($user['est_admin'] ?? false);

                header('Location: /recherche.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur serveur, veuillez reessayer plus tard.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Match Moov - Connexion</title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo-match-moov.svg">
    <link rel="stylesheet" href="/assets/css/style.css">
  </head>
  <body>
    <?php require_once '_nav.php'; ?>

    <div class="container">
      <main>
        <section class="auth-wrapper">
          <div class="auth-header">
            <h1>Connexion</h1>
            <p>Accede a ton espace Match Moov.</p>
          </div>

          <section class="auth-card">
            <form class="auth-form" method="post" action="/login.php">
              <?php if (!empty($errors)): ?>
                <div class="auth-error">
                  <?php foreach ($errors as $err): ?>
                    <p><?= htmlspecialchars($err) ?></p>
                  <?php endforeach; ?>
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

              <button type="submit" class="btn btn-primary auth-submit">
                Se connecter
              </button>

              <p class="auth-footer">
                Pas encore de compte ?
                <a href="/register.php">S'inscrire</a>
              </p>
            </form>
          </section>
        </section>
      </main>
    </div>
  </body>
</html>
