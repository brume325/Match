<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid  = (int)$_SESSION['user_id'];
$aid  = (int)($_POST['activity_id'] ?? 0);
$cont = trim($_POST['contenu'] ?? '');
$note = isset($_POST['note']) && (int)$_POST['note'] >= 1 && (int)$_POST['note'] <= 5 ? (int)$_POST['note'] : null;
if ($aid > 0 && $cont !== '') {
    $pdo->prepare('INSERT INTO commentaire (user_id,activity_id,contenu,note) VALUES (:uid,:aid,:c,:n)')
        ->execute([':uid'=>$uid,':aid'=>$aid,':c'=>$cont,':n'=>$note]);
}
header('Location: activite.php?id='.$aid.'#commentaires');
exit;