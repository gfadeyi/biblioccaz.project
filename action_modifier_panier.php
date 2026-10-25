<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';

if (isset($_GET['action'], $_GET['key'])) {
    $action = $_GET['action'];
    $key = $_GET['key'];

    if (isset($_SESSION['panier'][$key])) {
        if ($action === 'plus') {
            // On ne dépasse pas le stock réel de l'exemplaire
            $stmt = $pdo->prepare("SELECT quantite FROM exemplaire WHERE id_exemplaire = ?");
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
}

header("Location: panier.php");
exit();