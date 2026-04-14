<?php
session_start();

// Protection : obligation d'être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

$utilisateur = null;

// Récupérer les infos utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT user_id, prenom, nom, email, age, classe, avatar
        FROM utilisateur
        WHERE user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $user_id]);
    $utilisateur = $stmt->fetch();
} catch (PDOException $e) {
    $errors[] = 'Erreur lors de la récupération des données.';
}

// Valeurs par défaut
$prenom = $utilisateur['prenom'] ?? '';
$nom = $utilisateur['nom'] ?? '';
$email = $utilisateur['email'] ?? '';
$age = $utilisateur['age'] ?? '';
$classe = $utilisateur['classe'] ?? '';
$avatar = $utilisateur['avatar'] ?? '';

// Traitement modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $classe = trim($_POST['classe'] ?? '');
    $avatar_input = trim($_POST['avatar'] ?? '');
    $remove_avatar = isset($_POST['remove_avatar']);
    $new_mdp = $_POST['new_mdp'] ?? '';
    $confirm_mdp = $_POST['confirm_mdp'] ?? '';
    $avatar_to_save = $avatar;

    if ($prenom === '' || $nom === '' || $email === '') {
        $errors[] = 'Prénom, nom et email sont obligatoires.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    // Si changement de mot de passe
    if ($new_mdp !== '' || $confirm_mdp !== '') {
        if ($new_mdp !== $confirm_mdp) {
            $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
        }
    }

    if ($remove_avatar) {
        if (!empty($avatar) && str_starts_with($avatar, 'uploads/avatars/')) {
            $old_avatar_path = dirname(__DIR__, 2) . '/public/' . $avatar;
            if (is_file($old_avatar_path)) {
                @unlink($old_avatar_path);
            }
        }
        $avatar_to_save = '';
    } else {
        if ($avatar_input !== '') {
            $is_url = filter_var($avatar_input, FILTER_VALIDATE_URL);
            $is_local_path = str_starts_with($avatar_input, 'uploads/');
            if (!$is_url && !$is_local_path) {
                $errors[] = 'Avatar invalide : mettez une URL valide ou importez un fichier.';
            } else {
                $avatar_to_save = $avatar_input;
            }
        }

        if (!empty($_FILES['avatar_file']['name'] ?? '')) {
            $tmp_name = $_FILES['avatar_file']['tmp_name'] ?? '';
            $size = (int)($_FILES['avatar_file']['size'] ?? 0);
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];

            if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
                $errors[] = 'Upload avatar invalide.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);

                if (!isset($allowed_mimes[$mime])) {
                    $errors[] = 'Format avatar non autorise (JPG, PNG, GIF, WebP).';
                } elseif ($size > 5 * 1024 * 1024) {
                    $errors[] = 'Avatar trop volumineux (max 5 Mo).';
                } else {
                    $upload_dir = dirname(__DIR__, 2) . '/public/uploads/avatars/';
                    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                        $errors[] = 'Impossible de creer le dossier des avatars.';
                    } else {
                        $file_name = 'uploads/avatars/' . $user_id . '_' . uniqid('', true) . '.' . $allowed_mimes[$mime];
                        if (!move_uploaded_file($tmp_name, dirname(__DIR__, 2) . '/public/' . $file_name)) {
                            $errors[] = 'Erreur lors de l enregistrement de l avatar.';
                        } else {
                            if (!empty($avatar) && str_starts_with($avatar, 'uploads/avatars/')) {
                                $old_avatar_path = dirname(__DIR__, 2) . '/public/' . $avatar;
                                if (is_file($old_avatar_path)) {
                                    @unlink($old_avatar_path);
                                }
                            }
                            $avatar_to_save = $file_name;
                        }
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Vérifier si email déjà utilisé par un autre user
            $stmt = $pdo->prepare('SELECT user_id FROM utilisateur WHERE email = :email AND user_id != :uid');
            $stmt->execute([':email' => $email, ':uid' => $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Cette adresse email est déjà utilisée par un autre compte.';
            } else {
                // UPDATE utilisateur
                if ($new_mdp !== '') {
                    $hash = password_hash($new_mdp, PASSWORD_BCRYPT);
                    $sql = "
                        UPDATE utilisateur
                        SET prenom = :prenom, nom = :nom, email = :email, age = :age, classe = :classe, avatar = :avatar, mdp = :mdp
                        WHERE user_id = :uid
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':prenom' => $prenom,
                        ':nom' => $nom,
                        ':email' => $email,
                        ':age' => $age !== '' ? (int)$age : null,
                        ':classe' => $classe,
                        ':avatar' => $avatar_to_save !== '' ? $avatar_to_save : null,
                        ':mdp' => $hash,
                        ':uid' => $user_id,
                    ]);
                } else {
                    $sql = "
                        UPDATE utilisateur
                        SET prenom = :prenom, nom = :nom, email = :email, age = :age, classe = :classe, avatar = :avatar
                        WHERE user_id = :uid
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':prenom' => $prenom,
                        ':nom' => $nom,
                        ':email' => $email,
                        ':age' => $age !== '' ? (int)$age : null,
                        ':classe' => $classe,
                        ':avatar' => $avatar_to_save !== '' ? $avatar_to_save : null,
                        ':uid' => $user_id,
                    ]);
                }

                // Mettre à jour la session
                $_SESSION['prenom'] = $prenom;
                $_SESSION['nom'] = $nom;
                $_SESSION['email'] = $email;
                $avatar = $avatar_to_save;

                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur serveur lors de la modification.';
            $errors[] = $e->getMessage();
        }
    }
}

