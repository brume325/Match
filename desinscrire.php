<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$aid = (int)($_GET['id'] ?? 0);
if ($aid > 0) {
    $pdo->prepare('DELETE FROM registrations WHERE user_id=:uid AND activity_id=:aid')
        ->execute([':uid'=>$uid,':aid'=>$aid]);
}
header('Location: activite.php?id='.$aid.'&msg=Désinscription+effectuée');
exit;