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
    if ($action === 'bannir') {
        $stmt = $pdo->prepare("UPDATE user SET statut = 'banni' WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        insertLog($pdo, 'MODERATION', "Utilisateur ID $id a été banni.");
    } 
    elseif ($action === 'activer') {
        $stmt = $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?");
        $stmt->execute([$id]);
        insertLog($pdo, 'MODERATION', "Utilisateur ID $id a été réactivé.");
    }
}

header("Location: gestion_utilisateurs.php");
exit();