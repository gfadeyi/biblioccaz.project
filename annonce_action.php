<?php
require_once 'config.php';
require_once 'annonces_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function retourMesAnnonces(string $type, string $message): never
{
    $_SESSION['flash_annonce'] = [$type, $message];
    header("Location: mes_annonces.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    retourMesAnnonces('danger', "Session expirée, merci de réessayer.");
}

$idUser = (int) $_SESSION['user_id'];
$idAnnonce = (int) ($_POST['id_annonce'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$annonce = annonceParId($pdo, $idAnnonce);
if (!$annonce || (int) $annonce['id_proprietaire'] !== $idUser) {
    retourMesAnnonces('danger', "Annonce introuvable.");
}

if ($action === 'retirer') {
    $stmt = $pdo->prepare("UPDATE exemplaire SET statut_annonce = 'retiree', motif_moderation = NULL
                           WHERE id_exemplaire = ? AND id_proprietaire = ? AND statut_annonce IN ('en_validation', 'publiee')");
    $stmt->execute([$idAnnonce, $idUser]);
    if ($stmt->rowCount() === 0) {
        retourMesAnnonces('warning', "Cette annonce ne peut pas être retirée.");
    }
    insertLog('ANNONCE', "Retrait de l'annonce #$idAnnonce par son vendeur");
    retourMesAnnonces('success', "Annonce retirée : elle n'est plus visible. Vous pouvez la remettre en ligne quand vous voulez.");
}

if ($action === 'remettre') {
    $raison = raisonInterdictionVente($pdo, $idUser);
    if ($raison !== '') {
        retourMesAnnonces('warning', $raison);
    }
    if (nombreAnnoncesActives($pdo, $idUser) >= ANNONCES_ACTIVES_MAX) {
        retourMesAnnonces('warning', "Vous avez déjà " . ANNONCES_ACTIVES_MAX . " annonces en cours.");
    }
    $stmt = $pdo->prepare("UPDATE exemplaire SET statut_annonce = 'en_validation', motif_moderation = NULL, id_moderateur = NULL, date_moderation = NULL
                           WHERE id_exemplaire = ? AND id_proprietaire = ? AND statut_annonce = 'retiree' AND motif_moderation IS NULL");
    $stmt->execute([$idAnnonce, $idUser]);
    if ($stmt->rowCount() === 0) {
        retourMesAnnonces('warning', "Modifiez cette annonce pour la proposer de nouveau.");
    }
    insertLog('ANNONCE', "Remise en validation de l'annonce #$idAnnonce par son vendeur");
    retourMesAnnonces('success', "Annonce renvoyée à l'équipe : elle sera en ligne dès sa validation.");
}

if ($action === 'supprimer') {
    if (!supprimerAnnonce($pdo, $idAnnonce)) {
        retourMesAnnonces('warning', "Cette annonce est liée à une vente en cours : elle ne peut pas être supprimée.");
    }
    insertLog('ANNONCE', "Suppression de l'annonce #$idAnnonce par son vendeur");
    retourMesAnnonces('success', "Annonce supprimée.");
}

retourMesAnnonces('danger', "Action inconnue.");
