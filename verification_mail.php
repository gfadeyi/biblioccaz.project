<?php
require_once 'config.php';
include 'header.php';

echo '<div class="container my-5 text-center" style="max-width: 600px;">';

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
?>

<div class="card shadow border-0 p-5 mt-4" style="border-radius: 15px;">
    <?php if ($status === "success"): ?>
        <div class="text-success mb-4">
            <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
        </div>
        <h2 class="fw-bold text-dark mb-3">Compte Activé !</h2>
        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
        <div class="d-grid">
            <a href="login.php" class="btn btn-success btn-lg rounded-pill fw-bold py-2 shadow-sm">Se connecter</a>
        </div>

    <?php elseif ($status === "already_verified"): ?>
        <div class="text-warning mb-4">
            <i class="bi bi-info-circle-fill" style="font-size: 4rem;"></i>
        </div>
        <h2 class="fw-bold text-dark mb-3">Déjà validé !</h2>
        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
        <div class="d-grid">
            <a href="login.php" class="btn btn-warning btn-lg rounded-pill fw-bold py-2 text-white shadow-sm">Aller à la page de connexion</a>
        </div>

    <?php else: ?>
        <div class="text-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 4rem;"></i>
        </div>
        <h2 class="fw-bold text-dark mb-3">Erreur de validation</h2>
        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
        <div class="d-grid">
            <a href="inscription.php" class="btn btn-outline-secondary rounded-pill fw-bold py-2">Retour à l'inscription</a>
        </div>
    <?php endif; ?>
</div>

<?php
echo '</div>';
include 'footer.php';
?>