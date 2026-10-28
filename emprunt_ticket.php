<?php
require_once 'config.php';
require_once 'emprunts_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$idEmprunt = (int) ($_GET['id'] ?? 0);
try {
    $emprunt = empruntParId($pdo, $idEmprunt);
} catch (PDOException $e) {
    $emprunt = null;
}
if (!$emprunt || (int) $emprunt['id_user'] !== $idUser || !in_array($emprunt['statut'], ['reserve', 'en_cours', 'rendu'], true)) {
    header("Location: mes_emprunts.php");
    exit();
}
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $_SESSION['flash_emprunt'] = ['danger', "Le ticket ne peut pas être généré pour le moment."];
    header("Location: mes_emprunts.php");
    exit();
}
telechargerTicketEmprunt($emprunt, $pdo);
