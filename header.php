<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$connected = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'invite';

$solde_points = 0;
if ($connected && $userRole === 'client') {
    $stmt = $pdo->prepare("SELECT solde_points FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $resPoints = $stmt->fetch();
    $solde_points = $resPoints['solde_points'] ?? 0;
}

require_once __DIR__ . '/compte_lib.php';
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/icones_lib.php';
require_once __DIR__ . '/categories_lib.php';
require_once __DIR__ . '/messagerie_lib.php';
$photoMembre = $connected ? photoProfilDe($pdo, (int) $_SESSION['user_id']) : null;
$idsFavoris = $connected ? idsFavoris($pdo, (int) $_SESSION['user_id']) : [];
$nb_favoris = count($idsFavoris);
if ($connected) {
    require_once __DIR__ . '/emprunts_lib.php';
    verifierEmpruntsSiBesoin($pdo);
}
$resumeCompte = ($connected && $userRole === 'client') ? resumeCompte($pdo, (int) $_SESSION['user_id']) : null;
$nbMessagesNonLus = ($connected && in_array($userRole, ['client', 'auteur'], true)) ? nombreMessagesNonLus($pdo, (int) $_SESSION['user_id']) : 0;
$libellesRoles = ['admin' => 'Administrateur', 'gestionnaire' => 'Gestionnaire', 'moderateur' => 'Modérateur', 'auteur' => 'Auteur'];

$nb_notif_modo = 0;
$nb_notif_livres = 0;
$nb_notif_avis = 0;
$total_notif_admin = 0;

if ($connected && in_array($userRole, ['admin', 'gestionnaire', 'moderateur'], true)) {
    try {
        $nb_notif_livres = (int) $pdo->query("SELECT COUNT(*) FROM livre l WHERE l.is_valide = 0 AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.id_proprietaire IS NOT NULL)")->fetchColumn();
        $nb_notif_avis = (int) $pdo->query("SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0")->fetchColumn();
        if ($userRole === 'admin') {
            $nb_notif_modo = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'")->fetchColumn();
        }
    } catch (PDOException $e) {
    }
    $total_notif_admin = $nb_notif_modo + $nb_notif_livres + $nb_notif_avis;
}

$liensMenuCompte = [];
if ($resumeCompte) {
    $liensMenuCompte = [
        ['points_badges.php', 'medaille', 'Mes points et badges', 0],
        ['commandes.php', 'colis', 'Mes achats', $resumeCompte['commandes']],
        ['wishlist.php', 'marque-page', 'Mes favoris', $nb_favoris],
        ['alertes.php', 'clochette', 'Mes alertes', $resumeCompte['alertes']],
        ['adresses.php', 'enveloppe', 'Mes adresses', 0],
        ['avis.php', 'plume', 'Mes avis', $resumeCompte['avis']],
        ['messages.php', 'bulle', 'Mes messages', $nbMessagesNonLus],
        ['mes_dons.php', 'carton', 'Mes dons', $resumeCompte['dons']],
        ['mes_annonces.php', 'etiquette', 'Mes annonces', $resumeCompte['annonces'] ?? 0],
        ['mes_transactions.php', 'cabas', 'Mes ventes et achats', $resumeCompte['transactions'] ?? 0],
        ['mes_emprunts.php', 'livre-ouvert', 'Mes emprunts', $resumeCompte['emprunts'] ?? 0],
        ['mes_evenements.php', 'ticket', 'Mes événements', $resumeCompte['evenements']],
    ];
} elseif ($connected && $userRole === 'auteur') {
    $liensMenuCompte = [
        ['dashboard_auteur.php', 'plume', 'Espace créateur', 0],
        ['proposer_evenement.php', 'ticket', 'Proposer un événement', 0],
        ['proposer_ouvrage.php', 'livre-ouvert', 'Proposer un livre', 0],
        ['mes_annonces.php', 'etiquette', 'Mes annonces', 0],
        ['mes_transactions.php', 'cabas', 'Mes ventes et achats', 0],
        ['mes_emprunts.php', 'pile', 'Mes emprunts', 0],
        ['messages.php', 'bulle', 'Mes messages', $nbMessagesNonLus],
    ];
} elseif ($connected) {
    $liensMenuCompte = [
        ['admin/index.php', 'livre-ouvert', 'Back-office', $total_notif_admin],
        ['admin/moderation_livres.php', 'tampon', 'Fiches à valider', $nb_notif_livres],
        ['admin/moderation_avis.php', 'bulle', 'Avis signalés', $nb_notif_avis],
    ];
}

$livreAjoute = $_SESSION['panier_ajout'] ?? null;
unset($_SESSION['panier_ajout']);

$totalArticles = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $totalArticles += $item['quantite'];
    }
}

