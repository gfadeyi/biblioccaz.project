<?php
require_once 'config.php';
require_once 'ventes_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? '');
$idDemande = (int) ($_POST['id_demande'] ?? 0);
$idAnnonce = (int) ($_POST['id_annonce'] ?? 0);

function retourVente(string $destination, string $type, string $message): never
{
    $_SESSION['flash_vente'] = [$type, $message];
    header("Location: $destination");
    exit();
}

$destination = $idDemande ? "transaction.php?id=$idDemande" : ($idAnnonce ? "annonce.php?id=$idAnnonce" : 'mes_transactions.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    retourVente($destination, 'danger', "Session expirée, merci de réessayer.");
}

try {
    if ($action === 'demander') {
        [$type, $message, $idCree] = creerDemande($pdo, $idAnnonce, $idUser, (string) ($_POST['mode_remise'] ?? ''), (string) ($_POST['message'] ?? ''));
        retourVente($idCree ? "transaction.php?id=$idCree" : $destination, $type, $message);
    }
    $motif = (string) ($_POST['motif'] ?? '');
    [$type, $message] = match ($action) {
        'accepter' => accepterDemande($pdo, $idDemande, $idUser),
        'refuser' => refuserDemande($pdo, $idDemande, $idUser, $motif),
        'annuler' => annulerDemande($pdo, $idDemande, $idUser, $motif),
        'conclure' => conclureVente($pdo, $idDemande, $idUser, null),
        'confirmer' => confirmerVente($pdo, $idDemande, $idUser, 'acheteur'),
        'litige' => signalerLitige($pdo, $idDemande, $idUser, $motif),
        default => ['danger', "Action inconnue."],
    };
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    insertLog('ERREUR', "Action de vente « $action » impossible : " . $e->getMessage());
    [$type, $message] = ['danger', "Les ventes entre lecteurs sont momentanément indisponibles."];
}
retourVente($destination, $type, $message);
