<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/newsletter_consentement_lib.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$mode = ($_GET['mode'] ?? '') === 'desinscription' ? 'desinscription' : 'confirmation';
$saisie = $_POST['token'] ?? $_GET['token'] ?? '';
$jeton = is_string($saisie) ? $saisie : '';
$valide = $mode === 'desinscription' ? desinscriptionNewsletterValide($pdo, $jeton) : confirmationNewsletterValide($pdo, $jeton);
$reussite = false;
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) { http_response_code(403); $erreur = 'La session du formulaire a expiré. Rechargez la page.'; }
    elseif ($valide) {
        $reussite = $mode === 'desinscription' ? desinscrireNewsletterParJeton($pdo, $jeton) : confirmerNewsletter($pdo, $jeton);
        $valide = $reussite;
    }
}
$titreDocument = $mode === 'desinscription' ? 'Se désabonner de la newsletter' : 'Confirmer la newsletter';
include __DIR__ . '/header.php';
?>
<div class="page-formulaire">
    <section class="carte-formulaire">
        <h1><?= htmlspecialchars($titreDocument) ?></h1>
        <?php if ($erreur): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
        <?php if ($reussite): ?>
            <p role="status"><?= $mode === 'desinscription' ? 'Vous ne recevrez plus la newsletter. Vous pourrez vous réabonner quand vous le souhaiterez.' : 'Votre abonnement est confirmé. À bientôt pour de nouvelles lectures !' ?></p>
            <a href="index.php" class="bouton bouton-principal">Retour à l’accueil</a>
        <?php elseif ($valide): ?>
            <p><?= $mode === 'desinscription' ? 'Confirmez votre choix pour arrêter les emails de la newsletter. Aucun compte ni mot de passe n’est nécessaire.' : htmlspecialchars(NOTICE_NEWSLETTER) ?></p>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($jeton) ?>">
                <button class="bouton bouton-principal" type="submit"><?= $mode === 'desinscription' ? 'Me désabonner' : 'Confirmer mon abonnement' ?></button>
            </form>
        <?php else: ?>
            <p>Ce lien est invalide, expiré ou a déjà été utilisé. Les liens d’un ancien abonnement ne modifient pas un réabonnement récent.</p>
            <a href="index.php#newsletter" class="bouton bouton-principal">Retour à la newsletter</a>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
