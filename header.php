<?php
// Le menu a besoin de la base : on la charge si la page ne l'a pas déjà fait
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}
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
$nb_notif_avis = 0;
$total_notif_admin = 0;

// Ce qui attend l'équipe (affiché en pastille sur l'icône du compte)
if ($connected && in_array($userRole, ['admin', 'gestionnaire', 'moderateur'], true)) {
    try {
        $nb_notif_livres = (int) $pdo->query("SELECT COUNT(*) FROM livre WHERE is_valide = 0")->fetchColumn();
        $nb_notif_avis = (int) $pdo->query("SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0")->fetchColumn();
        if ($userRole === 'admin') {
            $nb_notif_modo = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'")->fetchColumn();
        }
    } catch (PDOException $e) {
    }
    $total_notif_admin = $nb_notif_modo + $nb_notif_livres + $nb_notif_avis;
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

<?php
// Données du menu des catégories (silencieux si la table n'existe pas encore)
$menuCategories = [];
try {
    $menuCategories = $pdo->query("SELECT nom, slug FROM categorie ORDER BY ordre, nom")->fetchAll();
} catch (PDOException $e) {
}
$pagesCategories = ['roman' => 'roman.php', 'jeunesse' => 'jeunesse.php', 'bd-mangas' => 'bd_mangas.php'];
$pageActuelle = basename($_SERVER['PHP_SELF']);
$menuCatActive = $_GET['categorie'] ?? ($preset['categorie'] ?? '');
$lienCategorie = fn($slug) => $pagesCategories[$slug] ?? 'catalogue.php?categorie=' . urlencode($slug);
?>
<header class="entete bg-white border-bottom sticky-top">
    <!-- Ligne 1 : logo, recherche, actions -->
    <div class="container-xxl d-flex align-items-center gap-3 py-2">
        <a class="logo-bibli text-decoration-none" href="index.php">
            <span class="logo-nom">BIBLIOccaz</span>
            <span class="logo-slogan">Donnez une seconde vie à vos lectures</span>
        </a>

        <form action="catalogue.php" method="GET" class="recherche-entete flex-grow-1 position-relative" role="search">
            <input type="text" id="barre-recherche" name="q" autocomplete="off" class="form-control rounded-pill pe-5"
                   placeholder="Rechercher par titre, auteur, ISBN..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit" class="btn position-absolute top-50 end-0 translate-middle-y me-2 border-0" aria-label="Rechercher">
                <i class="bi bi-search"></i>
            </button>
            <div id="suggestions-recherche" class="list-group position-absolute w-100 mt-1 shadow d-none" style="z-index: 1050; max-height: 300px; overflow-y: auto;"></div>
        </form>

        <?php if ($userRole === 'auteur'): ?>
            <a href="proposer_ouvrage.php" class="btn btn-outline-bibli rounded-pill fw-bold d-none d-lg-inline-flex align-items-center gap-2">
                <i class="bi bi-journal-plus"></i> Proposer une œuvre
            </a>
        <?php elseif ($userRole === 'client' || !$connected): ?>
            <a href="vendre.php" class="btn btn-outline-bibli rounded-pill fw-bold d-none d-lg-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat"></i> Donner ou revendre mes livres
            </a>
        <?php endif; ?>

        <nav class="d-flex align-items-start gap-1 icones-entete">
            <?php if ($connected && $userRole === 'client'): ?>
                <a href="profil.php" class="icone-entete" title="Mes points fidélité">
                    <i class="bi bi-coin"></i><span><?= htmlspecialchars($solde_points) ?> pts</span>
                </a>
                <a href="wishlist.php" class="icone-entete d-none d-sm-flex">
                    <i class="bi bi-heart"></i><span>Favoris</span>
                </a>
            <?php endif; ?>

            <div class="dropdown dropdown-hover">
                <a href="<?= $connected ? 'profil.php' : 'login.php' ?>" class="icone-entete position-relative">
                    <i class="bi bi-person"></i>
                    <span class="text-truncate" style="max-width: 90px;"><?= $connected ? htmlspecialchars($_SESSION['pseudo']) : 'Connexion' ?></span>
                    <?php if ($total_notif_admin > 0): ?>
                        <span class="pastille"><?= $total_notif_admin ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="border-radius: 12px; min-width: 220px;">
                    <?php if ($connected): ?>
                        <li class="px-3 py-2 border-bottom mb-1 text-center bg-light rounded-3">
                            <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($_SESSION['pseudo']) ?></strong>
                            <a href="profil.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mt-1" style="font-size: 0.75rem;">Mon profil</a>
                        </li>
                        <?php if (in_array($userRole, ['admin', 'gestionnaire', 'moderateur'], true)): ?>
                            <li><a class="dropdown-item small rounded-2 py-2 fw-bold" href="admin/index.php"><i class="bi bi-speedometer2 me-2 text-success"></i>Back-office<?php if ($total_notif_admin > 0): ?> <span class="badge bg-danger rounded-pill"><?= $total_notif_admin ?></span><?php endif; ?></a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="admin/moderation_livres.php"><i class="bi bi-shield-check me-2 text-success"></i>Fiches à valider<?php if ($nb_notif_livres > 0): ?> <span class="badge bg-danger rounded-pill"><?= $nb_notif_livres ?></span><?php endif; ?></a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="admin/moderation_avis.php"><i class="bi bi-chat-square-text me-2 text-success"></i>Avis signalés<?php if ($nb_notif_avis > 0): ?> <span class="badge bg-danger rounded-pill"><?= $nb_notif_avis ?></span><?php endif; ?></a></li>
                        <?php elseif ($userRole === 'auteur'): ?>
                            <li><a class="dropdown-item small rounded-2 py-2" href="dashboard_auteur.php"><i class="bi bi-layout-text-sidebar-reverse me-2 text-success"></i>Créateur</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item small rounded-2 py-2" href="commandes.php"><i class="bi bi-truck me-2 text-success"></i>Mes achats</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="wishlist.php"><i class="bi bi-heart me-2 text-success"></i>Ma liste</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="avis.php"><i class="bi bi-star me-2 text-success"></i>Mes avis</a></li>
                            <li><a class="dropdown-item small rounded-2 py-2" href="mes_dons.php"><i class="bi bi-gift me-2 text-success"></i>Mes dons</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-danger rounded-2 py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item small rounded-2 py-2" href="login.php">Se connecter</a></li>
                        <li><a class="dropdown-item small rounded-2 py-2" href="inscription.php">Créer un compte</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown dropdown-hover position-relative">
                <a href="panier.php" class="icone-entete position-relative">
                    <i class="bi bi-bag"></i><span>Panier</span>
                    <?php if ($totalArticles > 0): ?>
                        <span class="pastille"><?= $totalArticles ?></span>
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
        </nav>
    </div>

    <!-- Ligne 2 : catégories et raccourcis -->
    <div class="border-top">
        <div class="container-xxl d-flex align-items-center gap-2 py-2 overflow-auto ligne-raccourcis">
            <button class="btn btn-bibli rounded-pill fw-bold d-inline-flex align-items-center gap-2 text-nowrap" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#menuCategories" aria-controls="menuCategories">
                <i class="bi bi-list fs-5"></i> Toutes les catégories
            </button>
            <a href="nouveauté.php" class="btn btn-outline-bibli rounded-pill fw-bold text-nowrap d-inline-flex align-items-center gap-2 <?= $pageActuelle === 'nouveauté.php' ? 'active' : '' ?>">
                <i class="bi bi-stars"></i> Nouveau chez nous <i class="bi bi-chevron-right small"></i>
            </a>
            <a href="meilleures_ventes.php" class="raccourci <?= $pageActuelle === 'meilleures_ventes.php' ? 'actif' : '' ?>"><i class="bi bi-award"></i> Meilleures ventes</a>
            <a href="dernière_chance.php" class="raccourci <?= $pageActuelle === 'dernière_chance.php' ? 'actif' : '' ?>"><i class="bi bi-hourglass-split"></i> Dernière chance</a>
            <a href="petits_prix.php" class="raccourci <?= $pageActuelle === 'petits_prix.php' ? 'actif' : '' ?>"><i class="bi bi-tag"></i> Moins de 10 €</a>
            <a href="dons.php" class="raccourci <?= in_array($pageActuelle, ['dons.php', 'don_detail.php'], true) ? 'actif' : '' ?>"><i class="bi bi-gift"></i> Livres à donner</a>
            <a href="catalogue.php" class="raccourci <?= $pageActuelle === 'catalogue.php' && !$menuCatActive ? 'actif' : '' ?>"><i class="bi bi-grid"></i> Tout le catalogue</a>
        </div>
    </div>
</header>

<!-- Menu latéral des catégories -->
<div class="offcanvas offcanvas-start menu-lateral" tabindex="-1" id="menuCategories" aria-labelledby="menuCategoriesTitre">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="menuCategoriesTitre" style="color:#274e13;">Catégories</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="list-unstyled mb-0">
            <?php foreach ($menuCategories as $mc): ?>
                <li>
                    <a href="<?= htmlspecialchars($lienCategorie($mc['slug'])) ?>" class="lien-lateral <?= $menuCatActive === $mc['slug'] ? 'actif' : '' ?>">
                        <?= htmlspecialchars($mc['nom']) ?> <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endforeach; ?>
            <li><a href="catalogue.php" class="lien-lateral">Tout le catalogue <i class="bi bi-chevron-right"></i></a></li>
        </ul>
        <hr class="my-2">
        <ul class="list-unstyled mb-0">
            <?php if ($userRole === 'auteur'): ?>
                <li><a href="proposer_ouvrage.php" class="lien-lateral-secondaire"><i class="bi bi-journal-plus"></i> Proposer une œuvre</a></li>
            <?php else: ?>
                <li><a href="vendre.php" class="lien-lateral-secondaire"><i class="bi bi-arrow-repeat"></i> Donner ou revendre mes livres</a></li>
            <?php endif; ?>
            <li><a href="dons.php" class="lien-lateral-secondaire"><i class="bi bi-gift"></i> Livres à donner</a></li>
            <li><a href="meilleures_ventes.php" class="lien-lateral-secondaire"><i class="bi bi-award"></i> Meilleures ventes</a></li>
            <li><a href="nouveauté.php" class="lien-lateral-secondaire"><i class="bi bi-stars"></i> Nouveautés</a></li>
            <li><a href="petits_prix.php" class="lien-lateral-secondaire"><i class="bi bi-tag"></i> Petits prix</a></li>
            <?php if ($connected): ?>
                <li><a href="profil.php" class="lien-lateral-secondaire"><i class="bi bi-person"></i> Mon compte</a></li>
            <?php else: ?>
                <li><a href="login.php" class="lien-lateral-secondaire"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<style>
    :root { --bibli-vert: #274e13; --bibli-vert-clair: #f1f8ec; }
    .logo-bibli { display: flex; flex-direction: column; line-height: 1; flex-shrink: 0; }
    .logo-nom { font-weight: 800; font-size: 1.7rem; color: var(--bibli-vert); letter-spacing: -.02em; }
    .logo-slogan { font-size: .62rem; color: #666; margin-top: 2px; }
    .recherche-entete .form-control { height: 44px; border: 1px solid #bbb; padding-left: 1.2rem; }
    .recherche-entete .form-control:focus { border-color: var(--bibli-vert); box-shadow: 0 0 0 .2rem rgba(39,78,19,.15); }
    .btn-bibli { background: var(--bibli-vert); color: #fff; border: none; }
    .btn-bibli:hover { background: #1a330d; color: #fff; }
    .btn-outline-bibli { border: 1.5px solid var(--bibli-vert); color: var(--bibli-vert); background: #fff; }
    .btn-outline-bibli:hover, .btn-outline-bibli.active { background: var(--bibli-vert-clair); color: var(--bibli-vert); border-color: var(--bibli-vert); }
    .icone-entete { display: flex; flex-direction: column; align-items: center; min-width: 58px; padding: 2px 6px;
                    color: #222; text-decoration: none; font-size: .75rem; position: relative; }
    .icone-entete i { font-size: 1.35rem; color: var(--bibli-vert); line-height: 1.2; }
    .icone-entete:hover { color: var(--bibli-vert); }
    .pastille { position: absolute; top: -2px; right: 8px; background: #e8590c; color: #fff; border-radius: 50px;
                font-size: .65rem; font-weight: 700; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }
    .ligne-raccourcis::-webkit-scrollbar { display: none; }
    .raccourci { color: #222; text-decoration: none; font-weight: 600; white-space: nowrap; padding: .35rem .7rem; border-radius: 50px; }
    .raccourci i { color: var(--bibli-vert); margin-right: .25rem; }
    .raccourci:hover, .raccourci.actif { background: var(--bibli-vert-clair); color: var(--bibli-vert); }
    .menu-lateral { width: 360px; }
    .lien-lateral { display: flex; justify-content: space-between; align-items: center; padding: .8rem 1.25rem; color: #222; text-decoration: none; }
    .lien-lateral:hover, .lien-lateral.actif { background: var(--bibli-vert-clair); color: var(--bibli-vert); }
    .lien-lateral i { color: #888; }
    .lien-lateral-secondaire { display: flex; gap: .7rem; align-items: center; padding: .6rem 1.25rem; color: #333; text-decoration: none; }
    .lien-lateral-secondaire i { font-size: 1.15rem; color: var(--bibli-vert); }
    .lien-lateral-secondaire:hover { background: var(--bibli-vert-clair); }
    @media (max-width: 767px) { .logo-slogan { display: none; } .logo-nom { font-size: 1.3rem; } .icone-entete span { display: none; } .icone-entete { min-width: 40px; } }
</style>