<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_livre = $_POST['id_livre'];
    $prix = $_POST['prix'];
    $etat = $_POST['etat'];
    
    $id_user = $_SESSION['id_user'] ?? 1; 

    try {
        $stmt = $pdo->prepare("INSERT INTO exemplaire (id_livre, prix, etat, id_user, is_disponible) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$id_livre, $prix, $etat, $id_user]);
        
        header("Location: inventaire_admin.php");
        exit();
    } catch (Exception $e) {
        die("Erreur : " . $e->getMessage());
    }
} else {
    header("Location: inventaire_admin.php");
    exit();
}