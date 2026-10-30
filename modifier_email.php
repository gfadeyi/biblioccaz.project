<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'notifications_lib.php';
require_once 'changement_email_lib.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$idUser = (int) $_SESSION['user_id'];
$disponible = changementEmailDisponible($pdo);
$erreur = '';
$emailSaisi = is_string($_POST['nouvelle_email'] ?? null) ? trim($_POST['nouvelle_email']) : '';
$flash = $_SESSION['flash_changement_email'] ?? null;
unset($_SESSION['flash_changement_email']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$disponible) {
        $erreur = 'Le changement d’adresse email est temporairement indisponible.';
    } elseif (!verifierCsrf()) {
        $erreur = 'La session a expiré. Rechargez la page et réessayez.';
    } else {
        try {
            if (($_POST['action'] ?? '') === 'annuler') {
                annulerChangementEmail($pdo,$idUser);
                $_SESSION['flash_changement_email'] = 'La demande a été annulée. Votre adresse actuelle est conservée.';
                header('Location: modifier_email.php');
                exit();
            }
            if (($_POST['action'] ?? '') !== 'demander') { throw new DomainException('Action inconnue.'); }
            $motDePasse = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
            $demande = demanderChangementEmail($pdo,$idUser,$emailSaisi,$motDePasse);
            $lien = SITE_URL . '/confirmer_email.php?token=' . $demande['jeton'];
            $envoye = notifierParMail($demande['nouvelle_email'],'Confirmez votre nouvelle adresse email',"Bonjour {$demande['pseudo']},\n\nConfirmez votre nouvelle adresse email sur BibliOccaz en ouvrant le lien ci-dessous, puis en validant la confirmation. Il est valable une heure et ne peut être utilisé qu’une seule fois.\n\nVotre ancienne adresse reste utilisée jusque-là.",$lien,'Confirmer mon adresse email');
            if (!$envoye) {
                annulerChangementEmail($pdo,$idUser,hash('sha256',$demande['jeton']));
                throw new DomainException('Le mail n’a pas pu être envoyé. Votre adresse actuelle est conservée. Réessayez dans une minute.');
            }
            notifierParMail($demande['ancienne_email'],'Demande de changement d’adresse email',"Bonjour {$demande['pseudo']},\n\nUne demande de changement d’adresse email a été faite depuis votre compte BibliOccaz. Votre adresse actuelle reste utilisée tant que la nouvelle n’est pas confirmée.\n\nSi vous n’êtes pas à l’origine de cette demande, connectez-vous pour l’annuler et changez votre mot de passe.",SITE_URL . '/modifier_email.php','Consulter la demande');
            insertLog('COMPTE','Demande de changement d’adresse email');
            $_SESSION['flash_changement_email'] = 'Un lien de confirmation a été envoyé à la nouvelle adresse. Il est valable une heure.';
            header('Location: modifier_email.php');
            exit();
        } catch (DomainException $exception) {
            $erreur = $exception->getMessage();
        } catch (PDOException $exception) {
            insertLog('ERREUR','Demande de changement d’adresse email indisponible');
            $erreur = 'Le changement d’adresse email est temporairement indisponible.';
        }
    }
}
$requete = $pdo->prepare('SELECT email FROM user WHERE id=?');
$requete->execute([$idUser]);
$emailActuel = $requete->fetchColumn();
$enCours = $disponible ? demandeChangementEmailEnCours($pdo,$idUser) : null;
include 'header.php';
?>
<div class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><i class="bi bi-envelope" aria-hidden="true"></i></div>
        <h1>Changer mon adresse email</h1>
        <p class="carte-formulaire__intro">Votre adresse actuelle reste utilisée jusqu’à la confirmation de la nouvelle.</p>
        <p class="small text-muted">Adresse actuelle : <strong class="text-break"><?= htmlspecialchars((string) $emailActuel) ?></strong></p>
        <?php if ($flash): ?><div class="alert alert-success small" role="status"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($erreur): ?><div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <?php if (!$disponible): ?><div class="alert alert-warning small" role="status">Cette fonctionnalité est temporairement indisponible. Votre adresse actuelle reste utilisable.</div><?php endif; ?>
        <?php if ($enCours): ?>
            <div class="alert alert-info small" role="status">
                <p>Confirmation en attente pour <strong class="text-break"><?= htmlspecialchars($enCours['nouvelle_email']) ?></strong>, jusqu’au <?= date('d/m/Y à H:i',strtotime($enCours['date_expiration'])) ?>.</p>
                <form method="POST"><input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>"><input type="hidden" name="action" value="annuler"><button class="btn btn-outline-secondary btn-sm">Annuler la demande</button></form>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>">
            <input type="hidden" name="action" value="demander">
            <fieldset <?= $disponible ? '' : 'disabled' ?>>
                <div class="mb-3"><label for="nouvelle-email" class="form-label">Nouvelle adresse email</label><input id="nouvelle-email" name="nouvelle_email" type="email" maxlength="150" autocomplete="email" class="form-control" required value="<?= htmlspecialchars($emailSaisi) ?>"></div>
                <div class="mb-3"><label for="mot-de-passe" class="form-label">Mot de passe actuel</label><input id="mot-de-passe" name="mot_de_passe" type="password" maxlength="1024" autocomplete="current-password" class="form-control" required></div>
                <p class="small text-muted">Le nouveau lien remplace le précédent. Une fois l’adresse confirmée, vous devrez vous reconnecter.</p>
                <button class="bouton bouton-principal w-100">Envoyer le lien de confirmation</button>
            </fieldset>
        </form>
        <p class="carte-formulaire__pied"><a href="modifier_profil.php">Retour à mes informations</a></p>
    </div>
</div>
<?php include 'footer.php'; ?>
