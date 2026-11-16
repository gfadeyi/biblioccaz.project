<?php
require_once 'config.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit();
}

require_once 'avis_lib.php';
if (!verifierCsrf()) {
    header('Location: ./?newsletter=error_csrf#newsletter');
    exit();
}

$email = is_string($_POST['email_newsletter'] ?? null) ? trim($_POST['email_newsletter']) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    header('Location: ./?newsletter=error_email#newsletter');
    exit();
}

require_once 'anti_abus_lib.php';
if (!autoriserActionPublique('newsletter', 5, 3600)) {
    http_response_code(429);
    exit('Trop de demandes. Merci de réessayer plus tard.');
}

require_once 'newsletter_consentement_lib.php';
try {
    $jeton = demanderConfirmationNewsletter($pdo, $email);
    if ($jeton !== null) {
        $mail = creerMailer();
        $mail->Timeout = 5;
        $mail->addAddress($email);
        habillerMail($mail, 'Confirmez votre abonnement à BibliOccaz',
            "Vous avez demandé à recevoir la newsletter BibliOccaz.\n\n" . NOTICE_NEWSLETTER . "\n\nConfirmez votre choix sur la page ouverte par ce bouton, dans les 24 heures. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message : aucun abonnement ne sera activé.",
            SITE_URL . '/newsletter_confirmer?token=' . $jeton, 'Vérifier ma demande');
        $mail->send();
    }
} catch (Throwable $e) {
    insertLog('ERREUR', 'Confirmation de newsletter temporairement indisponible.');
}
header('Location: ./?newsletter=confirmation#newsletter');
exit();
