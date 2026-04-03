
<?php
// Démarre la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
 
</head>
    <body>
<header>
    <!-- Barre de navigation principale -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
        <div class="container-fluid px-5">

            <!-- Logo / Nom du site -->
            <a class="navbar-brand fw-bold text-bibli-green mb-0" href="index.php">
                <i class="bi bi-book-half"></i> BIBLIOccaz
            </a>

            <!-- Bouton burger pour mobile -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarMain"
                    aria-controls="navbarMain"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">

                <!-- Liens de navigation (catégories) -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="nouveaute.php">Nouveauté</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="petits_prix.php">Petits prix</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="derniere_chance.php">Dernière chance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="roman.php">Roman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bd_mangas.php">BD / Mangas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jeunesse.php">Jeunesse</a>
                    </li>

                    <!-- Lien Admin uniquement si connecté en tant qu'admin -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="admin.php">Admin</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Barre de recherche -->
                <div class="input-group me-3" style="max-width: 320px;">
                    <input type="text"
                           class="form-control search-bar"
                           placeholder="Rechercher par auteur, titre, ISBN..."
                           aria-label="Recherche">
                    <a href="recherche.php" class="btn btn-search px-4" type="button">
                        <i class="bi bi-search"></i>
                    </a>
                </div>

                <!-- Icônes utilisateur et panier -->
                <div class="d-flex align-items-center gap-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Utilisateur connecté -->
                        <a href="profil.php" class="text-dark text-decoration-none" title="Mon compte">
                            <i class="bi bi-person-fill fs-4"></i>
                        </a>
                        <a href="logout.php" class="text-dark text-decoration-none" title="Se déconnecter">
                            <i class="bi bi-box-arrow-right fs-4"></i>
                        </a>
                    <?php else: ?>
                        <!-- Utilisateur non connecté -->
                        <a href="login.php" class="text-dark text-decoration-none" title="Se connecter">
                            <i class="bi bi-person fs-4"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Panier -->
                    <a href="cart.php" class="text-dark text-decoration-none position-relative" title="Mon panier">
                        <i class="bi bi-cart3 fs-4"></i>
                        <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= htmlspecialchars($_SESSION['cart_count']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

            </div>
        </div>
    </nav>
</header>