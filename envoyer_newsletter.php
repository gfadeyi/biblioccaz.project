<?php
require_once 'config.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

require_once 'avis_lib.php';
if (!verifierCsrf()) {
    header('Location: index.php?newsletter=error_csrf#newsletter');
    exit();
}

$email = trim($_POST['email_newsletter'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    header('Location: index.php?newsletter=error_email#newsletter');
    exit();
}

require_once 'notifications_lib.php';
$stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
$stmt->execute([$email]);
$idMembre = $stmt->fetchColumn();
journalNewsletter($pdo, $email, $idMembre ? (int) $idMembre : null, true, 'site');
if ($idMembre) {
    $pdo->prepare("UPDATE user SET is_newsletter = 1, date_newsletter = NOW() WHERE email = ?")->execute([$email]);
} else {
    $pdo->prepare("
        INSERT INTO newsletter_abonne (email) VALUES (?)
        ON DUPLICATE KEY UPDATE actif = 1, date_inscription = NOW(), date_desinscription = NULL")->execute([$email]);
}
insertLog('NEWSLETTER', "Inscription à la newsletter");

try {
    $mail = creerMailer();
    $mail->addAddress($email);
    habillerMail($mail, "Bienvenue dans la newsletter BIBLIOccaz !",
        "Bonjour,\n\nMerci pour votre inscription ! Vous recevrez désormais nos dernières pépites d'occasion, les dons à récupérer et les événements, une fois par mois, directement dans votre boîte mail.\n\nÀ très bientôt sur BIBLIOccaz !",
        SITE_URL . "/catalogue.php", "Parcourir le catalogue",
        "Vous pouvez vous désabonner à tout moment depuis votre espace profil.");
    $mail->send();
} catch (\Throwable $e) {
    insertLog('ERREUR', "Mail de bienvenue newsletter non envoyé : " . $e->getMessage());
}

header('Location: index.php?newsletter=success#newsletter');
exit();
