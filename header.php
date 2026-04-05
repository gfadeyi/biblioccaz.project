<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['toggle_theme'])) {
    $_SESSION['theme'] = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'light' : 'dark';
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}
$theme_class = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark-mode' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?? "BIBLIOccaz" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $theme_class ?>">

<div class="announcement-bar text-center">
    LIVRAISON OFFERTE DÈS 35 EUROS D'ACHAT
</div>

<nav class="navbar navbar-expand-lg">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="index.php" style="min-width: 150px; text-decoration: none;">BIBLIOccaz</a>
        
        <div class="search-wrapper flex-grow-1 mx-3" style="max-width: 600px;">
            <form action="recherche.php" method="GET" class="position-relative">
                <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #888;"></i>
                <input type="text" name="q" class="form-control" placeholder="Rechercher un livre, un auteur..." style="border-radius: 25px; padding-left: 45px; background-color: var(--input-bg); color: var(--text-color); border: 1px solid var(--border-color);">
            </form>
        </div>

        <div class="nav-links d-flex align-items-center">
            <a href="login.php" class="nav-icons-link me-3 text-decoration-none" style="color: var(--text-color);">
                <i class="bi bi-person"></i> Connexion
            </a>
            <a href="vendre.php" class="btn-sell text-decoration-none">Donner ou revendre</a>
            <div class="ms-3 ps-3 border-start">
                <a href="?toggle_theme=1" class="nav-icons-link text-decoration-none" style="color: var(--text-color);">
                    <i class="bi <?= ($theme_class === 'dark-mode') ? 'bi-sun' : 'bi-moon-stars' ?>"></i>
                </a>
            </div>
        </div>
    </div>
</nav>