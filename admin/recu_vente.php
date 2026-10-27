<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../ventes_lib.php';
exigerDroit('moderation');

$idDemande = (int) ($_GET['id'] ?? 0);
try {
    $demande = demandeParId($pdo, $idDemande);
} catch (PDOException $e) {
    $demande = null;
}
if (!$demande || $demande['statut'] !== 'validee') {
    rediriger('ventes.php', 'warning', "Cette vente n'a pas de reçu.");
}
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    rediriger('ventes.php?id=' . $idDemande, 'danger', "Dompdf est introuvable : lancez « composer install » à la racine du projet.");
}
insertLog('VENTE', "Reçu de la vente #$idDemande téléchargé par l'équipe");
telechargerRecuVente($demande);