$pageActuelle = basename($_SERVER['PHP_SELF']);
$menuCatActive = $_GET['categorie'] ?? ($preset['categorie'] ?? '');
$universMenu = arbreCategories($pdo);
$universOuvert = 'bons-plans';
foreach ($universMenu as $universLigne) {
    if ($menuCatActive !== '' && ($universLigne['slug'] === $menuCatActive || in_array($menuCatActive, array_column($universLigne['categories'], 'slug'), true))) {
        $universOuvert = $universLigne['slug'];
    }
}
$bonsPlansMenu = [
    ['nouveauté.php', 'bi-stars', 'Nouveautés'],
    ['meilleures_ventes.php', 'bi-award', 'Meilleures ventes'],
    ['petits_prix.php', 'bi-tag', 'Moins de 10 €'],
    ['dernière_chance.php', 'bi-hourglass-split', 'Dernière chance'],
    ['dons.php', 'bi-gift', 'Livres à donner'],
];
$classeRaccourci = fn(array $pages) => in_array($pageActuelle, $pages, true) ? 'puce actif' : 'puce';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($adresseBase)): ?><base href="<?= htmlspecialchars($adresseBase) ?>"><?php endif; ?>
    <title><?= htmlspecialchars(isset($titreDocument) ? $titreDocument . ' - BIBLIOccaz' : 'BIBLIOccaz') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
    <meta name="csrf" content="<?= jetonCsrf() ?>">
    <script src="javascript/recherche.js?v=<?= filemtime(__DIR__ . '/javascript/recherche.js') ?>" defer></script>
    <script src="javascript/raccourcis.js?v=<?= filemtime(__DIR__ . '/javascript/raccourcis.js') ?>" defer></script>
    <script src="javascript/menu_univers.js?v=<?= filemtime(__DIR__ . '/javascript/menu_univers.js') ?>" defer></script>
    <script src="javascript/menu_compte.js?v=<?= filemtime(__DIR__ . '/javascript/menu_compte.js') ?>" defer></script>
</head>
<body>

