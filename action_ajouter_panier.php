<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';

if (isset($_POST['id_livre'], $_POST['etat'], $_POST['prix'], $_POST['titre'], $_POST['image'])) {
    $idLivre = $_POST['id_livre'];
    $etat = $_POST['etat'];
    $prix = floatval($_POST['prix']);
    $titre = $_POST['titre'];
    $image = $_POST['image'];
    $action = $_POST['action'] ?? 'ajouter';

    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    $cleUnique = $idLivre . '_' . strtolower(trim($etat));

    if (isset($_SESSION['panier'][$cleUnique])) {
        $_SESSION['panier'][$cleUnique]['quantite'] += 1;
    } else {
        $_SESSION['panier'][$cleUnique] = [
            'id_livre' => $idLivre,
            'titre' => $titre,
            'etat' => $etat,
            'prix' => $prix,
            'quantite' => 1,
            'image' => $image
        ];
    }

    if ($action === 'acheter') {
        header("Location: panier.php");
        exit();
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(200);
            exit();
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}