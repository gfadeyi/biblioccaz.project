<?php
require_once 'config.php';

$status = "error";
$message = "";

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE user SET est_verifie = 1, email_verifie = 1, email_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        $status = "success";
        $message = "Votre adresse email a bien été validée ! Vous faites maintenant partie de l'aventure BIBLIOccaz.";
    } else {
        $message = "Le lien de validation est invalide ou a expiré.";
    }
} elseif (!empty($email)) {
    $stmt = $pdo->prepare("SELECT id, est_verifie, email_verifie FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && ($user['est_verifie'] == 1 || $user['email_verifie'] == 1)) {
        $status = "already_verified";
        $message = "Votre adresse email a déjà été validée auparavant. Vous pouvez vous connecter directement.";
    } else {
        $message = "Aucun jeton de vérification valide n'a été fourni.";
    }
} else {
    $message = "Aucun paramètre de vérification n'a été fourni.";
}

$etats = [
    "success" => ["bi-check-circle", "", "Compte activé", "login.php", "Se connecter", "bouton-principal"],
    "already_verified" => ["bi-info-circle", "", "Adresse déjà validée", "login.php", "Aller à la connexion", "bouton-principal"],
    "error" => ["bi-exclamation-triangle", " alerte", "Lien de validation invalide", "inscription.php", "Retour à l'inscription", "bouton-contour"],
];
[$icone, $classeIcone, $titre, $lien, $libelleLien, $classeBouton] = $etats[$status];

include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire text-center">
        <div class="carte-formulaire__icone<?= $classeIcone ?>"><i class="bi <?= $icone ?>" aria-hidden="true"></i></div>
        <h1><?= $titre ?></h1>
        <p class="carte-formulaire__intro"><?= htmlspecialchars($message) ?></p>
        <a href="<?= $lien ?>" class="bouton <?= $classeBouton ?>"><?= $libelleLien ?></a>
    </div>
</div>

<?php include 'footer.php'; ?>
