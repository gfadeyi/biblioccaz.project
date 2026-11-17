<?php
require_once 'config.php';
require_once 'emprunts_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? '');
$idEmprunt = (int) ($_POST['id_emprunt'] ?? 0);
$idLivre = (int) ($_POST['id_livre'] ?? 0);
$idBoutique = filter_var($_POST['id_boutique'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
$retour = (string) ($_POST['retour'] ?? '');

function retourEmprunt(string $destination, string $type, string $message): never
{
    $_SESSION[str_starts_with($destination, 'livre?') ? 'flash_compte' : 'flash_emprunt'] = [$type, $message];
    header("Location: $destination");
    exit();
}

$destination = $retour === 'livre' && $idLivre ? "livre?id=$idLivre#emprunt" : 'mes_emprunts';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    retourEmprunt($destination, 'danger', "Session expirée, merci de réessayer.");
}

try {
    verifierEmprunts($pdo);
    if ($action === 'reserver') {
        [$type, $message, $idCree] = reserverEmprunt($pdo, $idLivre, $idUser, $idBoutique);
        retourEmprunt($idCree ? 'mes_emprunts#emprunt-' . $idCree : $destination, $type, $message);
    }
    [$type, $message] = match ($action) {
        'annuler' => array_slice(annulerReservation($pdo, $idEmprunt, $idUser), 0, 2),
        'renouveler' => renouvelerEmprunt($pdo, $idEmprunt, $idUser),
        'attendre' => rejoindreAttente($pdo, $idLivre, $idUser, $idBoutique),
        'quitter_attente' => quitterAttente($pdo, $idLivre, $idUser, $idBoutique ?: null),
        default => ['danger', "Action inconnue."],
    };
    retourEmprunt($destination, $type, $message);
} catch (PDOException $e) {
    insertLog('ERREUR', "Action d'emprunt « $action » impossible : " . $e->getMessage());
    retourEmprunt($destination, 'danger', "L'emprunt n'est pas encore disponible sur ce site.");
}
