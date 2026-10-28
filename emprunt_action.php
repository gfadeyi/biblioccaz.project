<?php
require_once 'config.php';
require_once 'emprunts_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? '');
$idEmprunt = (int) ($_POST['id_emprunt'] ?? 0);
$idLivre = (int) ($_POST['id_livre'] ?? 0);
$retour = (string) ($_POST['retour'] ?? '');

function retourEmprunt(string $destination, string $type, string $message): never
{
    $_SESSION[str_starts_with($destination, 'detail_livre.php') ? 'flash_compte' : 'flash_emprunt'] = [$type, $message];
    header("Location: $destination");
    exit();
}

$destination = $retour === 'livre' && $idLivre ? "detail_livre.php?id=$idLivre#emprunt" : 'mes_emprunts.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    retourEmprunt($destination, 'danger', "Session expirée, merci de réessayer.");
}

try {
    verifierEmprunts($pdo);
    if ($action === 'reserver') {
        [$type, $message, $idCree] = reserverEmprunt($pdo, $idLivre, $idUser);
        retourEmprunt($idCree ? 'mes_emprunts.php#emprunt-' . $idCree : $destination, $type, $message);
    }
    [$type, $message] = match ($action) {
        'annuler' => array_slice(annulerReservation($pdo, $idEmprunt, $idUser), 0, 2),
        'renouveler' => renouvelerEmprunt($pdo, $idEmprunt, $idUser),
        'attendre' => rejoindreAttente($pdo, $idLivre, $idUser),
        'quitter_attente' => quitterAttente($pdo, $idLivre, $idUser),
        default => ['danger', "Action inconnue."],
    };
    retourEmprunt($destination, $type, $message);
} catch (PDOException $e) {
    insertLog('ERREUR', "Action d'emprunt « $action » impossible : " . $e->getMessage());
    retourEmprunt($destination, 'danger', "L'emprunt n'est pas encore disponible sur ce site.");
}
