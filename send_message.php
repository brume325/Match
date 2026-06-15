<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid     = (int)$_SESSION['user_id'];
$contenu = trim($_POST['contenu'] ?? '');
$grp_id  = (int)($_POST['groupe_id'] ?? 0);

if ($contenu !== '' && $grp_id > 0) {
    // Vérifier que l'utilisateur est inscrit dans ce groupe
    $s = $pdo->prepare('SELECT 1 FROM registrations WHERE user_id=:uid AND activity_id=:aid');
    $s->execute([':uid'=>$uid,':aid'=>$grp_id]);
    if ($s->fetchColumn()) {
        $pdo->prepare('INSERT INTO messages (id_expediteur,id_groupe_activite,contenu) VALUES (:uid,:grp,:c)')
            ->execute([':uid'=>$uid,':grp'=>$grp_id,':c'=>$contenu]);
    }
}
header('Location: activite.php?id='.$grp_id);
exit;
