<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'notifications_lib.php';
require_once 'changement_email_lib.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$saisieJeton = $_POST['token'] ?? $_GET['token'] ?? '';
$jeton = is_string($saisieJeton) ? $saisieJeton : '';
$reussite = !empty($_SESSION['confirmation_email_reussie']);
unset($_SESSION['confirmation_email_reussie']);
$erreur = '';
$disponible = changementEmailDisponible($pdo);
$demande = $disponible ? demandeEmailParJeton($pdo,$jeton) : null;
if ($demande && isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] !== (int) $demande['id_user']) {
    $demande = null;
    $erreur = 'Ce lien concerne un autre compte. Déconnectez-vous de ce compte, puis ouvrez à nouveau le lien reçu par mail.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$reussite) {
    if (!$disponible || !$demande) {
        $erreur = $erreur ?: 'Ce lien est invalide ou expiré. Demandez un nouveau lien depuis votre compte.';
    } elseif (!verifierCsrf()) {
        $erreur = 'La session a expiré. Ouvrez à nouveau le lien reçu par mail et réessayez.';
    } else {
        try {
            $membre = confirmerChangementEmail($pdo,$jeton,isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
            notifierParMail($membre['ancienne_email'],'Votre adresse email a été modifiée',"Bonjour {$membre['pseudo']},\n\nL’adresse email de votre compte BibliOccaz vient d’être changée après confirmation de la nouvelle adresse. Si vous n’êtes pas à l’origine de ce changement, contactez l’équipe du site.");
            notifierParMail($membre['nouvelle_email'],'Votre nouvelle adresse email est confirmée',"Bonjour {$membre['pseudo']},\n\nVotre nouvelle adresse email est confirmée. Vous pouvez vous reconnecter avec cette adresse ou votre pseudo.",SITE_URL . '/login.php','Me connecter');
            insertLog('COMPTE','Changement d’adresse email confirmé pour le membre #' . $membre['id_user']);
            detruireSessionCourante();
            demarrerSession();
            session_regenerate_id(true);
            $_SESSION['confirmation_email_reussie'] = true;
            header('Location: confirmer_email.php');
            exit();
        } catch (DomainException $exception) {
            $erreur = $exception->getMessage();
        } catch (PDOException $exception) {
            insertLog('ERREUR','Confirmation de changement d’adresse email indisponible');
            $erreur = 'La confirmation est temporairement indisponible. Votre adresse actuelle reste utilisable.';
        }
    }
}
include 'header.php';
?>
<div class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><i class="bi bi-envelope-check" aria-hidden="true"></i></div>
        <h1><?= $reussite ? 'Adresse email confirmée' : 'Confirmer mon adresse email' ?></h1>
        <?php if ($reussite): ?>
            <p class="carte-formulaire__intro">Votre nouvelle adresse est maintenant utilisée. Reconnectez-vous avec cette adresse ou votre pseudo.</p>
            <a href="login.php" class="bouton bouton-principal w-100">Me connecter</a>
        <?php elseif ($demande): ?>
            <p class="carte-formulaire__intro">Confirmez l’utilisation de cette adresse pour votre compte BibliOccaz.</p>
            <p class="text-break fw-bold"><?= htmlspecialchars($demande['nouvelle_email']) ?></p>
            <?php if ($erreur): ?><div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
            <p class="small text-muted">Vos sessions seront fermées après confirmation. Vous pourrez ensuite vous reconnecter.</p>
            <form method="POST" action="confirmer_email.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($jeton) ?>">
                <button class="bouton bouton-principal w-100">Confirmer cette adresse</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning small" role="status"><?= htmlspecialchars($erreur ?: 'Ce lien est invalide ou expiré. Demandez un nouveau lien depuis votre compte.') ?></div>
            <a href="modifier_email.php" class="bouton bouton-principal w-100">Demander un nouveau lien</a>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
