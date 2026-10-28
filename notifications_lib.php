<?php

function notifierParMail(string $email, string $sujet, string $texte, ?string $lienBouton = null, ?string $libelleBouton = null): bool
{
    try {
        require_once __DIR__ . '/mailer.php';
        $mail = creerMailer();
        $mail->addAddress($email);
        habillerMail($mail, $sujet, $texte, $lienBouton, $libelleBouton);
        return $mail->send();
    } catch (\Throwable $e) {
        insertLog('ERREUR', "Mail non envoyé ($sujet) : " . $e->getMessage());
        return false;
    }
}

function journalNewsletter(PDO $pdo, string $email, ?int $idUser, bool $inscription, string $source): void
{
    try {
        $pdo->prepare("INSERT INTO newsletter_historique (email, id_user, action, source) VALUES (?, ?, ?, ?)")
            ->execute([$email, $idUser, $inscription ? 'inscription' : 'desinscription', $source]);
    } catch (PDOException $e) {
    }
}
