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
    $mail->isHTML(true);
    $mail->Subject = 'Bienvenue dans la newsletter BIBLIOccaz !';
    $mail->Body = '
    <html>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #b6d7a8; border-radius: 10px;">
            <h2 style="color: #274e13;">Merci pour votre inscription !</h2>
            <p>Bonjour,</p>
            <p>Vous recevrez désormais nos dernières pépites littéraires d\'occasion chaque mois directement dans votre boîte mail.</p>
            <p>À très bientôt sur notre plateforme !</p>
            <hr style="border: 0; border-top: 1px solid #93c47d;">
            <p style="font-size: 0.8rem; color: #777;">Ceci est un mail automatique, merci de ne pas y répondre.</p>
        </div>
    </body>
    </html>';
    $mail->send();
} catch (\Throwable $e) {
    insertLog('ERREUR', "Mail de bienvenue newsletter non envoyé : " . $e->getMessage());
}

header('Location: index.php?newsletter=success#newsletter');
exit();