// Traitement suppression compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    try {
        // Supprimer toutes les données liées (CASCADE ou manuel)
        $pdo->prepare("DELETE FROM participation WHERE user_id = :uid")->execute([':uid' => $user_id]);
        $pdo->prepare("DELETE FROM favori WHERE user_id = :uid")->execute([':uid' => $user_id]);
        $pdo->prepare("DELETE FROM commentaire WHERE author_id = :uid")->execute([':uid' => $user_id]);
        $pdo->prepare("DELETE FROM utilisateur_badge WHERE user_id = :uid")->execute([':uid' => $user_id]);
        $pdo->prepare("DELETE FROM activite WHERE createur_id = :uid")->execute([':uid' => $user_id]);
        $pdo->prepare("DELETE FROM utilisateur WHERE user_id = :uid")->execute([':uid' => $user_id]);

        // Détruire session et rediriger
        session_destroy();
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = 'Erreur lors de la suppression du compte.';
        $errors[] = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>MatchMove - Paramètres</title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo-match-moov.svg">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/style_parametres.css">
  </head>
  <body>
    <?php require_once '_nav.php'; ?>

    <div class="container">
      <main>
        <section class="settings-wrapper">
          <div class="settings-header">
            <h1>Paramètres du compte</h1>
            <p>Modifie tes informations personnelles ou supprime ton compte.</p>
          </div>

          <!-- Modification des données -->
          <section class="settings-card">
            <h2>Modifier mes informations</h2>

            <?php if ($success): ?>
              <div class="form-success">
                <p>Informations mises a jour avec succes.</p>
              </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
              <div class="form-error">
                <?php foreach ($errors as $err): ?>
                  <p><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="parametres.php" class="settings-form" enctype="multipart/form-data">
              <input type="hidden" name="action" value="modifier">

              <div class="form-row">
                <div class="form-field">
                  <label for="prenom">Prénom</label>
                  <input type="text" id="prenom" name="prenom" required
                         value="<?= htmlspecialchars($prenom) ?>">
                </div>
                <div class="form-field">
                  <label for="nom">Nom</label>
                  <input type="text" id="nom" name="nom" required
                         value="<?= htmlspecialchars($nom) ?>">
                </div>
              </div>

              <div class="form-field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($email) ?>">
              </div>

              <div class="form-row">
                <div class="form-field">
                  <label for="age">Âge</label>
                  <input type="number" id="age" name="age" min="10" max="99"
                         value="<?= htmlspecialchars($age) ?>">
                </div>
                <div class="form-field">
                  <label for="classe">Niveau</label>
                  <select id="classe" name="classe">
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

              <div class="form-field">
                <label for="avatar">Photo de profil (URL)</label>
                <input type="text" id="avatar" name="avatar"
                       placeholder="https://... ou uploads/avatars/..."
                       value="<?= htmlspecialchars($avatar) ?>">
              </div>

              <div class="form-field">
                <label for="avatar_file">Ou importer une image</label>
                <input type="file" id="avatar_file" name="avatar_file"
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <small>Formats : JPG, PNG, GIF, WebP (max 5 Mo)</small>
              </div>

              <?php if (!empty($avatar)): ?>
                <div class="form-field">
                  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar actuel"
                         style="width:64px;height:64px;border-radius:999px;object-fit:cover;border:2px solid var(--mm-grey-light);">
                    <label style="display:flex;align-items:center;gap:8px;margin:0;">
                      <input type="checkbox" name="remove_avatar" value="1">
                      Supprimer la photo actuelle
                    </label>
                  </div>
                </div>
              <?php endif; ?>

              <div class="form-field">
                <label for="new_mdp">Nouveau mot de passe (optionnel)</label>
                <input type="password" id="new_mdp" name="new_mdp">
                <small>Laisse vide si tu ne veux pas changer ton mot de passe</small>
              </div>

              <div class="form-field">
                <label for="confirm_mdp">Confirmer nouveau mot de passe</label>
                <input type="password" id="confirm_mdp" name="confirm_mdp">
              </div>

              <button type="submit" class="btn btn-primary settings-submit">
                Enregistrer les modifications
              </button>
            </form>
          </section>

          <!-- Double Authentification (2FA) -->
          <section class="settings-card">
            <h2>Double authentification (2FA)</h2>
            <p style="color:var(--mm-grey);margin-bottom:16px;">
              Activez la vérification en deux étapes pour sécuriser votre compte.
              À chaque connexion, un code vous sera envoyé par email.
            </p>
            <?php
              $twofa = false;
              try {
                  // Vérifier si colonne deux_facteurs existe, sinon la créer
                  $pdo->exec("ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS deux_facteurs TINYINT(1) NOT NULL DEFAULT 0");
                  $s = $pdo->prepare('SELECT deux_facteurs FROM utilisateur WHERE user_id=:uid');
                  $s->execute([':uid' => $user_id]);
                  $twofa = (bool)($s->fetchColumn());
              } catch (Exception $e) {}

              if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='toggle_2fa') {
                  try {
                      $new_val = $twofa ? 0 : 1;
                      $pdo->prepare("UPDATE utilisateur SET deux_facteurs=:twofa WHERE user_id=:uid")
                          ->execute([':twofa' => $new_val, ':uid' => $user_id]);
                      $twofa = !$twofa;
                  } catch (Exception $e) {}
              }
            ?>
            <form method="POST" action="parametres.php">
              <input type="hidden" name="action" value="toggle_2fa">
              <div style="display:flex; align-items:center; gap:16px; padding:16px; background:var(--mm-bg); border-radius:10px;">
                <div style="flex:1;">
                  <strong><?= $twofa ? 'Activee' : 'Desactivee' ?></strong>
                  <p style="font-size:.82rem; color:var(--mm-grey); margin-top:4px;">
                    <?= $twofa ? 'La double authentification est activée sur votre compte.' : 'Votre compte est protégé par mot de passe uniquement.' ?>
                  </p>
                </div>
                <button type="submit" class="btn <?= $twofa ? 'btn-outline' : 'btn-primary' ?> btn-sm">
                  <?= $twofa ? 'Désactiver' : 'Activer la 2FA' ?>
                </button>
              </div>
            </form>
            <p style="font-size:.78rem; color:var(--mm-grey); margin-top:10px;">
              Necessite un serveur mail configure pour l'envoi des codes.
              En mode développement, le code apparaît dans les logs PHP.
            </p>
          </section>

          <!-- Liens RGPD -->
          <section class="settings-card">
            <h2>Mes donnees (RGPD)</h2>
            <p style="color:var(--mm-grey); margin-bottom:16px;">
              Conformément au RGPD, vous pouvez exporter ou supprimer toutes vos données.
            </p>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
              <a href="/index.php" class="btn btn-outline btn-sm">Exporter mes donnees</a>
              <a href="/index.php" class="btn btn-outline btn-sm">Politique de confidentialite</a>
            </div>
          </section>

          <!-- Suppression du compte -->
          <section class="settings-card danger-zone">
            <h2>Zone dangereuse</h2>
            <p>La suppression de ton compte est définitive et irréversible.</p>

            <form method="post" action="parametres.php" 
                  onsubmit="return confirm('ATTENTION : Cette action est irreversible.\n\nToutes tes donnees seront supprimees definitivement :\n- Ton profil\n- Tes activites creees\n- Tes participations\n- Tes favoris\n- Tes badges\n\nVeux-tu vraiment supprimer ton compte ?')">
              <input type="hidden" name="action" value="supprimer">
              <button type="submit" class="btn btn-danger">
                Supprimer mon compte définitivement
              </button>
            </form>
          </section>
        </section>
      </main>
    </div>
  </body>
</html>
