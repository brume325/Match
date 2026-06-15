<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$data = [];

$s = $pdo->prepare('SELECT id,prenom,nom,email,age,classe,organisation,points,created_at FROM users WHERE id=:uid');
$s->execute([':uid'=>$uid]);
$data['profil'] = $s->fetch();

$s = $pdo->prepare('SELECT titre,categorie,lieu,date_activite,created_at FROM activities WHERE id_organisateur=:uid');
$s->execute([':uid'=>$uid]);
$data['activites_creees'] = $s->fetchAll();

$s = $pdo->prepare('SELECT a.titre,a.date_activite,r.date_inscription FROM registrations r JOIN activities a ON a.id=r.activity_id WHERE r.user_id=:uid');
$s->execute([':uid'=>$uid]);
$data['participations'] = $s->fetchAll();

$s = $pdo->prepare('SELECT b.nom,b.description,ub.obtenu_le FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=:uid');
$s->execute([':uid'=>$uid]);
$data['badges'] = $s->fetchAll();

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="matchmoov-mes-donnees-'.date('Y-m-d').'.json"');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;