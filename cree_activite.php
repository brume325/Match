<?php
session_start();

// Protection : obligation d'être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$errors = [];
$success = false;
$new_badges = [];

$titre = $description = $ville = $nom_lieu = $adresse = $date = $heure_debut = $heure_fin = $image = '';
$nb_places_max = '';
$est_payante = false;
$categorie_id = 0;

// Charger les catégories pour le formulaire
$categories = [];
try {
    $categories = $pdo->query("SELECT categorie_id, nom, icone FROM categorie ORDER BY nom")->fetchAll();
} catch (PDOException $e) { }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre         = trim($_POST['titre'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $ville         = trim($_POST['ville'] ?? '');
    $nom_lieu      = trim($_POST['nom_lieu'] ?? '');
    $adresse       = trim($_POST['adresse'] ?? '');
    $date          = trim($_POST['date'] ?? '');
    $heure_debut   = trim($_POST['heure_debut'] ?? '');
    $heure_fin     = trim($_POST['heure_fin'] ?? '');
    $nb_places_max = trim($_POST['nb_places_max'] ?? '');
    $est_payante   = isset($_POST['est_payante']);
    $categorie_id  = (int)($_POST['categorie_id'] ?? 0);
    $createur_id   = $_SESSION['user_id'];

    // Gestion de l'image : upload fichier OU URL saisie
    $image = '';
    if (!empty($_FILES['image_fichier']['name'])) {
        // Upload de fichier
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $_FILES['image_fichier']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Format image non autorisé (JPG, PNG, GIF, WebP uniquement).';
        } elseif ($_FILES['image_fichier']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image trop lourde (max 5 Mo).';
        } else {
            $ext        = pathinfo($_FILES['image_fichier']['name'], PATHINFO_EXTENSION);
            $filename   = 'uploads/activites/' . uniqid('act_', true) . '.' . strtolower($ext);
            $uploadDir  = __DIR__ . '/uploads/activites/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['image_fichier']['tmp_name'], __DIR__ . '/' . $filename)) {
                $image = $filename;
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement de l\'image.';
            }
        }
    } else {
        // URL saisie manuellement
        $image = trim($_POST['image_couverture'] ?? '');
    }

    // validations rapides
    if ($titre === '' || $description === '' || $ville === '' || $date === '' || $heure_debut === '') {
        $errors[] = 'Titre, description, ville, date et heure de début sont obligatoires.';
    }

    if ($nb_places_max !== '' && !ctype_digit($nb_places_max)) {
        $errors[] = 'Le nombre de places doit être un entier.';
    }

    if (empty($errors)) {
        try {
            // 1) créer le lieu
            $sqlLieu = "
                INSERT INTO lieu (nom_lieu, adresse, ville)
                VALUES (:nom_lieu, :adresse, :ville)
            ";
            $stmtLieu = $pdo->prepare($sqlLieu);
            $stmtLieu->execute([
                ':nom_lieu' => $nom_lieu !== '' ? $nom_lieu : $ville,
                ':adresse'  => $adresse !== '' ? $adresse : null,
                ':ville'    => $ville,
            ]);
            $lieu_id = (int)$pdo->lastInsertId();

            // 2) créer l'activité
            $sqlAct = "
                INSERT INTO activite (
                    titre, description, createur_id, categorie_id, lieu_id,
                    date, heure_debut, heure_fin, nb_places_max, image_couverture, est_payante
                )
                VALUES (
                    :titre, :description, :createur_id, :categorie_id, :lieu_id,
                    :date, :heure_debut, :heure_fin, :nb_places_max, :image_couverture, :est_payante
                )
            ";
            $stmtAct = $pdo->prepare($sqlAct);
            $stmtAct->execute([
                ':titre'            => $titre,
                ':description'      => $description,
                ':createur_id'      => $createur_id,
                ':categorie_id'     => $categorie_id > 0 ? $categorie_id : null,
                ':lieu_id'          => $lieu_id,
                ':date'             => $date,
                ':heure_debut'      => $heure_debut !== '' ? $heure_debut : null,
                ':heure_fin'        => $heure_fin !== '' ? $heure_fin : null,
                ':nb_places_max'    => $nb_places_max !== '' ? (int)$nb_places_max : null,
                ':image_couverture' => $image !== '' ? $image : null,
                ':est_payante'      => $est_payante ? 1 : 0,
            ]);

            $activity_id = (int)$pdo->lastInsertId();

            // Ajouter points + vérifier badges
            require_once 'badges.php';
            ajouter_points($createur_id, 20, $pdo); // 20 points par création
            $new_badges = attribuer_badges($createur_id, $pdo);

            $success = true;

            // reset formulaire
            $titre = $description = $ville = $nom_lieu = $adresse = $date = $heure_debut = $heure_fin = $image = '';
            $nb_places_max = '';
            $est_payante = false;
            $categorie_id = 0;

        } catch (PDOException $e) {
            $errors[] = "Erreur serveur lors de la création de l'activité.";
            $errors[] = $e->getMessage(); // debug
        }
    }
}
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>MatchMove - Créer une activité</title>
    <link rel="icon" type="image/jpg" href="logo.jpg">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style_cree_activite.css">
  </head>
  <body>
    <?php require_once '_nav.php'; ?>

    <div class="container">
      <main>
        <section class="form-wrapper">
          <div class="form-header">
            <h1>Créer une activité</h1>
            <p>Renseigne les informations principales de ta sortie.</p>
          </div>

          <section class="form-card">
            <?php if ($success): ?>
              <div class="form-success">
                <p>Activité créée avec succès !</p>
                <p>Tu viens de gagner <strong>20 points</strong> ! 🎉</p>
                <?php if (!empty($new_badges)): ?>
                  <p><strong>Nouveau badge debloque :</strong> <?= htmlspecialchars(implode(', ', $new_badges)) ?></p>
                <?php endif; ?>
                <p><a href="rewards.php" class="btn btn-secondary btn-sm" style="margin-top:8px;">Voir mes badges</a></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
              <div class="form-error">
                <?php foreach ($errors as $err): ?>
                  <p><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="cree_activite.php" class="activity-form" enctype="multipart/form-data">
              <div class="form-row">
                <div class="form-field">
                  <label for="titre">Titre de l'activité</label>
                  <input type="text" id="titre" name="titre" required
                         value="<?= htmlspecialchars($titre) ?>">
                </div>
              </div>

              <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required><?= htmlspecialchars($description) ?></textarea>
              </div>

              <div class="form-row">
                <div class="form-field">
                  <label for="ville">Ville</label>
                  <input type="text" id="ville" name="ville" required
                         value="<?= htmlspecialchars($ville) ?>">
                </div>
                <div class="form-field">
                  <label for="nom_lieu">Nom du lieu</label>
                  <input type="text" id="nom_lieu" name="nom_lieu"
                         value="<?= htmlspecialchars($nom_lieu) ?>">
                </div>
              </div>

              <div class="form-field">
                <label for="adresse">Adresse (optionnel)</label>
                <input type="text" id="adresse" name="adresse"
                       value="<?= htmlspecialchars($adresse) ?>">
              </div>

              <div class="form-row">
                <div class="form-field">
                  <label for="date">Date</label>
                  <input type="date" id="date" name="date" required
                         value="<?= htmlspecialchars($date) ?>">
                </div>
                <div class="form-field">
                  <label for="heure_debut">Heure de début</label>
                  <input type="time" id="heure_debut" name="heure_debut" required
                         value="<?= htmlspecialchars($heure_debut) ?>">
                </div>
                <div class="form-field">
                  <label for="heure_fin">Heure de fin</label>
                  <input type="time" id="heure_fin" name="heure_fin"
                         value="<?= htmlspecialchars($heure_fin) ?>">
                </div>
              </div>

              <div class="form-row">
                <div class="form-field">
                  <label for="categorie_id">Catégorie</label>
                  <select id="categorie_id" name="categorie_id">
                    <option value="0">-- Choisir une catégorie --</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= (int)$cat['categorie_id'] ?>"
                        <?= $categorie_id === (int)$cat['categorie_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['icone'] . ' ' . $cat['nom']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-field">
                  <label for="nb_places_max">Places max (optionnel)</label>
                  <input type="number" id="nb_places_max" name="nb_places_max" min="1"
                         value="<?= htmlspecialchars($nb_places_max) ?>">
                </div>
              </div>

              <div class="form-field">
                <label>Image de couverture (optionnel)</label>
                <div style="display:flex;flex-direction:column;gap:10px;">
                  <!-- Option 1 : upload fichier -->
                  <div>
                    <label for="image_fichier" style="font-size:.85rem;font-weight:600;color:var(--mm-grey);margin-bottom:4px;display:block;">
                      📁 Choisir un fichier (JPG, PNG, WebP — max 5 Mo)
                    </label>
                    <input type="file" id="image_fichier" name="image_fichier"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           style="width:100%;padding:8px;border:2px dashed var(--mm-grey-light);border-radius:8px;font-size:.9rem;cursor:pointer;"
                           onchange="document.getElementById('image_couverture').value=''">
                  </div>
                  <!-- Option 2 : URL -->
                  <div>
                    <label for="image_couverture" style="font-size:.85rem;font-weight:600;color:var(--mm-grey);margin-bottom:4px;display:block;">
                      🔗 Ou coller une URL d'image
                    </label>
                    <input type="text" id="image_couverture" name="image_couverture"
                           placeholder="https://exemple.com/image.jpg"
                           value="<?= htmlspecialchars($image) ?>"
                           oninput="if(this.value) document.getElementById('image_fichier').value=''">
                  </div>
                  <!-- Prévisualisation -->
                  <div id="img-preview" style="display:none;margin-top:6px;">
                    <img id="img-preview-src" src="" alt="Aperçu"
                         style="max-height:140px;max-width:100%;border-radius:8px;object-fit:cover;border:2px solid var(--mm-grey-light);">
                  </div>
                </div>
                <script>
                  // Prévisualisation pour upload fichier
                  document.getElementById('image_fichier').addEventListener('change', function(e) {
                    if (e.target.files[0]) {
                      var reader = new FileReader();
                      reader.onload = function(ev) {
                        document.getElementById('img-preview-src').src = ev.target.result;
                        document.getElementById('img-preview').style.display = 'block';
                      };
                      reader.readAsDataURL(e.target.files[0]);
                    }
                  });
                  // Prévisualisation pour URL
                  document.getElementById('image_couverture').addEventListener('input', function() {
                    var url = this.value.trim();
                    if (url) {
                      document.getElementById('img-preview-src').src = url;
                      document.getElementById('img-preview').style.display = 'block';
                    } else {
                      document.getElementById('img-preview').style.display = 'none';
                    }
                  });
                </script>
              </div>

              <div class="form-field auth-checkbox">
                <label>
                  <input type="checkbox" name="est_payante" <?= $est_payante ? 'checked' : '' ?>>
                  Activité payante
                </label>
              </div>

              <button type="submit" class="btn btn-primary form-submit">
                Publier l'activité
              </button>
            </form>
          </section>
        </section>
      </main>
    </div>
  </body>
</html>
