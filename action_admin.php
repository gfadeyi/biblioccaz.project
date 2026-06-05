<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && $action) {
    if ($action === 'ban') {
        $stmt = $pdo->prepare("UPDATE user SET statut = 'refuse_definitif' WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        insertLog('MODERATION', "Utilisateur ID $id a été banni définitivement.");
    } 
    elseif ($action === 'deban') {
        $stmt = $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?");
        $stmt->execute([$id]);
        insertLog('MODERATION', "Utilisateur ID $id a été réactivé.");
    }
}

header("Location: gestion_utilisateurs.php");
exit();