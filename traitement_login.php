<?php
require_once 'config.php';
require_once 'connexion_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pseudo = trim($_POST['pseudo'] ?? '');
$mdp = $_POST['mdp'] ?? '';

$captcha = $_SESSION['captcha'] ?? null;
unset($_SESSION['captcha']);

if (!$captcha || empty($captcha['valide']) || time() - $captcha['valide_le'] > 300) {
    header("Location: login.php?error=captcha");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$pseudo, $pseudo]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        
        $refus = refusConnexionMembre($user);
        if ($refus !== '') {
            header("Location: login.php?error=$refus");
            exit();
        }

        $retour = ouvrirSessionMembre($pdo, $user);
        header('Location: ' . $retour);
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
} catch (RuntimeException $e) {
    insertLog('ERREUR', 'Connexion temporairement indisponible');
    header('Location: login.php?error=technique');
    exit();
}
?>
