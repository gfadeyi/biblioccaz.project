<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioccaz - Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f9f9f9; }
        .bg-bibli-green { background-color: #7BB57B; } 
        .text-bibli-green { color: #7BB57B; }
        .search-bar { border-radius: 20px; }
        .card-book { border: none; transition: transform 0.2s; }
        .card-book:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

    <header class="py-3 bg-white border-bottom shadow-sm">
        <div class="container-fluid px-5">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h3 class="fw-bold text-bibli-green mb-0">BIBLIOccaz</h3>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control search-bar" placeholder="Rechercher par auteur, titre, ISBN...">
                        <button class="btn btn-success search-bar" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <a href="admin.php" class="btn btn-outline-secondary me-2 btn-sm"><i class="bi bi-lock"></i> Admin</a>
                    <a href="#" class="text-dark me-3 text-decoration-none"><i class="bi bi-person fs-4"></i></a>
                    <a href="#" class="text-dark text-decoration-none"><i class="bi bi-cart3 fs-4"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid px-5 my-4">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-white sidebar rounded shadow-sm p-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#">Nouveau chez nous</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">Petits prix</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">Dernière chance</a></li>
                    <hr>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">Romans</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">BD / Mangas</a></li>
                </ul>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="bg-white p-5 rounded shadow-sm mb-4 border border-success">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <span class="badge bg-bibli-green mb-2">Bon plan</span>
                            <h1 class="display-5 fw-bold text-success">Uniquement chez Biblioccaz !</h1>
                            <p class="lead"><strong>-5%</strong> dès 30€ d'achats et <strong>-10%</strong> dès 50€ d'achats</p>
                            <button class="btn btn-warning btn-lg">J'en profite</button>
                        </div>
                        <div class="col-md-4 text-center">
                            <h2 class="display-3 fw-bold text-bibli-green">-10%</h2>
                        </div>
                    </div>
                </div>

                <h3 class="mt-5 mb-4 fw-bold">Nos meilleures ventes</h3>
                
                <?php
                $meilleuresVentes = [
                    ["titre" => "Le Crépuscule des dieux", "auteur" => "Guillaume Musso", "prix" => 14.90, "img" => "https://covers.openlibrary.org/b/id/13018260-M.jpg"],
                    ["titre" => "Harry Potter et la Coupe de Feu", "auteur" => "J.K. Rowling", "prix" => 32.00, "img" => "https://covers.openlibrary.org/b/id/10522967-M.jpg"],
                    ["titre" => "La chronique des Bridgerton", "auteur" => "Julia Quinn", "prix" => 9.50, "img" => "https://covers.openlibrary.org/b/id/10850239-M.jpg"],
                    ["titre" => "Heated Rivalry", "auteur" => "Rachel Reid", "prix" => 11.00, "img" => "https://covers.openlibrary.org/b/id/12836338-M.jpg"],
                ];
                ?>

                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <?php foreach ($meilleuresVentes as $livre): ?>
                    <div class="col">
                        <div class="card h-100 card-book shadow-sm text-center p-2 bg-white">
                            <img src="<?php echo $livre['img']; ?>" class="card-img-top mx-auto mt-2" alt="Couverture" style="width: 100px; height: 150px; object-fit: cover;">
                            <div class="card-body">
                                <p class="card-text text-muted mb-1" style="font-size: 0.8rem;"><?php echo $livre['auteur']; ?></p>
                                <h6 class="card-title fw-bold" style="font-size: 0.9rem;"><?php echo $livre['titre']; ?></h6>
                                <p class="card-text fw-bold text-success fs-5 mt-2"><?php echo number_format($livre['prix'], 2); ?> €</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>

</body>
</html>