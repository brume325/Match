<?php session_start(); require_once 'config.php'; ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Politique de confidentialité – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main class="legal-page">
    <h1>Politique de Confidentialité</h1>
    <p class="legal-date">Dernière mise à jour : juin 2025</p>

    <h2>1. Responsable du traitement</h2>
    <p>Match Moov, plateforme communautaire étudiante. Contact : privacy@matchmoov.fr</p>

    <h2>2. Données collectées</h2>
    <ul style="color:var(--mm-grey);line-height:2;padding-left:20px;">
      <li>Informations d'identité : prénom, nom, adresse e-mail, âge, niveau d'études</li>
      <li>Données de profil : photo de profil, organisation</li>
      <li>Données d'activité : activités créées, participations, messages, commentaires</li>
      <li>Données techniques : adresse IP (logs), cookies de session</li>
    </ul>

    <h2>3. Finalités du traitement</h2>
    <p>Les données sont utilisées pour : la gestion des comptes utilisateurs, la mise en relation entre utilisateurs, l'amélioration du service, la modération du contenu, et la conformité légale.</p>

    <h2>4. Base légale</h2>
    <p>Le traitement est fondé sur : le consentement de l'utilisateur (CGU acceptées à l'inscription) et l'exécution du contrat de service.</p>

    <h2>5. Durée de conservation</h2>
    <p>Les données sont conservées pendant la durée d'activité du compte, puis 3 ans après la dernière connexion. Les données supprimées via la demande RGPD sont effacées dans un délai de 30 jours.</p>

    <h2>6. Vos droits</h2>
    <p>Conformément au RGPD, vous disposez des droits suivants :</p>
    <ul style="color:var(--mm-grey);line-height:2;padding-left:20px;">
      <li>Droit d'accès et de rectification</li>
      <li>Droit à l'effacement ("droit à l'oubli")</li>
      <li>Droit à la portabilité des données</li>
      <li>Droit d'opposition et de limitation du traitement</li>
    </ul>
    <?php if (isset($_SESSION['user_id'])): ?>
    <p><a href="export_rgpd.php" class="btn btn-outline btn-sm" style="margin-top:8px;display:inline-block;">📥 Exporter mes données</a></p>
    <?php endif; ?>

    <h2>7. Cookies</h2>
    <p>Match Moov utilise uniquement des cookies strictement nécessaires au fonctionnement (cookie de session). Aucun cookie publicitaire ou de tracking tiers n'est utilisé.</p>

    <h2>8. Contact</h2>
    <p>Pour toute demande relative à vos données : privacy@matchmoov.fr ou via les paramètres de votre compte.</p>
  </main>
</div>
<style>
.legal-page{max-width:760px;margin:32px auto;padding:0 16px;}
.legal-page h1{margin-bottom:8px;}
.legal-date{color:var(--mm-grey);font-size:.85rem;margin-bottom:28px;}
.legal-page h2{margin-top:24px;margin-bottom:8px;font-size:1.05rem;}
.legal-page p{color:var(--mm-grey);line-height:1.75;}
</style>
</body>
</html>