<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'livres_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    header("Location: panier.php?error=" . urlencode("Session expirée, merci de réessayer."));
    exit();
}

$action = $_POST['action'] ?? '';
$key = $_POST['key'] ?? '';

if (isset($_SESSION['panier'][$key])) {
    if ($action === 'plus') {
        $stmt = $pdo->prepare("SELECT e.quantite FROM exemplaire e WHERE e.id_exemplaire = ? AND e.is_disponible = 1 AND " . conditionExemplaireMagasin());
        $stmt->execute([$_SESSION['panier'][$key]['id_exemplaire']]);
        $stock = (int) $stmt->fetchColumn();
        if ($_SESSION['panier'][$key]['quantite'] < $stock) {
            $_SESSION['panier'][$key]['quantite'] += 1;
        } else {
            header("Location: panier.php?error=" . urlencode("Stock maximum atteint pour « " . $_SESSION['panier'][$key]['titre'] . " »."));
            exit();
        }
    } elseif ($action === 'moins') {
        $_SESSION['panier'][$key]['quantite'] -= 1;
        if ($_SESSION['panier'][$key]['quantite'] <= 0) {
            unset($_SESSION['panier'][$key]);
        }
    } elseif ($action === 'supprimer') {
        unset($_SESSION['panier'][$key]);
    }
}

header("Location: panier.php");
exit();
