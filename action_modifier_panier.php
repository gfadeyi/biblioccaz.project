<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_GET['action'], $_GET['key'])) {
    $action = $_GET['action'];
    $key = $_GET['key'];

    if (isset($_SESSION['panier'][$key])) {
        if ($action === 'plus') {
            $_SESSION['panier'][$key]['quantite'] += 1;
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