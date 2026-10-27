<?php
require_once 'config.php';
require_once 'ventes_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$idDemande = (int) ($_GET['id'] ?? 0);
try {
    $demande = demandeParId($pdo, $idDemande);
} catch (PDOException $e) {
    $demande = null;
}
if (!$demande || !in_array($idUser, [(int) $demande['id_acheteur'], (int) $demande['id_vendeur']], true) || $demande['statut'] !== 'validee') {
    header("Location: mes_transactions.php");
    exit();
}
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $_SESSION['flash_vente'] = ['danger', "Le reçu ne peut pas être généré pour le moment."];
    header("Location: transaction.php?id=$idDemande");
    exit();
}
insertLog('VENTE', "Téléchargement du reçu de la vente #$idDemande");
telechargerRecuVente($demande);
