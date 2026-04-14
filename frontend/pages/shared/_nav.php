<?php
/**
 * _nav.php - Barre de navigation & menu latéral partagé
 * Inclure avec : require_once '_nav.php';
 * Nécessite que session_start() ait déjà été appelé.
 */
$_nav_logged  = isset($_SESSION['user_id']);
$_nav_admin   = isset($_SESSION['est_admin']) && $_SESSION['est_admin'];
$_nav_current = basename($_SERVER['PHP_SELF']);

// Compter les notifications non lues et demandes d'amis
$_nav_notif_count = 0;
$_nav_ami_count   = 0;
if ($_nav_logged) {
    try {
        global $pdo;
        // Notifications non lues
        $s = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id=:uid AND est_lue=0");
        $s->execute([':uid' => $_SESSION['user_id']]);
        $_nav_notif_count = (int)$s->fetchColumn();
        // Demandes d'amis en attente
        $s = $pdo->prepare("SELECT COUNT(*) FROM ami WHERE recepteur_id=:uid AND statut='en_attente'");
        $s->execute([':uid' => $_SESSION['user_id']]);
        $_nav_ami_count = (int)$s->fetchColumn();
    } catch (Exception $e) { /* tables peut-être pas encore créées */ }
}
?>
<!-- TOPBAR -->
<div class="topbar">
  <button id="hb" class="hamburger" aria-label="Ouvrir le menu" aria-expanded="false">
    <span class="bar"></span>
  </button>
  <a href="index.php" class="brand">
    <img src="logo.jpg" alt="Logo MATCH MOOV" class="brand-logo">
    <span>MATCH MOOV</span>
  </a>
  <div class="topbar-actions">
    <?php if ($_nav_logged): ?>
      <a href="recherche.php"    class="topbar-link<?= $_nav_current==='recherche.php'?' active':'' ?>">🔍 Rechercher</a>
      <a href="messagerie.php"   class="topbar-link<?= $_nav_current==='messagerie.php'?' active':'' ?>">💬 Messages</a>
      <a href="notifications.php" class="topbar-link notif-btn<?= $_nav_current==='notifications.php'?' active':'' ?>" title="Notifications">
        🔔<?php if ($_nav_notif_count > 0): ?><span class="notif-badge"><?= $_nav_notif_count ?></span><?php endif; ?>
      </a>
      <a href="amis.php" class="topbar-link notif-btn<?= $_nav_current==='amis.php'?' active':'' ?>" title="Amis">
        👥<?php if ($_nav_ami_count > 0): ?><span class="notif-badge" style="background:var(--mm-yellow);color:var(--mm-dark);"><?= $_nav_ami_count ?></span><?php endif; ?>
      </a>
      <a href="cree_activite.php" class="topbar-link<?= $_nav_current==='cree_activite.php'?' active':'' ?>">+ Activité</a>
      <a href="profil.php"       class="topbar-link<?= $_nav_current==='profil.php'?' active':'' ?>">Mon profil</a>
      <a href="logout.php"       class="topbar-link">Déconnexion</a>
    <?php else: ?>
      <a href="recherche.php"  class="topbar-link">🔍 Rechercher</a>
      <a href="login.php"      class="topbar-link<?= $_nav_current==='login.php'?' active':'' ?>">Connexion</a>
      <a href="register.php"   class="topbar-link btn-register<?= $_nav_current==='register.php'?' active':'' ?>">S'inscrire</a>
    <?php endif; ?>
  </div>
</div>

<!-- MENU LATÉRAL -->
<aside id="side" class="side" aria-hidden="true">
  <div class="side-header">
    <img src="logo.jpg" alt="Logo" class="side-logo">
    <span>MATCH MOOV</span>
  </div>
  <nav class="side-nav">
    <a href="index.php"          <?= $_nav_current==='index.php'?' class="active"':'' ?>>🏠 Accueil</a>
    <a href="recherche.php"      <?= $_nav_current==='recherche.php'?' class="active"':'' ?>>🔍 Rechercher</a>
    <?php if ($_nav_logged): ?>
      <a href="cree_activite.php"  <?= $_nav_current==='cree_activite.php'?' class="active"':'' ?>>➕ Créer une activité</a>
      <a href="messagerie.php"    <?= $_nav_current==='messagerie.php'?' class="active"':'' ?>>💬 Messagerie</a>
      <a href="amis.php"          <?= $_nav_current==='amis.php'?' class="active"':'' ?>>👥 Mes amis <?php if($_nav_ami_count>0): ?><span style="background:var(--mm-yellow);color:var(--mm-dark);border-radius:999px;padding:1px 7px;font-size:.72rem;font-weight:700;"><?= $_nav_ami_count ?></span><?php endif; ?></a>
      <a href="notifications.php" <?= $_nav_current==='notifications.php'?' class="active"':'' ?>>🔔 Notifications <?php if($_nav_notif_count>0): ?><span style="background:var(--mm-blue);color:#fff;border-radius:999px;padding:1px 7px;font-size:.72rem;font-weight:700;"><?= $_nav_notif_count ?></span><?php endif; ?></a>
      <a href="rewards.php"       <?= $_nav_current==='rewards.php'?' class="active"':'' ?>>🏆 Récompenses</a>
      <a href="profil.php"        <?= $_nav_current==='profil.php'?' class="active"':'' ?>>👤 Mon profil</a>
      <a href="parametres.php"    <?= $_nav_current==='parametres.php'?' class="active"':'' ?>>⚙️ Paramètres</a>
      <?php if ($_nav_admin): ?>
        <a href="admin.php"       <?= $_nav_current==='admin.php'?' class="active"':'' ?>>🛡️ Administration</a>
      <?php endif; ?>
      <hr>
      <a href="logout.php">🚪 Déconnexion</a>
    <?php else: ?>
      <hr>
      <a href="login.php"    <?= $_nav_current==='login.php'?' class="active"':'' ?>>🔑 Connexion</a>
      <a href="register.php" <?= $_nav_current==='register.php'?' class="active"':'' ?>>✨ S'inscrire</a>
    <?php endif; ?>
    <hr>
    <a href="cgu.php"             <?= $_nav_current==='cgu.php'?' class="active"':'' ?>>📄 CGU</a>
    <a href="confidentialite.php" <?= $_nav_current==='confidentialite.php'?' class="active"':'' ?>>🔒 Confidentialité</a>
  </nav>
</aside>
<div id="overlay" class="overlay" tabindex="-1"></div>

<script>
(function(){
  const hb = document.getElementById('hb');
  const side = document.getElementById('side');
  const overlay = document.getElementById('overlay');
  function openMenu(){ side.classList.add('open'); overlay.classList.add('show'); hb.setAttribute('aria-expanded','true'); side.setAttribute('aria-hidden','false'); }
  function closeMenu(){ side.classList.remove('open'); overlay.classList.remove('show'); hb.setAttribute('aria-expanded','false'); side.setAttribute('aria-hidden','true'); }
  hb.addEventListener('click', ()=>{ side.classList.contains('open') ? closeMenu() : openMenu(); });
  overlay.addEventListener('click', closeMenu);
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeMenu(); });
})();
</script>
