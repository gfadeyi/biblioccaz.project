<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioccaz - Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #d9ead3; /* Vert très clair en fond */
            color: #274e13; /* Vert très foncé pour le texte */
        }
        
        header { background-color: #ffffff !important; border-bottom: 3px solid #93c47d !important; }
        .sidebar { background-color: #ffffff !important; border: 1px solid #b6d7a8; }
        
        .text-bibli-green { color: #274e13 !important; }
        .text-success { color: #274e13 !important; } /* On remplace le vert flash par ton vert foncé */
        
        .search-bar { border-radius: 20px; border: 2px solid #93c47d; }
        .btn-search { 
            background-color: #8fce00 !important; 
            border: none; 
            color: #274e13; 
            font-weight: bold;
            border-radius: 20px;
        }

        .promo-banner {
            background-color: #ffffff !important;
            border: 3px solid #8fce00 !important;
            border-radius: 15px;
        }
        .badge-promo { background-color: #8fce00 !important; color: #274e13; }
        .btn-promo { background-color: #274e13 !important; color: #ffffff !important; border: none; }

        .card-book { 
            border: 2px solid #b6d7a8 !important; 
            transition: transform 0.2s, box-shadow 0.2s; 
            border-radius: 12px;
        }
        .card-book:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 20px rgba(39, 78, 19, 0.2) !important;
            border-color: #8fce00 !important;
        }
        .price-tag { color: #8fce00 !important; font-weight: 800; }
        
        .nav-link:hover { color: #8fce00 !important; background-color: #f8fdf7; }
    </style>
</head>
<body>

    <header class="py-3 shadow-sm">
        <div class="container-fluid px-5">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h3 class="fw-bold text-bibli-green mb-0"><i class="bi bi-book-half"></i> BIBLIOccaz</h3>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control search-bar" placeholder="Rechercher par auteur, titre, ISBN...">
                        <button class="btn btn-search px-4" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <a href="login.php" class="btn btn-outline-dark me-2 btn-sm"><i class="bi bi-lock"></i> Connexion Admin</a>
                    <a href="#" class="text-dark me-3 text-decoration-none"><i class="bi bi-person fs-4"></i></a>
                    <a href="#" class="text-dark text-decoration-none"><i class="bi bi-cart3 fs-4"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid px-5 my-4">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar rounded shadow-sm p-3 h-100">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#"><i class="bi bi-stars text-warning"></i> Nouveautés</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#"><i class="bi bi-tags"></i> Petits prix</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#"><i class="bi bi-clock-history"></i> Dernière chance</a></li>
                    <hr>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">Romans</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">BD / Mangas</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#">Jeunesse</a></li>
                </ul>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                
                <div class="promo-banner p-5 shadow-sm mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <span class="badge badge-promo mb-2 px-3 py-2">OFFRE EXCLUSIVE</span>
                            <h1 class="display-5 fw-bold text-success">Uniquement chez Biblioccaz !</h1>
                            <p class="lead">Réductions automatiques dans votre panier :<br>
                            <strong>-5%</strong> dès 30€ d'achats et <strong>-10%</strong> dès 50€ d'achats.</p>
                            <button class="btn btn-promo btn-lg px-4 shadow">J'en profite maintenant</button>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="display-3 fw-bold" style="color: #8fce00;">-10%</div>
                            <small class="text-muted text-uppercase fw-bold">Sur tout le catalogue</small>
                        </div>
                    </div>
                </div>

                <h3 class="mt-5 mb-4 fw-bold text-bibli-green border-start border-4 border-success ps-3">Nos meilleures ventes</h3>
                
                <?php
                $meilleuresVentes = [
                    ["titre" => "Le crime du paradis", "auteur" => "Guillaume Musso", "prix" => 14.90, "img" => "img/Le crime du paradis.jpg"],
                    ["titre" => "Harry Potter et la Coupe de Feu", "auteur" => "J.K. Rowling", "prix" => 32.00, "img" => "img/Harry Potter et la Coupe de Feu.jpg"],
                    ["titre" => "La chronique des Bridgerton", "auteur" => "Julia Quinn", "prix" => 9.50, "img" => "img/La chronique des Bridgerton.jpg"],
                    ["titre" => "Heated Rivalry", "auteur" => "Rachel Reid", "prix" => 11.00, "img" => "img/Heated Rivalry.jpg"],
                ];
                ?>

                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <?php foreach ($meilleuresVentes as $livre): ?>
                    <div class="col">
                        <div class="card h-100 card-book shadow-sm text-center p-2 bg-white">
                            <img src="<?php echo $livre['img']; ?>" class="card-img-top mx-auto mt-2" alt="Couverture" style="width: 100px; height: 150px; object-fit: contain;">
                            <div class="card-body">
                                <p class="card-text text-muted mb-1" style="font-size: 0.8rem;"><?php echo $livre['auteur']; ?></p>
                                <h6 class="card-title fw-bold" style="font-size: 0.9rem; color: #274e13;"><?php echo $livre['titre']; ?></h6>
                                <p class="card-text price-tag fs-5 mt-2"><?php echo number_format($livre['prix'], 2); ?> €</p>
                                <button class="btn btn-sm btn-outline-success w-100">Ajouter au panier</button>
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