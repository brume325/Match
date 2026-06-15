<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$aid = (int)($_GET['id'] ?? 0);
if (!$aid) { header('Location: recherche.php'); exit; }

try {
    $s = $pdo->prepare('SELECT id,nb_max_participants FROM activities WHERE id=:id AND statut="actif"');
    $s->execute([':id'=>$aid]);
    $act = $s->fetch();
    if (!$act) { header('Location: recherche.php'); exit; }

    $s = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE activity_id=:aid');
    $s->execute([':aid'=>$aid]);
    $nb = (int)$s->fetchColumn();
    $max = $act['nb_max_participants'] ? (int)$act['nb_max_participants'] : PHP_INT_MAX;

    if ($nb >= $max) { header('Location: activite.php?id='.$aid.'&msg=Activité+complète'); exit; }

    $pdo->prepare('INSERT IGNORE INTO registrations (user_id,activity_id) VALUES (:uid,:aid)')
        ->execute([':uid'=>$uid,':aid'=>$aid]);

    require_once 'badges.php';
    ajouter_points($uid, 10, $pdo);
    attribuer_badges($uid, $pdo);

} catch (PDOException $e) {}

header('Location: activite.php?id='.$aid.'&msg=Inscription+enregistrée+!');
exit;
