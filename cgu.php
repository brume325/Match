<?php session_start(); require_once 'config.php'; ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CGU – Match Moov</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once '_nav.php'; ?>
<div class="container">
  <main class="legal-page">
    <h1>Conditions Générales d'Utilisation</h1>
    <p class="legal-date">Dernière mise à jour : juin 2025</p>

    <h2>1. Objet</h2>
    <p>Match Moov est une plateforme communautaire permettant aux étudiants de créer, rejoindre et partager des activités locales. L'utilisation du site implique l'acceptation pleine et entière des présentes CGU.</p>

    <h2>2. Inscription</h2>
    <p>L'accès aux fonctionnalités complètes requiert la création d'un compte avec une adresse e-mail valide. L'utilisateur s'engage à fournir des informations exactes et à maintenir la confidentialité de ses identifiants.</p>

    <h2>3. Comportement des utilisateurs</h2>
    <p>Il est interdit de publier du contenu illicite, offensant, discriminatoire ou trompeur. Match Moov se réserve le droit de supprimer tout contenu ne respectant pas ces règles et de suspendre les comptes contrevenants.</p>

    <h2>4. Propriété intellectuelle</h2>
    <p>Le contenu de la plateforme (logos, textes, design) est protégé par le droit d'auteur. Les utilisateurs conservent leurs droits sur les contenus qu'ils publient mais accordent à Match Moov une licence d'affichage.</p>

    <h2>5. Responsabilité</h2>
    <p>Match Moov ne peut être tenu responsable des activités organisées par les utilisateurs, ni des contenus publiés par ceux-ci. La participation à une activité se fait sous l'entière responsabilité de l'utilisateur.</p>

    <h2>6. Données personnelles</h2>
    <p>Les données collectées sont traitées conformément à notre <a href="confidentialite.php">politique de confidentialité</a> et au RGPD.</p>

    <h2>7. Modification des CGU</h2>
    <p>Match Moov peut modifier les présentes CGU à tout moment. Les utilisateurs seront informés des changements significatifs.</p>

    <h2>8. Droit applicable</h2>
    <p>Les présentes CGU sont soumises au droit français. Tout litige sera soumis aux tribunaux compétents de Paris.</p>
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