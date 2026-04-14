<?php
session_start();
require_once 'config.php';

$errors = [];
$prenom = $nom = $email = $age = $classe = '';

// Si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom       = trim($_POST['prenom'] ?? '');
    $nom          = trim($_POST['nom'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $age          = trim($_POST['age'] ?? '');
    $classe       = trim($_POST['niveau'] ?? '');
    $mdp          = $_POST['mdp'] ?? '';
    $mdp_confirm  = $_POST['mdp_confirm'] ?? '';
    $cgu          = isset($_POST['cgu']);

    // Validations
    if ($prenom === '' || $nom === '' || $email === '' || $mdp === '' || $mdp_confirm === '') {
        $errors[] = 'Tous les champs obligatoires doivent être remplis.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    if ($mdp !== $mdp_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (!$cgu) {
        $errors[] = 'Vous devez accepter les CGU et la politique de confidentialité.';
    }

    if (!empty($age) && (!ctype_digit($age) || (int)$age < 10 || (int)$age > 99)) {
        $errors[] = 'Âge invalide.';
    }

    // Si pas d’erreurs, on tente l’insert
    if (empty($errors)) {
        try {
            // Email déjà utilisé ?
            $stmt = $pdo->prepare('SELECT user_id FROM utilisateur WHERE email = :email');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Cette adresse email est déjà utilisée.';
            } else {
                // Hash du mot de passe + insertion
                $hash = password_hash($mdp, PASSWORD_BCRYPT);

                $sql = "
                    INSERT INTO utilisateur (prenom, nom, email, mdp, age, classe)
                    VALUES (:prenom, :nom, :email, :mdp, :age, :classe)
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':prenom' => $prenom,
                    ':nom'    => $nom,
                    ':email'  => $email,
                    ':mdp'    => $hash,
                    ':age'    => $age !== '' ? (int)$age : null,
                    ':classe' => $classe !== '' ? $classe : null,
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$pdo->lastInsertId();
                $_SESSION['prenom']    = $prenom;
                $_SESSION['nom']       = $nom;
                $_SESSION['email']     = $email;
                $_SESSION['est_admin'] = false;

                // Attribution du badge "Bienvenu"
                require_once 'badges.php';
                attribuer_badges($_SESSION['user_id'], $pdo);

                // Redirection vers recherche pour rejoindre une premiere activite en 1-2 clics
                header('Location: recherche.php?welcome=1');
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
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>MatchMove - Inscription</title>
    <link rel="icon" type="image/jpg" href="logo.jpg">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style_register.css">
  </head>
  <body>
    <?php require_once '_nav.php'; ?>

    <div class="container">
      <main>
        <section class="auth-wrapper">
          <div class="auth-header">
            <h1>Créer ton compte</h1>
            <p>Rejoins MatchMove pour proposer et rejoindre des activités près de toi.</p>
          </div>

          <section class="auth-card">
            <form class="auth-form" method="post" action="register.php">
              <?php if (!empty($errors)): ?>
                <div class="auth-error">
                  <?php foreach ($errors as $err): ?>
                    <p><?= htmlspecialchars($err) ?></p>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="auth-row">
                <div class="auth-field">
                  <label for="prenom">Prénom</label>
                  <input type="text" id="prenom" name="prenom" required
                         value="<?= htmlspecialchars($prenom) ?>">
                </div>
                <div class="auth-field">
                  <label for="nom">Nom</label>
                  <input type="text" id="nom" name="nom" required
                         value="<?= htmlspecialchars($nom) ?>">
                </div>
              </div>

              <div class="auth-field">
                <label for="email">Adresse e-mail scolaire</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($email) ?>">
              </div>
              <div class="auth-row">
                <div class="auth-field">
                  <label for="age">Âge</label>
                  <input type="number" id="age" name="age" min="10" max="99"
                        value="<?= htmlspecialchars($age) ?>">
                </div>
                <div class="auth-field">
                  <label for="niveau">Niveau</label>
                  <select id="niveau" name="niveau" required>
                    <option value="">-- Choisir --</option>
                    <option value="B1" <?= $classe === 'B1' ? 'selected' : '' ?>>B1</option>
                    <option value="B2" <?= $classe === 'B2' ? 'selected' : '' ?>>B2</option>
                    <option value="B3" <?= $classe === 'B3' ? 'selected' : '' ?>>B3</option>
                    <option value="M1" <?= $classe === 'M1' ? 'selected' : '' ?>>M1</option>
                    <option value="M2" <?= $classe === 'M2' ? 'selected' : '' ?>>M2</option>
                    <option value="Autre" <?= $classe === 'Autre' ? 'selected' : '' ?>>Autre</option>
                  </select>
                </div>
              </div>
              <div class="auth-field">
                <label for="mdp">Mot de passe</label>
                <input type="password" id="mdp" name="mdp" required>
              </div>

              <div class="auth-field">
                <label for="mdp_confirm">Confirmer le mot de passe</label>
                <input type="password" id="mdp_confirm" name="mdp_confirm" required>
              </div>

              <div class="auth-field auth-checkbox">
                <label>
                  <input type="checkbox" name="cgu" required>
                  J'accepte les 
                  <a href="cgu.php" target="_blank">CGU</a> 
                  et la 
                  <a href="confidentialite.php" target="_blank">politique de confidentialité</a>.
                </label>
              </div>

              <button type="submit" class="btn btn-primary auth-submit">
                Créer mon compte
              </button>

              <p class="auth-footer">
                Déjà un compte ?
                <a href="login.php">Se connecter</a>
              </p>
            </form>
          </section>
        </section>
      </main>
    </div>

    <script>
      const hb = document.getElementById('hb');
      const side = document.getElementById('side');
      const overlay = document.getElementById('overlay');

      function openMenu(){
        side.classList.add('open');
        overlay.classList.add('show');
        hb.setAttribute('aria-expanded','true');
        side.setAttribute('aria-hidden','false');
      }

      function closeMenu(){
        side.classList.remove('open');
        overlay.classList.remove('show');
        hb.setAttribute('aria-expanded','false');
        side.setAttribute('aria-hidden','true');
      }

      hb.addEventListener('click', ()=>{
        if(side.classList.contains('open')) closeMenu(); else openMenu();
      });
      overlay.addEventListener('click', closeMenu);
      document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeMenu(); });
    </script>
  </body>
</html>
