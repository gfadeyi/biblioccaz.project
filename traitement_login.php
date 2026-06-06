<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Récupération des données du formulaire
$pseudo = trim($_POST['pseudo'] ?? '');
$mdp = $_POST['mdp'] ?? '';
$captcha_token = $_POST['captcha_token'] ?? ''; // Correction du nom ici

// 2. Vérification stricte du CAPTCHA
if (empty($captcha_token)) {
    header("Location: login.php?error=captcha");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$pseudo, $pseudo]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        
        if ($user['email_verifie'] == 0) {
            header("Location: login.php?error=not_verified");
            exit();
        }

        if ($user['statut'] === 'en_attente_moderateur') {
            header("Location: login.php?error=en_attente_moderateur");
            exit();
        }

        if ($user['statut'] === 'refuse_temporaire') {
            header("Location: login.php?error=refuse_temporaire");
            exit();
        }

        if ($user['statut'] === 'refuse_definitif') {
            header("Location: login.php?error=refuse_definitif");
            exit();
        }

        $premiere_connexion_modo = false;
        if ($user['role'] === 'moderateur' && $user['statut'] === 'valide_moderateur') {
            $update = $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?");
            $update->execute([$user['id']]);
            $premiere_connexion_modo = true;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];

        $_SESSION['connexion_time'] = time();

        insertLog('CONNEXION', "Connexion réussie");

        if ($premiere_connexion_modo) {
            $_SESSION['welcome_modo'] = "Bienvenue dans l'équipe de modération ! Votre compte a été validé par l'administrateur. Votre mission sera désormais de modérer nos livres et d'en modifier les descriptifs.";
        }

        header("Location: index.php");
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>