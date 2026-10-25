<?php
// Fonctions communes pour les dons de livres.

const DON_POINTS = 20;                    // points pour le donneur quand le don est récupéré
const DON_MAX_RESERVATIONS = 3;           // réservations en cours maximum par personne
const DON_DUREE_MAX_JOURS = 60;           // date limite de retrait au plus tard dans 60 jours
const DON_ETATS = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'État usé'];
const DON_STATUTS = [
    'disponible' => ['Disponible', 'success'],
    'reserve'    => ['Réservé', 'warning'],
    'recupere'   => ['Récupéré', 'secondary'],
    'annule'     => ['Annulé', 'danger'],
    'expire'     => ['Expiré', 'dark'],
];

// Statut réel : un don disponible dont la date limite est passée est "expiré"
function statutDon(array $don): string
{
    if ($don['statut'] === 'disponible' && $don['date_limite'] < date('Y-m-d')) {
        return 'expire';
    }
    return $don['statut'];
}

function badgeStatutDon(array $don): string
{
    [$libelle, $couleur] = DON_STATUTS[statutDon($don)] ?? ['?', 'secondary'];
    $texte = in_array($couleur, ['warning'], true) ? 'text-dark' : '';
    return "<span class=\"badge bg-$couleur $texte rounded-pill\">$libelle</span>";
}

// Code court lisible (sans 0/O ni 1/I pour éviter les confusions)
function genererCodeCourt(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $stmt = $pdo->prepare("SELECT 1 FROM don WHERE code_court = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());
    return $code;
}

// Adresse que le QR code ouvre (page de validation du retrait)
function urlValidationDon(array $don): string
{
    return SITE_URL . '/don_valider.php?code=' . $don['code'];
}

function photoDon(?string $photo): string
{
    return $photo ? 'img/dons/' . rawurlencode($photo) : 'img/default.png';
}

// Envoie un mail sans bloquer la page si l'envoi échoue
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
