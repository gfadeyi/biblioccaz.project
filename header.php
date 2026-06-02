<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$theme_class = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark-mode' : '';
$connected = isset($_SESSION['user_id']);
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIBLIOccaz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .mon-compte-dropdown { position: relative; display: inline-block; }
        .menu-deroulant-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 260px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            z-index: 1000;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .mon-compte-dropdown:hover .menu-deroulant-content { display: block; }
        .menu-header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 1px solid #eee; }
        .section-admin { background-color: #f4f7f4; border-top: 1px solid #e0eee0; border-bottom: 1px solid #e0eee0; }
        .lien-menu { color: #333; padding: 12px 20px; text-decoration: none !important; display: block; font-size: 0.9rem; }
        .lien-menu:hover { background-color: rgba(39, 78, 19, 0.05); color: #274e13; }
        .titre-admin { font-weight: bold; color: #274e13; text-transform: uppercase; font-size: 0.7rem; padding: 10px 20px 5px; margin: 0; }
        .section-deco { background-color: #f8f9fa; border-top: 1px solid #eee; }
        .deco-link { color: #274e13 !important; font-weight: bold; text-align: center; padding: 12px; display: block; text-decoration: none; transition: 0.3s; }
        .deco-link:hover { background-color: #e9ecef; }
        body.dark-mode .menu-deroulant-content { background-color: #2d2d2d; border-color: #444; }
        body.dark-mode .menu-header, body.dark-mode .section-deco { background-color: #333; border-color: #444; }
        body.dark-mode .lien-menu { color: #eee; }
        body.dark-mode .section-admin { background-color: #1e2a1e; border-color: #2a3a2a; }
    </style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const barre = document.getElementById('barre-recherche');
    const suggestions = document.getElementById('suggestions-recherche');
<<<<<<< ours
=======

>>>>>>> theirs
    if (barre && suggestions) {
        barre.addEventListener('input', function(e) {
            let saisie = e.target.value.trim();
            if (saisie.length < 2) {
                suggestions.classList.add('d-none');
                suggestions.innerHTML = '';
                return;
            }
            fetch('recherche.php?q=' + encodeURIComponent(saisie))
                .then(response => response.json())
                .then(livres => {
                    suggestions.innerHTML = '';
                    if (livres.length > 0) {
                        suggestions.classList.remove('d-none');
                        livres.forEach(livre => {
                            let item = document.createElement('a');
                            item.href = 'detail_livre.php?id=' + livre.id;
                            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                            item.innerHTML = `<span>${livre.titre}</span><span class="badge bg-success rounded-pill">${livre.auteur}</span>`;
                            suggestions.appendChild(item);
                        });
                    } else {
                        suggestions.classList.remove('d-none');
                        suggestions.innerHTML = '<div class="list-group-item text-muted small">Aucun livre trouvé...</div>';
                    }
                })
                .catch(error => console.error('Erreur:', error));
        });
<<<<<<< ours
=======

>>>>>>> theirs
        document.addEventListener('click', function(e) {
            if (!barre.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.classList.add('d-none');
            }
        });
    }
});
<<<<<<< ours
</script>
=======
</script> 
>>>>>>> theirs
</head>
<body class="<?= $theme_class ?>">

<nav class="navbar navbar-expand-lg border-bottom bg-white py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand fw-bold text-success fs-3" href="index.php" style="text-decoration:none;">BIBLIOccaz</a>
        
<div class="flex-grow-1 mx-4" style="max-width: 450px;">
    <div class="position-relative">
        <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #888;"></i>
        <input type="text" id="barre-recherche" name="q" autocomplete="off" class="form-control rounded-pill ps-5" placeholder="Rechercher un livre...">
        <div id="suggestions-recherche" class="list-group position-absolute w-100 mt-1 shadow d-none" style="z-index: 1000; max-height: 300px; overflow-y: auto;"></div>    
    </div>
</div>
        <div class="d-flex align-items-center">
            <?php if ($connected): ?>
                <div class="mon-compte-dropdown">
                    <div class="nav-link d-flex align-items-center py-2" style="cursor: pointer;">
                        <i class="bi bi-person-circle fs-4 me-2 text-dark"></i>
                        <span class="text-dark fw-medium"><?= htmlspecialchars($_SESSION['pseudo']) ?></span>
                    </div>
                    
                    <div class="menu-deroulant-content">
                        <div class="menu-header">
                            <i class="bi bi-person-fill text-secondary" style="font-size: 2.5rem;"></i>
                            <p class="mb-2 fw-bold text-dark"><?= htmlspecialchars($_SESSION['pseudo']) ?></p>
                            <a href="profil.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Mon profil</a>
                        </div>

                        <?php if ($isAdmin): ?>
                            <div class="section-admin">
                                <h6 class="titre-admin">Administration</h6>
                                <a class="lien-menu" href="admin.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard Global</a>
                                <a class="lien-menu" href="moderation_catalogue.php"><i class="bi bi-shield-check me-2"></i> Modération Livres</a>
                                <a class="lien-menu" href="inventaire_admin.php"><i class="bi bi-box-seam me-2"></i> Inventaire & Stocks</a>
                                <a class="lien-menu" href="gestion_utilisateurs.php"><i class="bi bi-people me-2"></i> Gestion Membres</a>
                                <a class="lien-menu" href="diffusion_admin.php"><i class="bi bi-megaphone me-2"></i> Diffusion Newsletter</a>
                                <a class="lien-menu" href="admin_logs.php"><i class="bi bi-journal-text me-2"></i> Journal des Logs</a>
                            </div>
                        <?php else: ?>
                            <a class="lien-menu" href="commandes.php"><i class="bi bi-truck me-2"></i> Mes achats</a>
                            <a class="lien-menu" href="wishlist.php"><i class="bi bi-heart me-2"></i> Ma liste d'envies</a>
                        <?php endif; ?>

                        <div class="section-deco">
                            <a href="logout.php" class="deco-link">Me déconnecter <i class="bi bi-box-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link me-3 text-dark">Connexion</a>
            <?php endif; ?>
            <a href="vendre.php" class="btn btn-success rounded-pill ms-3 px-4 fw-bold">Donner ou revendre</a>
        </div>
    </div>
</nav>
