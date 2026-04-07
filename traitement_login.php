<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['pseudo']);
    $mdp = $_POST['mdp'];

    if (empty($identifiant) || empty($mdp)) {
        header("Location: login.php?error=empty");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$identifiant, $identifiant]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        
        if ($user['statut'] === 'banni') {
            header("Location: login.php?error=banned");
            exit();
        }

        if ($user['statut'] === 'suspendu') {
            header("Location: login.php?error=suspended");
            exit();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];

        insertLog($pdo, 'CONNEXION', "Connexion réussie de l'utilisateur : " . $user['pseudo']);

        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();

    } else {
        insertLog($pdo, 'CONNEXION_ECHEC', "Tentative de connexion échouée pour : " . $identifiant);
        header("Location: login.php?error=1");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>