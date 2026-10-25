<?php
require_once 'config.php';
require_once 'evenements_lib.php';
require_once 'avis_lib.php';

$idEvt = (int) ($_POST['id_evenement'] ?? 0);
$retour = "evenement.php?id=$idEvt";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!verifierCsrf()) {
    $_SESSION['flash_evt'] = ['danger', "Session expirée, merci de réessayer."];
} elseif (($_POST['action'] ?? '') === 'inscrire') {
    $_SESSION['flash_evt'] = inscrireAEvenement($pdo, $idEvt, (int) $_SESSION['user_id']);
} elseif (($_POST['action'] ?? '') === 'desinscrire') {
    $_SESSION['flash_evt'] = desinscrireDeEvenement($pdo, $idEvt, (int) $_SESSION['user_id']);
}

if (($_POST['retour'] ?? '') === 'mes_evenements') {
    $retour = 'mes_evenements.php';
}
header("Location: $retour");
exit();
