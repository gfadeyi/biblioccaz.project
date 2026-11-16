<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/anti_abus_lib.php';
require_once __DIR__ . '/captcha_lib.php';
require_once __DIR__ . '/validation_email_lib.php';
require_once __DIR__ . '/notifications_lib.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$erreur = '';
$recu = false;
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) { http_response_code(403); $erreur = 'La session du formulaire a expiré. Rechargez la page.'; }
    elseif (!captchaRecent()) { $erreur = CAPTCHA_MESSAGE_REQUIS; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) { $erreur = 'Indiquez une adresse email valide.'; }
    else {
        try {
            $jeton = autoriserActionPublique('validation_email', 5, 900) ? demanderValidationEmail($pdo, $email) : null;
            if ($jeton !== null) {
                notifierParMail($email, 'Confirmez votre adresse email BibliOccaz', 'Votre nouveau lien est valable 24 heures et remplace le précédent. Ouvrez la page puis confirmez votre adresse. Si vous n’avez rien demandé, ignorez cet email.', SITE_URL . '/verification_mail?token=' . $jeton, 'Vérifier mon adresse');
            }
        } catch (Throwable $e) { insertLog('ERREUR', 'Renvoi de validation email temporairement indisponible.'); }
        $recu = true;
    }
}
$titreDocument = 'Recevoir un lien de validation';
include __DIR__ . '/header.php';
?>
<div class="page-formulaire">
    <section class="carte-formulaire">
        <h1>Valider mon adresse email</h1>
        <?php if ($recu): ?>
            <p role="status">Si cette adresse correspond à un compte à valider et que le délai entre les demandes est respecté, un email vient de partir. Utilisez le lien le plus récent dans les 24 heures.</p>
            <a href="connexion" class="bouton bouton-principal">Se connecter</a>
        <?php else: ?>
            <p>Recevez un nouveau lien si le précédent a expiré. Vos autres informations restent inchangées.</p>
            <?php if ($erreur): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                <label for="email-validation" class="form-label">Adresse email</label>
                <input id="email-validation" name="email" type="email" class="form-control mb-3" maxlength="150" autocomplete="email" value="<?= htmlspecialchars($email) ?>" required>
                <?php include __DIR__ . '/partie_captcha.php'; ?>
                <button class="bouton bouton-principal" type="submit">Recevoir un nouveau lien</button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
