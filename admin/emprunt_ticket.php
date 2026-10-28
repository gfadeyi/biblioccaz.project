<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../emprunts_lib.php';
exigerDroit('catalogue');

$idEmprunt = (int) ($_GET['id'] ?? 0);
try {
    $emprunt = empruntParId($pdo, $idEmprunt);
} catch (PDOException $e) {
    $emprunt = null;
}
if (!$emprunt || !in_array($emprunt['statut'], ['reserve', 'en_cours', 'rendu'], true)) {
    rediriger('emprunts.php', 'warning', "Ticket introuvable.");
}
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    rediriger("emprunts.php?id=$idEmprunt", 'danger', "Dompdf est introuvable : lancez « composer install » à la racine du projet.");
}
telechargerTicketEmprunt($emprunt, $pdo);
