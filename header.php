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
require_once __DIR__ . '/demonstration_lib.php';
$photoMembre = $connected ? photoProfilDe($pdo, (int) $_SESSION['user_id']) : null;
$idsFavoris = $connected ? idsFavoris($pdo, (int) $_SESSION['user_id']) : [];
$nb_favoris = count($idsFavoris);
if ($connected) {
    require_once __DIR__ . '/emprunts_lib.php';
    verifierEmpruntsSiBesoin($pdo);
}
require_once __DIR__ . '/livraison_lib.php';
avancerSuiviCommandesSiBesoin($pdo);
$resumeCompte = ($connected && $userRole === 'client') ? resumeCompte($pdo, (int) $_SESSION['user_id']) : null;
$nbMessagesNonLus = ($connected && in_array($userRole, ['client', 'auteur'], true)) ? nombreMessagesNonLus($pdo, (int) $_SESSION['user_id']) : 0;
require_once __DIR__ . '/notifications_membre_lib.php';
purgerNotificationsSiNecessaire($pdo);
$avecCloche = $connected && in_array($userRole, ['client', 'auteur'], true);
$nbNotificationsNonLues = $avecCloche ? nombreNotificationsNonLues($pdo, (int) $_SESSION['user_id']) : 0;
require_once __DIR__ . '/service_client_lib.php';
$nbReponsesService = $avecCloche ? nombreReponsesServiceNonLues($pdo, (int) $_SESSION['user_id']) : 0;
$apercuNotifications = $avecCloche ? notificationsMembre($pdo, (int) $_SESSION['user_id'], NOTIFICATIONS_APERCU) : [];
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
            require_once __DIR__ . '/candidatures_equipe_lib.php';
            $nb_notif_modo = nombreCandidaturesEquipeEnAttente($pdo);
        }
    } catch (PDOException $e) {
    }
    $total_notif_admin = $nb_notif_modo + $nb_notif_livres + $nb_notif_avis;
}

