<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pseudo = $_POST['pseudo'];
    $mdp = $_POST['mdp'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$pseudo, $pseudo]);
    $user = $stmt->fetch();

    if ($user && $mdp === $user['mot_de_passe']) {
        
        if ($user['statut'] === 'suspendu') {
            header("Location: login.php?error=banned");
            exit();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'];

        $stmtLog = $pdo->prepare("INSERT INTO logs (id_user, action_type, description, adresse_ip) VALUES (?, 'CONNEXION', 'Connexion réussie à l\'interface', ?)");
        $stmtLog->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

        $stmtUpdate = $pdo->prepare("UPDATE user SET last_activity = NOW() WHERE id = ?");
        $stmtUpdate->execute([$user['id']]);

        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}