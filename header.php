<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$theme_class = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark-mode' : '';
$connected = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'invite';

$solde_points = 0;
if ($connected && $userRole === 'client') {
    $stmt = $pdo->prepare("SELECT solde_points FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $resPoints = $stmt->fetch();
    $solde_points = $resPoints['solde_points'] ?? 0;
}

$nb_notif_modo = 0;
$nb_notif_livres = 0;
$total_notif_admin = 0;

if ($connected && $userRole === 'admin') {
    $stmtNotifModo = $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'");
    $nb_notif_modo = intval($stmtNotifModo->fetchColumn());

    $stmtNotifLivres = $pdo->query("SELECT COUNT(*) FROM livre WHERE is_valide = 0");
    $nb_notif_livres = intval($stmtNotifLivres->fetchColumn());

    $total_notif_admin = $nb_notif_modo + $nb_notif_livres;
}

$totalArticles = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $totalArticles += $item['quantite'];
    }
}
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const barre = document.getElementById('barre-recherche');
    const suggestions = document.getElementById('suggestions-recherche');

    if (barre && suggestions) {
        barre.addEventListener('input', function(e) {
            let saisie = e.target.value.trim();
            if (saisie.length < 2) {
                suggestions.classList.add('d-none');
                suggestions.innerHTML = '';
                return;
            }

            let rechercheMajuscule = saisie.toUpperCase();
                if (rechercheMajuscule === "SANANES") {
                suggestions.classList.remove('d-none');
                suggestions.innerHTML = `
                    <div class="list-group-item list-group-item-warning p-3" style="border: 2px dashed #ffc107;">
                        <h6 class="mb-1" style="font-weight: bold; color: #856404;"> Livre Secret Débloqué !</h6>
                        <p class="mb-1 small" style="font-weight: bold;">
                            "Comment mettre un 20/20 à BiblioOccaz" — par M. SANANES
                        </p>
                        <small class="text-muted d-block mt-1" style="font-style: italic;">
                            Ouvrage indisponible, déjà victime de son succès auprès du jury ! 
                        </small>
                    </div>
                `;
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
                            item.href = 'detail_livre.php?id=' + (livre.id_livre || livre.id);
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

        document.addEventListener('click', function(e) {
            if (!barre.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.classList.add('d-none');
            }
        });
    }
});
</script>
</head>
<body class="<?= $theme_class ?>">

<nav class="navbar navbar-expand-lg border-bottom bg-white py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a class="navbar-brand fw-bold text-success fs-3 mb-0 me-3" href="index.php" style="text-decoration:none;">BIBLIOccaz</a>
            <?php if ($connected && $userRole === 'client'): ?>
                <span class="badge bg-light text-success border border-success rounded-pill px-3 py-2 small fw-bold">
                    <i class="bi bi-coin me-1"></i> <?= htmlspecialchars($solde_points) ?> points
                </span>
            <?php endif; ?>
        </div>
        
        <div class="flex-grow-1 mx-4" style="max-width: 450px;">
            <div class="position-relative">
                <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #888;"></i>
                <input type="text" id="barre-recherche" name="q" autocomplete="off" class="form-control rounded-pill ps-5" placeholder="Rechercher un livre...">
                <div id="suggestions-recherche" class="list-group position-absolute w-100 mt-1 shadow d-none" style="z-index: 1000; max-height: 300px; overflow-y: auto;"></div>    
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if ($userRole === 'auteur'): ?>
                <a href="proposer_ouvrage.php" class="btn btn-success rounded-pill px-3 fw-bold btn-sm d-none d-sm-inline-block me-2">Proposer une œuvre</a>
            <?php elseif ($userRole === 'client' || !$connected): ?>
                <a href="vendre.php" class="btn btn-success rounded-pill px-3 fw-bold btn-sm d-none d-sm-inline-block me-2">Donner ou revendre</a>
            <?php endif; ?>

            <div class="dropdown d-inline-block dropdown-hover">
                <div class="p-2 text-dark">
                    <?php if ($connected): ?>
                        <a href="profil.php" class="text-decoration-none text-dark d-flex align-items-center">
                            <i class="bi bi-person-circle fs-4 me-1"></i>
                            <span class="fw-medium small d-none d-md-inline-block"><?= htmlspecialchars($_SESSION['pseudo']) ?></span>
                        </a>
                        <?php if ($total_notif_admin > 0): ?>
                            <span class="badge bg-success rounded-pill ms-1" style="font-size: 0.65rem;"><?= $total_notif_admin ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="text-decoration-none text-dark d-flex align-items-center">
                            <i class="bi bi-person-circle fs-4 me-1"></i>
                            <span class="fw-medium small d-none d-md-inline-block">Connexion</span>
                        </a>
                    <?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="border-radius: 12px; min-width: 220px;">
                    <?php if ($connected): ?>
                        <li class="px-3 py-2 border-bottom mb-1 text-center bg-light rounded-3">
                            <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($_SESSION['pseudo']) ?></strong>
                            <a href="profil.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mt-1" style="font-size: 0.75rem;">Mon profil</a>
                        </li>
                        <?php if ($userRole === 'admin'): ?>
                            <li><a class="dropdown-item small rounded-2 py-2" href="admin.php"><i class="bi bi-speedometer2 me-2 text-success"></i>Dashboard Global</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="moderation_catalogue.php"><i class="bi bi-shield-check me-2 text-success"></i>Modération Livres</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="inventaire_admin.php"><i class="bi bi-box-seam me-2 text-success"></i>Stocks</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="gestion_utilisateurs.php"><i class="bi bi-people me-2 text-success"></i>Membres</a></li>
                        <?php elseif ($userRole === 'gestionnaire'): ?>
                            <li><a class="dropdown-item small rounded-2 py-2" href="dashboard_gestionnaire.php"><i class="bi bi-speedometer me-2 text-success"></i>Dashboard</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="moderation_catalogue.php"><i class="bi bi-shield-check me-2 text-success"></i>Modérer</a></li>
                        <?php elseif ($userRole === 'auteur'): ?>
                            <li><a class="dropdown-item small rounded-2 py-2" href="dashboard_auteur.php"><i class="bi bi-layout-text-sidebar-reverse me-2 text-success"></i>Créateur</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item small rounded-2 py-2" href="commandes.php"><i class="bi bi-truck me-2 text-success"></i>Mes achats</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="wishlist.php"><i class="bi bi-heart me-2 text-success"></i>Ma liste</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-danger rounded-2 py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item small rounded-2 py-2" href="login.php">Se connecter</a></li>
                        <li><a class="dropdown-item small rounded-2 py-2" href="inscription.php">Créer un compte</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown d-inline-block dropdown-hover position-relative">
                <a href="panier.php" class="btn btn-link text-dark p-2 text-decoration-none d-flex align-items-center justify-content-center position-relative" style="width: 40px; height: 40px;">
                    <i class="bi bi-cart fs-4 text-success"></i>
                    <?php if ($totalArticles > 0): ?>
                        <span class="badge-notify"><?= $totalArticles ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3 shadow border-0 cart-dropdown-adjust">
                    <?php if (empty($_SESSION['panier'])): ?>
                        <li>
                            <div class="text-center text-muted py-2 small">Votre panier est vide</div>
                        </li>
                    <?php else: ?>
                        <h6 class="fw-bold text-dark mb-3 small">Mon Panier (<?= $totalArticles ?>)</h6>
                        <div class="mini-cart-scroll">
                            <?php foreach ($_SESSION['panier'] as $item): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <img src="img/<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded border" style="width: 40px; height: 55px; object-fit: cover; flex-shrink: 0;">
                                    <div class="flex-grow-1 ms-3 min-width-0">
                                        <h6 class="mb-0 small fw-bold text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($item['titre']) ?></h6>
                                        <div class="text-muted" style="font-size: 0.7rem;">État : <?= htmlspecialchars($item['etat']) ?></div>
                                        <small class="text-secondary" style="font-size: 0.75rem;">Qté : <?= $item['quantite'] ?></small>
                                    </div>
                                    <span class="fw-bold text-dark small ms-2 flex-shrink-0"><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <li class="pt-2 mt-1">
                            <a href="panier.php" class="btn btn-custom-green btn-sm w-100 text-white text-center d-block rounded-pill py-2 text-decoration-none">
                                Voir tout le panier
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>