$colonnesMenuCompte = [];
$serviceClientMenu = $avecCloche ? ['service_client.php', 'tasse', 'Service client', $nbReponsesService] : null;
if ($resumeCompte) {
    $colonnesMenuCompte = [
        ['Mon compte & ventes', [
            ['profil.php', 'lecteur', 'Mon profil', 0],
            ['commandes.php', 'colis', 'Mes achats', $resumeCompte['commandes']],
            ['retours.php', 'carton', 'Mes retours', 0],
            ['mes_annonces.php', 'etiquette', 'Mes annonces', $resumeCompte['annonces'] ?? 0],
            ['mes_transactions.php', 'cabas', 'Mes ventes entre lecteurs', $resumeCompte['transactions'] ?? 0],
            ['adresses.php', 'enveloppe', 'Mes adresses', 0],
            ['alertes.php', 'clochette', 'Mes alertes', $resumeCompte['alertes']],
        ]],
        ['Dons, emprunts & communauté', [
            ['mes_dons.php', 'seconde-vie', 'Mes dons', $resumeCompte['dons']],
            ['mes_emprunts.php', 'livre-ouvert', 'Mes emprunts', $resumeCompte['emprunts'] ?? 0],
            ['mes_evenements.php', 'ticket', 'Mes événements', $resumeCompte['evenements']],
            ['ma_collection.php', 'pile', 'Mes collections', 0],
            ['avis.php', 'plume', 'Mes avis', $resumeCompte['avis']],
            ['wishlist.php', 'marque-page', 'Mes favoris', $nb_favoris, true],
            ['messages.php', 'bulle', 'Mes messages', $nbMessagesNonLus, true],
        ]],
    ];
} elseif ($connected && $userRole === 'auteur') {
    $colonnesMenuCompte = [
        ['Mon espace créateur', [
            ['profil.php', 'lecteur', 'Mon profil', 0],
            ['dashboard_auteur.php', 'plume', 'Espace créateur', 0],
            ['proposer_ouvrage.php', 'livre-ouvert', 'Proposer un livre', 0],
            ['proposer_evenement.php', 'ticket', 'Proposer un événement', 0],
        ]],
        ['Communauté & activités', [
            ['mes_annonces.php', 'etiquette', 'Mes annonces', 0],
            ['mes_transactions.php', 'cabas', 'Mes ventes entre lecteurs', 0],
            ['mes_emprunts.php', 'pile', 'Mes emprunts', 0],
            ['ma_collection.php', 'pile', 'Mes collections', 0],
            ['messages.php', 'bulle', 'Mes messages', $nbMessagesNonLus, true],
        ]],
    ];
} elseif ($connected) {
    $colonnesMenuCompte = [
        [null, [
            ['profil.php', 'lecteur', 'Mon profil', 0],
            ['admin/index.php', 'livre-ouvert', 'Back-office', $total_notif_admin],
            ['admin/moderation_livres.php', 'tampon', 'Fiches à valider', $nb_notif_livres],
            ['admin/moderation_avis.php', 'bulle', 'Avis signalés', $nb_notif_avis],
        ]],
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
    ['plus_emprunts.php', 'bi-book', 'Les plus empruntés'],
    ['plus_dons.php', 'bi-gift', 'Les plus donnés'],
    ['petits_prix.php', 'bi-tag', 'Moins de 10 €'],
    ['dernière_chance.php', 'bi-hourglass-split', 'Dernière chance'],
    ['dons.php', 'bi-gift', 'Voir les livres à donner'],
];
$classeRaccourci = fn(array $pages) => in_array($pageActuelle, $pages, true) ? 'puce actif' : 'puce';
$titresOnglets = [
    'index.php' => 'Livres d’occasion, dons et emprunts',
    'newsletter_confirmer.php' => 'Mon abonnement à la newsletter', 'verification_mail.php' => 'Valider mon adresse email', 'renvoyer_validation.php' => 'Recevoir un lien de validation',
    'login.php' => 'Se connecter', 'inscription.php' => 'Créer un compte',
    'profil.php' => 'Mon compte', 'points_badges.php' => 'Mes points et badges',
    'commandes.php' => 'Mes commandes', 'wishlist.php' => 'Mes favoris',
    'alertes.php' => 'Mes alertes de disponibilité', 'adresses.php' => 'Mes adresses',
    'avis.php' => 'Mes avis', 'mes_dons.php' => 'Mes dons',
    'mes_evenements.php' => 'Mes événements', 'suggestions.php' => 'Suggestions de lecture',
    'trouver_lecture.php' => 'Trouver ma prochaine lecture',
    'evenements.php' => 'Les événements', 'evenement.php' => 'Détail de l’événement',
    'dons.php' => 'Voir les livres à donner', 'don_creer.php' => 'Proposer un don',
    'don_detail.php' => 'Détail du don', 'vendre.php' => 'Donner ou revendre un livre',
    'auteurs.php' => 'Nos auteurs', 'dashboard_auteur.php' => 'Espace auteur',
    'proposer_evenement.php' => 'Proposer un événement', 'proposer_ouvrage.php' => 'Proposer un livre',
    'candidature_auteur.php' => 'Devenir auteur', 'rejoindre_equipe.php' => 'Rejoindre l’équipe',
    'modifier_profil.php' => 'Modifier mon profil', 'modifier_email.php' => 'Modifier mon adresse email',
    'recuparation_mdp.php' => 'Retrouver mon accès', 'modifier_mdp.php' => 'Choisir un mot de passe',
    'achat.php' => 'Paiement de démonstration', 'confirmation_achat.php' => 'Confirmation de commande',
];
$pageReferencement = cheminPageReferencement();
$indexable = indexationPermise($pageReferencement);
if (!$indexable && !headers_sent()) { header('X-Robots-Tag: noindex, nofollow'); }
$nomOnglet = trim((string) ($titreDocument ?? $titrePage ?? $titresOnglets[$pageActuelle] ?? 'Bibliothèque d’occasion'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php if (!empty($adresseBase)): ?><base href="<?= htmlspecialchars($adresseBase) ?>"><?php endif; ?>
    <title><?= htmlspecialchars($nomOnglet . ' - BIBLIOccaz', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars(descriptionReferencement($pageReferencement, $nomOnglet), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= $indexable ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($indexable): ?>
    <link rel="canonical" href="<?= htmlspecialchars(adresseCanonique($pageReferencement), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="BIBLIOccaz">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="<?= htmlspecialchars($nomOnglet, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars(descriptionReferencement($pageReferencement, $nomOnglet), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars(adresseCanonique($pageReferencement), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/ressources/partage-biblioccaz.png', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="BIBLIOccaz — Livres d’occasion et communauté de lecteurs. Démonstration.">
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    <link rel="icon" type="image/svg+xml" href="<?= urlStatique('favicon.svg') ?>">
    <link rel="manifest" href="<?= urlStatique('manifest.json') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= urlStatique('apple-touch-icon.png') ?>">
    <meta name="theme-color" content="#274e13">
    <meta name="application-name" content="BibliOccaz">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BibliOccaz">
    <link rel="stylesheet" href="<?= urlStatique('ressources/polices/polices.css') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-icons-1.11.0/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
    <meta name="csrf" content="<?= jetonCsrf() ?>">
    <meta name="panier-expiration" content="<?= (int) ($_SESSION['panier_expiration'] ?? 0) ?>">
    <meta name="heure-serveur" content="<?= time() ?>">
    <link rel="stylesheet" href="<?= urlStatique('boutique.css') ?>">
    <link rel="stylesheet" href="<?= urlStatique('scanner_isbn.css') ?>">
    <script src="javascript/recherche.js?v=<?= filemtime(__DIR__ . '/javascript/recherche.js') ?>" defer></script>
    <script src="javascript/raccourcis.js?v=<?= filemtime(__DIR__ . '/javascript/raccourcis.js') ?>" defer></script>
    <script src="javascript/menu_univers.js?v=<?= filemtime(__DIR__ . '/javascript/menu_univers.js') ?>" defer></script>
    <script src="javascript/menu_compte.js?v=<?= filemtime(__DIR__ . '/javascript/menu_compte.js') ?>" defer></script>
    <script src="<?= urlStatique('javascript/couvertures.js') ?>" data-defaut="img/default.png"></script>
    <script src="<?= urlStatique('javascript/bandeau_demo.js') ?>" defer></script>
    <script src="<?= urlStatique('javascript/scanner_isbn.js') ?>" defer></script>
    <script src="<?= urlStatique('javascript/application_web.js') ?>" defer></script>
    <script src="<?= urlStatique('javascript/tiroir_notifications.js') ?>" defer></script>
</head>
<body>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>
<?php if (bandeauDemonstrationVisible()): ?>
    <div class="bandeau-demo" role="note" data-bandeau-demo="<?= COOKIE_BANDEAU_DEMONSTRATION ?>">
        <div class="container-xxl bandeau-demo__contenu">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <p><?= htmlspecialchars(MENTION_DEMONSTRATION) ?></p>
            <button type="button" class="bandeau-demo__fermer" data-fermer-bandeau-demo aria-label="Fermer le bandeau de démonstration"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>
<?php endif; ?>

<header class="entete">
    <div class="container-xxl ligne-entete d-flex align-items-center gap-3 pt-2">
        <a class="logo-bibli" href="./">
            <span class="logo-nom">BIBLIOccaz</span>
            <span class="logo-slogan">Donnez une seconde vie à vos lectures</span>
        </a>

        <form action="catalogue.php" method="GET" class="recherche-entete flex-grow-1 position-relative" role="search">
            <label for="barre-recherche" class="visually-hidden">Rechercher un livre</label>
            <input type="search" id="barre-recherche" name="q" autocomplete="off" class="champ-recherche"
                   maxlength="200" placeholder="Titre, auteur, ISBN…" value="<?= htmlspecialchars(is_string($_GET['q'] ?? null) ? mb_substr($_GET['q'], 0, 200) : '') ?>" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="suggestions-recherche" aria-expanded="false" aria-describedby="aide-recherche">
            <button type="button" class="bouton-scanner-entete" data-scanner-isbn data-scanner-mode="recherche" data-scanner-cible="barre-recherche" aria-label="Scanner un ISBN" title="Scanner un ISBN" hidden><i class="bi bi-upc-scan" aria-hidden="true"></i></button>
            <button type="submit" class="bouton-loupe" aria-label="Rechercher"><?= iconeDessin('loupe') ?></button>
            <div id="suggestions-recherche" class="suggestions-recherche list-group position-absolute w-100 mt-1 shadow d-none" role="listbox" aria-label="Livres suggérés"></div>
            <p id="statut-recherche" class="recherche-statut position-absolute w-100 mt-1 d-none" role="status" aria-live="polite" aria-atomic="true"></p>
            <span id="aide-recherche" class="visually-hidden">Saisissez un titre, un auteur ou un ISBN. Utilisez les flèches pour parcourir les suggestions, Entrée pour ouvrir un livre ou lancer la recherche, Échap pour fermer.</span>
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
            <?php if ($avecCloche): ?>
                <div class="dropdown dropdown-hover position-relative" id="entete-notifications" data-tiroir-notifications>
                    <a href="notifications.php" class="icone-entete" aria-haspopup="true" aria-expanded="false" aria-controls="menu-notifications" aria-label="Notifications<?= $nbNotificationsNonLues ? ', ' . $nbNotificationsNonLues . ' non lue' . ($nbNotificationsNonLues > 1 ? 's' : '') : '' ?>">
                        <?= iconeDessin('clochette') ?><span>Notifs</span>
                        <?php if ($nbNotificationsNonLues > 0): ?><span class="pastille" data-compteur-notifications><?= $nbNotificationsNonLues > 99 ? '99+' : $nbNotificationsNonLues ?></span><?php endif; ?>
                    </a>
                    <div class="voile-tiroir" hidden></div>
                    <ul class="dropdown-menu dropdown-menu-end p-2 shadow border-0 menu-notifications" id="menu-notifications">
                        <li class="menu-notifications__fermer"><button type="button" class="bouton-fermer-menu" aria-label="Fermer les notifications"><i class="bi bi-x-lg" aria-hidden="true"></i></button></li>
                        <li class="menu-notifications__titre">
                            <span data-titre-notifications>Notifications<?= $nbNotificationsNonLues ? ' (' . $nbNotificationsNonLues . ' non lue' . ($nbNotificationsNonLues > 1 ? 's' : '') . ')' : '' ?></span>
                            <?php if ($nbNotificationsNonLues > 0): ?>
                                <form method="POST" action="notification_action.php" class="menu-notifications__tout-lire" data-tout-lire>
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="tout_lire">
                                    <button type="submit">Tout marquer comme lu</button>
                                </form>
                            <?php endif; ?>
                        </li>
                        <?php if (!$apercuNotifications): ?>
                            <li class="menu-notifications__vide">Aucune notification pour le moment.</li>
                        <?php endif; ?>
                        <?php foreach ($apercuNotifications as $notificationEntete): ?>
                            <li>
                                <form method="POST" action="notification_action.php">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="ouvrir">
                                    <input type="hidden" name="id_notification" value="<?= (int) $notificationEntete['id_notification'] ?>">
                                    <button type="submit" class="notification__bouton<?= (int) $notificationEntete['lu'] ? '' : ' notification--non-lue' ?>">
                                        <span class="notification__icone"><?= iconeDessin(iconeNotification($notificationEntete['type'])) ?></span>
                                        <span class="notification__texte"><span><?= htmlspecialchars($notificationEntete['message']) ?></span><small><?= htmlspecialchars(dateNotification($notificationEntete['date_creation'])) ?></small></span>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                        <li class="menu-notifications__pied"><a href="notifications.php">Voir toutes mes notifications</a></li>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="dropdown dropdown-hover zone-menu-compte" data-menu-compte>
                <a href="<?= $connected ? 'profil.php' : htmlspecialchars(lienConnexion()) ?>" class="icone-entete" aria-label="<?= $connected ? 'Mon compte' : 'Connexion' ?>" aria-haspopup="true" aria-expanded="false" aria-controls="menu-compte">
                    <?= $connected && cheminPhotoMembre($photoMembre) ? avatarMembre($_SESSION['pseudo'], $photoMembre, 'entete') : iconeDessin('lecteur') ?>
                    <span class="text-truncate" style="max-width: 90px;"><?= $connected ? htmlspecialchars(pseudoAffiche($_SESSION['pseudo'])) : 'Connexion' ?></span>
                    <?php if ($total_notif_admin > 0): ?>
                        <span class="pastille"><?= $total_notif_admin ?></span>
                    <?php endif; ?>
                </a>
                <div class="voile-menu-compte" hidden></div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 menu-entete<?= count($colonnesMenuCompte) > 1 ? ' menu-entete--large' : '' ?>" id="menu-compte">
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
                            <li>
                                <a href="points_badges.php" class="menu-entete__points">
                                    <span class="menu-entete__solde"><?= iconeDessin('medaille') ?><strong><?= (int) $solde_points ?></strong> points à utiliser <span class="menu-entete__voir">Points et badges <i class="bi bi-chevron-right" aria-hidden="true"></i></span></span>
                                    <span class="jauge-niveau" style="--progression: <?= round($resumeCompte['niveau']['progression'], 3) ?>;"><span></span></span>
                                    <small>
                                        <?php if ($resumeCompte['niveau']['prochain']): ?>
                                            Encore <?= $resumeCompte['niveau']['reste'] ?> pts pour devenir « <?= htmlspecialchars($resumeCompte['niveau']['prochain']['nom']) ?> »
                                        <?php else: ?>
                                            Niveau maximum atteint, bravo !
                                        <?php endif; ?>
                                    </small>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="menu-entete__colonnes<?= count($colonnesMenuCompte) > 1 ? ' menu-entete__colonnes--double' : '' ?>">
                            <?php foreach ($colonnesMenuCompte as $indexColonne => [$titreColonne, $liensColonne]): ?>
                                <div class="menu-entete__colonne">
                                    <?php if ($titreColonne !== null): ?>
                                        <p class="menu-entete__titre-colonne" id="menu-compte-colonne-<?= $indexColonne ?>"><?= htmlspecialchars($titreColonne) ?></p>
                                    <?php endif; ?>
                                    <ul class="menu-entete__liens"<?= $titreColonne !== null ? ' aria-labelledby="menu-compte-colonne-' . $indexColonne . '"' : '' ?>>
                                        <?php foreach ($liensColonne as $entreeMenu): ?>
                                            <?php [$lienMenu, $iconeMenu, $libelleMenu, $compteurMenu] = $entreeMenu; ?>
                                            <li<?= !empty($entreeMenu[4]) ? ' class="menu-entete__petit-ecran"' : '' ?>>
                                                <a class="lien-menu-compte" href="<?= $lienMenu ?>">
                                                    <?= iconeDessin($iconeMenu) ?><span><?= $libelleMenu ?></span>
                                                    <?php if ($compteurMenu > 0): ?><span class="compteur-menu<?= $resumeCompte ? '' : ' urgent' ?>"><?= $compteurMenu ?></span><?php endif; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </li>
                        <li class="menu-entete__pied">
                            <?php if ($serviceClientMenu): ?>
                                <?php [$lienMenu, $iconeMenu, $libelleMenu, $compteurMenu] = $serviceClientMenu; ?>
                                <a class="lien-menu-compte" href="<?= $lienMenu ?>">
                                    <?= iconeDessin($iconeMenu) ?><span><?= $libelleMenu ?></span>
                                    <?php if ($compteurMenu > 0): ?><span class="compteur-menu<?= $resumeCompte ? '' : ' urgent' ?>"><?= $compteurMenu ?></span><?php endif; ?>
                                </a>
                            <?php endif; ?>
                            <form method="POST" action="logout.php" class="menu-entete__deconnexion"><input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="lien-menu-compte deconnexion border-0 bg-transparent text-start"><?= iconeDessin('porte') ?><span>Déconnexion</span></button></form>
                        </li>
                    <?php else: ?>
                        <li class="menu-entete__accueil">Bienvenue ! Retrouvez vos favoris, vos points et vos commandes.</li>
                        <li><a class="lien-menu-compte" href="<?= htmlspecialchars(lienConnexion()) ?>"><?= iconeDessin('porte') ?><span>Se connecter</span></a></li>
                        <li><a class="lien-menu-compte" href="inscription.php"><?= iconeDessin('lecteur') ?><span>Créer un compte</span></a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown dropdown-hover position-relative" id="entete-panier">
                <a href="panier.php" class="icone-entete" aria-label="Mon panier">
                    <?= iconeDessin('cabas', $livreAjoute ? 'secousse' : '') ?><span>Panier</span>
                    <?php if ($totalArticles > 0): ?>
                        <span data-panier-compteur class="pastille<?= $livreAjoute ? ' rebond' : '' ?>"><?= $totalArticles ?></span>
                    <?php endif; ?>
                </a>
                <span class="entete-panier-minute" data-panier-minute data-expiration="<?= (int) ($_SESSION['panier_expiration'] ?? 0) ?>" data-maintenant="<?= time() ?>"></span>
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
        <button class="bouton-categories" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuCategories" aria-controls="menuCategories" aria-label="Toutes les catégories">
            <?= iconeDessin('pile') ?> <span>Toutes les catégories</span>
        </button>
        <nav class="defilement-raccourcis" aria-label="Raccourcis">
        <a href="nouveauté.php" class="<?= $classeRaccourci(['nouveauté.php']) ?>"><i class="bi bi-stars"></i> Nouveautés</a>
        <a href="meilleures_ventes.php" class="<?= $classeRaccourci(['meilleures_ventes.php']) ?>"><i class="bi bi-award"></i> Meilleures ventes</a>
        <a href="petits_prix.php" class="<?= $classeRaccourci(['petits_prix.php']) ?>"><i class="bi bi-tag"></i> Moins de 10 €</a>
        <a href="dernière_chance.php" class="<?= $classeRaccourci(['dernière_chance.php']) ?>"><i class="bi bi-hourglass-split"></i> Dernière chance</a>
        <a href="dons.php" class="<?= $classeRaccourci(['dons.php', 'don_detail.php']) ?>"><i class="bi bi-gift"></i> Voir les livres à donner</a>
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
                <li><a href="<?= htmlspecialchars(lienConnexion()) ?>" class="lien-lateral-secondaire"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="zone-defilement" id="zone-defilement">
<main id="contenu" tabindex="-1">
<?php $messageConnexion = messageApresConnexion(); if ($messageConnexion !== null): ?>
    <div class="container-xxl pt-3">
        <div class="alert alert-success alert-dismissible fade show mb-0" role="status" data-message-apres-connexion>
            <i class="bi bi-heart-fill me-1" aria-hidden="true"></i> <?= htmlspecialchars($messageConnexion) ?> <a href="wishlist.php" class="alert-link">Voir mes favoris</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    </div>
<?php endif; ?>
