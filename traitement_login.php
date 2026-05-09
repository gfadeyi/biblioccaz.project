<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pseudo = trim($_POST['pseudo'] ?? '');
$mdp = $_POST['mdp'] ?? '';
$captcha = $_POST['puzzle_pos'] ?? '';

if ($captcha !== 'correct') {
    header("Location: login.php?error=captcha");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$pseudo, $pseudo]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        
        if ($user['est_verifie'] == 0) {
            header("Location: login.php?error=not_verified");
            exit();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];

        $ip = $_SERVER['REMOTE_ADDR'];
        $stmtLog = $pdo->prepare("INSERT INTO logs (id_user, action_type, description, adresse_ip, date_action) VALUES (?, 'CONNEXION', 'Connexion réussie', ?, NOW())");
        $stmtLog->execute([$user['id'], $ip]);

        header("Location: index.php");
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}