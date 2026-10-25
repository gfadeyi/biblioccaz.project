<?php

function notifierParMail(string $email, string $sujet, string $texte): void
{
    try {
        require_once __DIR__ . '/mailer.php';
        $mail = creerMailer();
        $mail->addAddress($email);
        $mail->isHTML(false);
        $mail->Subject = "BIBLIOccaz - " . $sujet;
        $mail->Body = $texte . "\n\n---------------\nCeci est un mail automatique, merci de ne pas y répondre.";
        $mail->send();
    } catch (\Throwable $e) {
        insertLog('ERREUR', "Mail non envoyé ($sujet) : " . $e->getMessage());
    }
}
