<?php
require_once 'mailer.php';
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'recuperation_mdp_lib.php';

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

use PHPMailer\PHPMailer\Exception;

$erreur = "";
$succes = "";

const MESSAGE_ENVOI = "Si cette adresse correspond à un compte, un lien de récupération vient d'être envoyé. Il est valable 1 heure.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debutDemande = hrtime(true);
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

    if (!is_string($_POST['csrf'] ?? null) || !verifierCsrf()) {
        $erreur = "Session expirée, merci de réessayer.";
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez entrer une adresse email valide.";
    } else {
        try {
            $user = autoriserDemandeRecuperation() ? preparerRecuperation($pdo, $email) : null;
            if ($user) {
                $url = SITE_URL . "/modifier_mdp.php?token=" . $user['jeton'];
                $mail = creerMailer();
                $mail->Timeout = 5;
                $mail->addAddress($user['email'], $user['prenom'] ?? '');
                habillerMail($mail, "Récupération de votre mot de passe",
                    "Bonjour " . $user['prenom'] . ",\n\nVous avez demandé la réinitialisation de votre mot de passe sur BIBLIOccaz. Le lien ci-dessous est valable 1 heure.\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce mail : votre mot de passe actuel reste valable.",
                    $url, "Choisir un nouveau mot de passe");
                $mail->send();
                insertLog('SECURITE', "Demande de réinitialisation de mot de passe (user #{$user['id']})");
            }
        } catch (Exception | PDOException $e) {
            insertLog('ERREUR', 'Echec de la demande de récupération de mot de passe');
        }

        $succes = MESSAGE_ENVOI;
        $attente = 400000 - (int) ((hrtime(true) - $debutDemande) / 1000);
        if ($attente > 0) {
            usleep($attente);
        }
    }
}

include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire">
        <div class="carte-formulaire__icone"><i class="bi bi-key" aria-hidden="true"></i></div>
        <h1>Mot de passe oublié</h1>
        <p class="carte-formulaire__intro">Indiquez votre adresse email : nous vous envoyons un lien pour choisir un nouveau mot de passe.</p>

        <?php if ($erreur): ?>
            <div class="alert alert-danger small" role="alert"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
            <div class="alert alert-success small" role="status"><?= htmlspecialchars($succes) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-4">
                <label for="email" class="form-label">Adresse email *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="exemple@mail.com" autocomplete="email" required>
            </div>
            <button type="submit" class="bouton bouton-principal">Recevoir le lien</button>
        </form>
        <p class="carte-formulaire__pied"><a href="login.php">Retour à la connexion</a></p>
    </div>
</div>

<?php include 'footer.php'; ?>
