<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'validation_email_lib.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$saisie = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($saisie) ? $saisie : '';
$valide = validationEmailValide($pdo, $token);
$reussite = false;
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) { http_response_code(403); $erreur = 'La session du formulaire a expiré. Rechargez la page.'; }
    elseif ($valide) { $reussite = confirmerValidationEmail($pdo, $token); $valide = $reussite; }
}
$titreDocument = 'Valider mon adresse email';
include 'header.php';
?>
<div class="page-formulaire">
    <section class="carte-formulaire">
        <h1><?= $reussite ? 'Compte activé' : 'Valider mon adresse email' ?></h1>
        <?php if ($erreur): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
        <?php if ($reussite): ?>
            <p role="status">Votre adresse email est validée. Vous pouvez maintenant vous connecter.</p>
            <a href="connexion" class="bouton bouton-principal">Se connecter</a>
        <?php elseif ($valide): ?>
            <p>Confirmez que cette adresse email vous appartient pour activer votre compte.</p>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <button type="submit" class="bouton bouton-principal">Confirmer mon adresse email</button>
            </form>
        <?php else: ?>
            <p>Ce lien est invalide, a expiré ou a déjà été utilisé. Demandez un nouveau lien si nécessaire.</p>
            <a href="renvoyer_validation" class="bouton bouton-principal">Recevoir un nouveau lien</a>
        <?php endif; ?>
    </section>
</div>
<?php include 'footer.php'; ?>
