<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$idDon = (int) ($_POST['id_don'] ?? 0);
$idUser = (int) $_SESSION['user_id'];
$estAdmin = ($_SESSION['role'] ?? '') === 'admin';
$retour = "don_detail.php?id=$idDon";

function terminer(string $url, string $type, string $message): void
{
    $_SESSION['flash_don'] = [$type, $message];
    header("Location: $url");
    exit();
}

if (!verifierCsrf()) {
    terminer($retour, 'danger', "Session expirée, merci de réessayer.");
}

$stmt = $pdo->prepare("
    SELECT d.*, dn.email AS donneur_email, dn.pseudo AS donneur_pseudo
    FROM don d INNER JOIN user dn ON dn.id = d.id_donneur WHERE d.id_don = ?");
$stmt->execute([$idDon]);
$don = $stmt->fetch();
if (!$don) {
    terminer('dons.php', 'danger', "Ce don n'existe pas.");
}
$statut = statutDon($don);

switch ($_POST['action'] ?? '') {
    case 'reserver':
        if ((int) $don['id_donneur'] === $idUser) {
            terminer($retour, 'danger', "Vous ne pouvez pas réserver votre propre don.");
        }
        $nb = $pdo->prepare("SELECT COUNT(*) FROM don WHERE id_beneficiaire = ? AND statut = 'reserve'");
        $nb->execute([$idUser]);
        if ((int) $nb->fetchColumn() >= DON_MAX_RESERVATIONS) {
            terminer($retour, 'danger', "Vous avez déjà " . DON_MAX_RESERVATIONS . " réservations en cours. Récupérez-les ou annulez-en une avant d'en faire une autre.");
        }
        $maj = $pdo->prepare("UPDATE don SET statut = 'reserve', id_beneficiaire = ?, date_reservation = NOW()
                              WHERE id_don = ? AND statut = 'disponible' AND date_limite >= CURDATE()");
        $maj->execute([$idUser, $idDon]);
        if (!$maj->rowCount()) {
            terminer($retour, 'danger', "Trop tard, ce livre n'est plus disponible.");
        }
        insertLog('DON', "Don #$idDon réservé");
        notifierParMail($don['donneur_email'], "Votre don a été réservé",
            "Bonjour {$don['donneur_pseudo']},\n\nVotre don « {$don['titre']} » vient d'être réservé par {$_SESSION['pseudo']}.\n"
            . "Retrouvez son contact et votre QR code ici :\n" . SITE_URL . "/don_detail.php?id=$idDon");
        terminer($retour, 'success', "C'est réservé ! Contactez le donneur pour convenir du retrait.");

    case 'annuler_reservation':
        $maj = $pdo->prepare("UPDATE don SET statut = 'disponible', id_beneficiaire = NULL, date_reservation = NULL
                              WHERE id_don = ? AND statut = 'reserve' AND id_beneficiaire = ?");
        $maj->execute([$idDon, $idUser]);
        if ($maj->rowCount()) {
            insertLog('DON', "Réservation du don #$idDon annulée par le bénéficiaire");
            notifierParMail($don['donneur_email'], "Réservation annulée",
                "Bonjour {$don['donneur_pseudo']},\n\nLa réservation de votre don « {$don['titre']} » a été annulée. Il est de nouveau disponible.");
        }
        terminer($retour, 'success', "Votre réservation est annulée.");

    case 'liberer':
        $maj = $pdo->prepare("UPDATE don SET statut = 'disponible', id_beneficiaire = NULL, date_reservation = NULL
                              WHERE id_don = ? AND statut = 'reserve' AND id_donneur = ?");
        $maj->execute([$idDon, $idUser]);
        insertLog('DON', "Réservation du don #$idDon libérée par le donneur");
        terminer($retour, 'success', "Le don est de nouveau disponible.");

    case 'annuler':
        if ((int) $don['id_donneur'] !== $idUser && !$estAdmin) {
            terminer($retour, 'danger', "Action non autorisée.");
        }
        $maj = $pdo->prepare("UPDATE don SET statut = 'annule' WHERE id_don = ? AND statut IN ('disponible', 'reserve')");
        $maj->execute([$idDon]);
        insertLog('DON', "Don #$idDon annulé" . ($estAdmin && (int) $don['id_donneur'] !== $idUser ? ' par un administrateur' : ''));
        terminer($retour, 'success', "Le don est annulé.");
}

header("Location: $retour");
exit();
