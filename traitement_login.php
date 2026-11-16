<?php
require_once 'config.php';
require_once 'connexion_lib.php';
require_once 'avis_lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verifierCsrf()) {
    header('Location: connexion?error=csrf');
    exit();
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pseudo = is_string($_POST['pseudo'] ?? null) ? trim($_POST['pseudo']) : '';
$mdp = is_string($_POST['mdp'] ?? null) ? $_POST['mdp'] : '';
if ($pseudo === '' || mb_strlen($pseudo) > 150 || strlen($mdp) > 72 || str_contains($mdp, "\0")) {
    header('Location: connexion?error=1');
    exit();
}

require_once 'captcha_lib.php';
if (!captchaRecent()) {
    header("Location: connexion?error=captcha");
    exit();
}

require_once 'anti_abus_lib.php';
if (!autoriserActionPublique('connexion_client', 30, 900)) {
    header('Location: connexion?error=limite');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo = ? OR email = ?");
    $stmt->execute([$pseudo, $pseudo]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        
        $refus = refusConnexionMembre($user);
        if ($refus !== '') {
            header("Location: connexion?error=$refus");
            exit();
        }

        $retour = ouvrirSessionMembre($pdo, $user);
        if (($_POST['memoriser_identifiant'] ?? '') === '1') { memoriserIdentifiant($pseudo); } else { oublierIdentifiantMemorise(); }
        header('Location: ' . $retour);
        exit();
    } else {
        $_SESSION['identifiant_connexion'] = $pseudo;
        compterEchecConnexionCaptcha();
        header("Location: connexion?error=1");
        exit();
    }
} catch (RuntimeException $e) {
    insertLog('ERREUR', 'Connexion temporairement indisponible');
    header('Location: connexion?error=technique');
    exit();
}
?>