<header class="entete">
    <div class="container-xxl ligne-entete d-flex align-items-center gap-3 pt-2">
        <a class="logo-bibli" href="index.php">
            <span class="logo-nom">BIBLIOccaz</span>
            <span class="logo-slogan">Donnez une seconde vie à vos lectures</span>
        </a>

        <form action="catalogue.php" method="GET" class="recherche-entete flex-grow-1 position-relative" role="search">
            <label for="barre-recherche" class="visually-hidden">Rechercher un livre</label>
            <input type="search" id="barre-recherche" name="q" autocomplete="off" class="champ-recherche"
                   placeholder="Titre, auteur, ISBN…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit" class="bouton-loupe" aria-label="Rechercher"><?= iconeDessin('loupe') ?></button>
            <div id="suggestions-recherche" class="suggestions-recherche list-group position-absolute w-100 mt-1 shadow d-none"></div>
        </form>

        <?php if ($userRole === 'auteur'): ?>
            <a href="proposer_ouvrage.php" class="btn btn-outline-bibli rounded-pill fw-bold d-none d-lg-inline-flex align-items-center gap-2">
                <i class="bi bi-journal-plus"></i> Proposer une œuvre
            </a>
        <?php elseif ($userRole === 'client' || !$connected): ?>
            <a href="vendre.php" class="btn btn-outline-bibli rounded-pill fw-bold d-none d-xl-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat"></i> Donner ou revendre
            </a>
        <?php endif; ?>

        <nav class="d-flex align-items-start gap-1" aria-label="Mon compte">
            <?php if ($connected && $userRole === 'client'): ?>
                <a href="points_badges.php" class="icone-entete" title="Mes points et badges" aria-label="Mes points et badges, <?= (int) $solde_points ?> points">
                    <?= iconeDessin('medaille') ?><span><?= htmlspecialchars($solde_points) ?> pts</span>
                </a>
                <a href="wishlist.php" class="icone-entete d-none d-sm-flex">
                    <?= iconeDessin('marque-page') ?><span>Favoris</span>
                    <?php if ($nb_favoris > 0): ?><span class="pastille" id="compteur-favoris"><?= $nb_favoris ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if ($connected && in_array($userRole, ['client', 'auteur'], true)): ?>
                <a href="messages.php" class="icone-entete d-none d-sm-flex"<?= $nbMessagesNonLus ? ' aria-label="Messages, ' . $nbMessagesNonLus . ' non lu' . ($nbMessagesNonLus > 1 ? 's' : '') . '"' : '' ?>>
                    <?= iconeDessin('bulle') ?><span>Messages</span>
                    <?php if ($nbMessagesNonLus > 0): ?><span class="pastille"><?= $nbMessagesNonLus ?></span><?php endif; ?>
                </a>
            <?php endif; ?>

            <div class="dropdown dropdown-hover zone-menu-compte" data-menu-compte>
                <a href="<?= $connected ? 'profil.php' : 'login.php' ?>" class="icone-entete" aria-label="<?= $connected ? 'Mon compte' : 'Connexion' ?>" aria-haspopup="true" aria-expanded="false" aria-controls="menu-compte">
                    <?= $connected && cheminPhotoMembre($photoMembre) ? avatarMembre($_SESSION['pseudo'], $photoMembre, 'entete') : iconeDessin('lecteur') ?>
                    <span class="text-truncate" style="max-width: 90px;"><?= $connected ? htmlspecialchars(pseudoAffiche($_SESSION['pseudo'])) : 'Connexion' ?></span>
                    <?php if ($total_notif_admin > 0): ?>
                        <span class="pastille"><?= $total_notif_admin ?></span>
                    <?php endif; ?>
                </a>
                <div class="voile-menu-compte" hidden></div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 menu-entete" id="menu-compte">
                    <li class="menu-entete__fermer"><button type="button" class="bouton-fermer-menu" aria-label="Fermer le menu du compte"><i class="bi bi-x-lg" aria-hidden="true"></i></button></li>
                    <?php if ($connected): ?>
                        <li class="menu-entete__profil">
                            <?= avatarMembre($_SESSION['pseudo'], $photoMembre) ?>
                            <span class="menu-entete__identite">
                                <strong><?= htmlspecialchars(pseudoAffiche($_SESSION['pseudo'])) ?></strong>
                                <span class="niveau-lecteur"><?= $resumeCompte ? htmlspecialchars($resumeCompte['niveau']['nom']) : ($libellesRoles[$userRole] ?? 'Membre') ?></span>
                            </span>
                        </li>
                        <?php if ($resumeCompte): ?>
                            <li class="menu-entete__points">
                                <a href="points_badges.php" class="menu-entete__solde"><?= iconeDessin('medaille') ?><strong><?= (int) $solde_points ?></strong> points à utiliser</a>
                                <span class="jauge-niveau" style="--progression: <?= round($resumeCompte['niveau']['progression'], 3) ?>;"><span></span></span>
                                <small>
                                    <?php if ($resumeCompte['niveau']['prochain']): ?>
                                        Encore <?= $resumeCompte['niveau']['reste'] ?> pts pour devenir « <?= htmlspecialchars($resumeCompte['niveau']['prochain']['nom']) ?> »
                                    <?php else: ?>
                                        Niveau maximum atteint, bravo !
                                    <?php endif; ?>
                                </small>
                            </li>
                        <?php endif; ?>
                        <li><a class="lien-menu-compte" href="profil.php"><?= iconeDessin('lecteur') ?><span>Mon profil</span></a></li>
                        <?php foreach ($liensMenuCompte as [$lienMenu, $iconeMenu, $libelleMenu, $compteurMenu]): ?>
                            <li>
                                <a class="lien-menu-compte" href="<?= $lienMenu ?>">
                                    <?= iconeDessin($iconeMenu) ?><span><?= $libelleMenu ?></span>
                                    <?php if ($compteurMenu > 0): ?><span class="compteur-menu<?= $resumeCompte ? '' : ' urgent' ?>"><?= $compteurMenu ?></span><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="logout.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="lien-menu-compte deconnexion border-0 bg-transparent w-100 text-start"><?= iconeDessin('porte') ?><span>Déconnexion</span></button></form></li>
                    <?php else: ?>
                        <li class="menu-entete__accueil">Bienvenue ! Retrouvez vos favoris, vos points et vos commandes.</li>
                        <li><a class="lien-menu-compte" href="login.php"><?= iconeDessin('porte') ?><span>Se connecter</span></a></li>
                        <li><a class="lien-menu-compte" href="inscription.php"><?= iconeDessin('lecteur') ?><span>Créer un compte</span></a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown dropdown-hover position-relative">
                <a href="panier.php" class="icone-entete" aria-label="Mon panier">
                    <?= iconeDessin('cabas', $livreAjoute ? 'secousse' : '') ?><span>Panier</span>
                    <?php if ($totalArticles > 0): ?>
                        <span class="pastille<?= $livreAjoute ? ' rebond' : '' ?>"><?= $totalArticles ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3 shadow border-0 cart-dropdown-adjust">
                    <?php if (empty($_SESSION['panier'])): ?>
                        <li>
                            <div class="text-center text-muted py-2 small">Votre panier est vide</div>
                        </li>
                    <?php else: ?>
                        <li><h6 class="fw-bold mb-3 small">Mon panier (<?= $totalArticles ?>)</h6></li>
                        <li class="mini-cart-scroll">
                            <?php foreach ($_SESSION['panier'] as $item): ?>
                                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                    <img src="img/<?= htmlspecialchars($item['image']) ?>" alt="" class="img-fluid rounded border" style="width: 40px; height: 55px; object-fit: cover; flex-shrink: 0;">
                                    <div class="flex-grow-1 ms-3" style="min-width: 0;">
                                        <h6 class="mb-0 small fw-bold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($item['titre']) ?></h6>
                                        <div class="text-muted" style="font-size: 0.7rem;">État : <?= htmlspecialchars($item['etat']) ?></div>
                                        <small class="text-secondary" style="font-size: 0.75rem;">Qté : <?= (int) $item['quantite'] ?></small>
                                    </div>
                                    <span class="fw-bold small ms-2 flex-shrink-0"><?= formaterPrix($item['prix'] * $item['quantite']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </li>
                        <li class="pt-2 mt-1">
                            <a href="panier.php" class="btn btn-bibli btn-sm w-100 rounded-pill py-2">Voir tout le panier</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>

    <div class="container-xxl barre-raccourcis">
        <button class="bouton-categories" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuCategories" aria-controls="menuCategories">
            <?= iconeDessin('pile') ?> <span>Toutes les catégories</span>
        </button>
        <nav class="defilement-raccourcis" aria-label="Raccourcis">
        <a href="nouveauté.php" class="<?= $classeRaccourci(['nouveauté.php']) ?>"><i class="bi bi-stars"></i> Nouveautés</a>
        <a href="meilleures_ventes.php" class="<?= $classeRaccourci(['meilleures_ventes.php']) ?>"><i class="bi bi-award"></i> Meilleures ventes</a>
        <a href="petits_prix.php" class="<?= $classeRaccourci(['petits_prix.php']) ?>"><i class="bi bi-tag"></i> Moins de 10 €</a>
        <a href="dernière_chance.php" class="<?= $classeRaccourci(['dernière_chance.php']) ?>"><i class="bi bi-hourglass-split"></i> Dernière chance</a>
        <a href="dons.php" class="<?= $classeRaccourci(['dons.php', 'don_detail.php']) ?>"><i class="bi bi-gift"></i> Livres à donner</a>
        <a href="evenements.php" class="<?= $classeRaccourci(['evenements.php', 'evenement.php', 'mes_evenements.php']) ?>"><i class="bi bi-calendar-event"></i> Événements</a>
        <a href="catalogue.php" class="<?= $pageActuelle === 'catalogue.php' && !$menuCatActive ? 'puce actif' : 'puce' ?>"><i class="bi bi-grid"></i> Tout le catalogue</a>
        </nav>
    </div>
</header>

<?php if ($livreAjoute): ?>
    <div class="toast-panier" role="status">
        <i class="bi bi-check-circle-fill"></i>
        <span>« <?= htmlspecialchars(mb_strimwidth($livreAjoute, 0, 40, '…')) ?> » est dans votre panier</span>
        <a href="panier.php">Voir le panier</a>
    </div>
<?php endif; ?>

<div class="offcanvas offcanvas-start menu-lateral" tabindex="-1" id="menuCategories" aria-labelledby="menuCategoriesTitre">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="menuCategoriesTitre">Nos univers</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body p-0 menu-univers">
        <ul class="list-unstyled mb-0 liste-univers">
            <li class="univers-menu univers-bons-plans <?= $universOuvert === 'bons-plans' ? 'ouvert' : '' ?>">
                <button type="button" class="bouton-univers" aria-expanded="<?= $universOuvert === 'bons-plans' ? 'true' : 'false' ?>" aria-controls="panneau-bons-plans">
                    <span class="icone-univers"><?= iconeDessin('etiquette') ?></span>
                    <span class="nom-univers">Bons plans</span>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
                <div class="panneau-univers" id="panneau-bons-plans">
                    <p class="titre-panneau">Bons plans</p>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($bonsPlansMenu as [$lienBonPlan, $iconeBonPlan, $libelleBonPlan]): ?>
                            <li><a href="<?= $lienBonPlan ?>" class="lien-panneau <?= $pageActuelle === $lienBonPlan ? 'actif' : '' ?>"><i class="bi <?= $iconeBonPlan ?>" aria-hidden="true"></i> <?= $libelleBonPlan ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
            <?php foreach ($universMenu as $universLigne): ?>
                <?php $estOuvert = $universOuvert === $universLigne['slug']; ?>
                <li class="univers-menu univers-<?= htmlspecialchars($universLigne['couleur'] ?: 'litterature') ?> <?= $estOuvert ? 'ouvert' : '' ?>">
                    <button type="button" class="bouton-univers" aria-expanded="<?= $estOuvert ? 'true' : 'false' ?>" aria-controls="panneau-<?= htmlspecialchars($universLigne['slug']) ?>">
                        <span class="icone-univers"><?= iconeDessin($universLigne['icone'] ?: 'livre-ouvert') ?></span>
                        <span class="nom-univers"><?= htmlspecialchars($universLigne['nom']) ?></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                    <div class="panneau-univers" id="panneau-<?= htmlspecialchars($universLigne['slug']) ?>">
                        <p class="titre-panneau"><?= htmlspecialchars($universLigne['nom']) ?></p>
                        <ul class="list-unstyled mb-0">
                            <li><a href="<?= htmlspecialchars(lienCategorie($universLigne['slug'])) ?>" class="lien-panneau lien-tout-univers <?= $menuCatActive === $universLigne['slug'] ? 'actif' : '' ?>">Tout l'univers <i class="bi bi-arrow-right" aria-hidden="true"></i></a></li>
                            <?php foreach ($universLigne['categories'] as $categorieMenu): ?>
                                <li><a href="<?= htmlspecialchars(lienCategorie($categorieMenu['slug'])) ?>" class="lien-panneau <?= $menuCatActive === $categorieMenu['slug'] ? 'actif' : '' ?>"><?= htmlspecialchars($categorieMenu['nom']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="titre-panneau titre-raccourcis">À découvrir</p>
                        <ul class="list-unstyled mb-0 raccourcis-panneau">
                            <li><a href="catalogue.php?<?= htmlspecialchars(http_build_query(['categorie' => $universLigne['slug'], 'tri' => 'ventes'])) ?>" class="lien-panneau"><i class="bi bi-award" aria-hidden="true"></i> Meilleures ventes</a></li>
                            <li><a href="catalogue.php?<?= htmlspecialchars(http_build_query(['categorie' => $universLigne['slug'], 'prix_max' => 10])) ?>" class="lien-panneau"><i class="bi bi-tag" aria-hidden="true"></i> Petits prix</a></li>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
            <li><a href="catalogue.php" class="lien-lateral">Tout le catalogue <i class="bi bi-chevron-right"></i></a></li>
        </ul>
        <hr class="my-2 separateur-menu">
        <ul class="list-unstyled mb-0 liens-secondaires">
            <?php if ($userRole === 'auteur'): ?>
                <li><a href="proposer_ouvrage.php" class="lien-lateral-secondaire"><i class="bi bi-journal-plus"></i> Proposer une œuvre</a></li>
                <li><a href="proposer_evenement.php" class="lien-lateral-secondaire"><i class="bi bi-calendar-plus"></i> Proposer un événement</a></li>
            <?php else: ?>
                <li><a href="vendre.php" class="lien-lateral-secondaire"><i class="bi bi-arrow-repeat"></i> Donner ou revendre mes livres</a></li>
            <?php endif; ?>
            <li><a href="evenements.php" class="lien-lateral-secondaire"><i class="bi bi-calendar-event"></i> Événements</a></li>
            <?php if ($connected): ?>
                <li><a href="profil.php" class="lien-lateral-secondaire"><i class="bi bi-person"></i> Mon compte</a></li>
            <?php else: ?>
                <li><a href="login.php" class="lien-lateral-secondaire"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<main id="contenu">
