<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIBLIOccaz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Francois+One&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="announcement-bar container-fluid">
    <div class="container text-center text-uppercase">
        BIBLIOccaz : Donnez une seconde vie à vos lectures — Livraison offerte dès 35 euros d'achat
    </div>
</div>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">BIBLIOccaz</a>

        <?php 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $page = basename($_SERVER['PHP_SELF']);
        $pages_gestion = ['admin.php', 'inventaire_admin.php', 'ajouter_livre.php', 'modifier_livre.php', 'gestion_users.php'];
        $est_page_gestion = in_array($page, $pages_gestion);
        ?>

        <?php if (!$est_page_gestion): ?>
        <form class="d-none d-lg-block ms-4 me-auto">
            <div class="search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control search-input" placeholder="Rechercher un livre, un auteur...">
            </div>
        </form>
        <?php endif; ?>

        <div class="d-flex align-items-center ms-auto">
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                <a href="admin.php" class="nav-icons-link">
                    <i class="bi bi-speedometer2 fs-5"></i> <span>Dashboard</span>
                </a>
                <a href="logout.php" class="nav-icons-link ms-3 text-danger">
                    <i class="bi bi-box-arrow-right fs-5"></i> <span>Déconnexion</span>
                </a>
                
                <?php if (!$est_page_gestion): ?>
                <a href="panier.php" class="nav-icons-link ms-3">
                    <i class="bi bi-cart3 fs-5"></i> <span>Panier</span>
                </a>
                <a href="vendre.php" class="btn btn-sell ms-4">
                    Donner ou revendre
                </a>
                <?php endif; ?>

            <?php else: ?>
                <a href="login.php" class="nav-icons-link">
                    <i class="bi bi-person fs-5"></i> <span>Connexion</span>
                </a>
                <a href="panier.php" class="nav-icons-link ms-3">
                    <i class="bi bi-cart3 fs-5"></i> <span>Panier</span>
                </a>
                <a href="vendre.php" class="btn btn-sell ms-4">
                    Donner ou revendre
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="py-4">