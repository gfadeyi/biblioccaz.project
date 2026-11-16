<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
$titrePage = $titrePage ?? 'Back-office';
$pageActive = $pageActive ?? '';
$compteurs = estEquipe() ? compteursEquipe($pdo) : ['livres' => 0, 'avis' => 0, 'candidatures' => 0];
require_once __DIR__ . '/../messagerie_equipe_lib.php';
$nonLusEquipe = estEquipe() ? nombreNonLusEquipe($pdo, (int) $_SESSION['user_id']) : 0;

$menu = [
    'Pilotage' => [
        ['index', './', 'speedometer2', 'Tableau de bord', 'equipe', 0],
        ['messagerie_equipe', 'messagerie_equipe', 'chat-left-text', 'Messagerie de l’équipe', 'equipe', $nonLusEquipe],
        ['historique_decisions', 'historique_decisions', 'clock-history', 'Historique des décisions', 'equipe', 0],
    ],
    'Catalogue' => [
        ['moderation_livres', 'moderation_livres', 'shield-check', 'Fiches à valider', 'catalogue', $compteurs['livres']],
        ['annonces', 'annonces', 'shop', 'Annonces', 'moderation', $compteurs['annonces'] ?? 0],
        ['inventaire', 'inventaire', 'box-seam', 'Inventaire & stock', 'catalogue', 0],
        ['livre', 'livre', 'plus-circle', 'Ajouter un livre', 'catalogue', 0],
        ['categories', 'categories', 'tags', 'Catégories & tags', 'admin', 0],
    ],
    'Ventes' => [
        ['commandes', 'commandes', 'bag-check', 'Commandes', 'catalogue', 0],
        ['service_client', 'service_client', 'headset', 'Service client', 'equipe', $compteurs['service_client'] ?? 0],
        ['retours', 'retours', 'arrow-return-left', 'Retours', 'catalogue', $compteurs['retours'] ?? 0],
        ['ventes', 'ventes', 'people', 'Entre lecteurs', 'ventes', $compteurs['ventes'] ?? 0],
        ['emprunts', 'emprunts', 'book', 'Emprunts', 'catalogue', $compteurs['emprunts'] ?? 0],
    ],
    'Communauté' => [
        ['avis_a_valider', 'avis_a_valider', 'patch-check', 'Avis à valider', 'moderation', $compteurs['avis_a_valider'] ?? 0],
        ['moderation_avis', 'moderation_avis', 'chat-square-text', 'Avis signalés', 'moderation', $compteurs['avis']],
        ['messages', 'messages', 'chat-dots', 'Messages', 'moderation', $compteurs['messages'] ?? 0],
        ['membres_points', 'membres_points', 'award', 'Niveaux & badges', 'equipe', 0],
        ['dons', 'dons', 'gift', 'Dons', 'dons_lecture', aLeDroit('dons') ? ($compteurs['dons'] ?? 0) : 0],
        ['evenements', 'evenements', 'calendar-event', 'Événements', 'evenements', $compteurs['evenements_proposes'] ?? 0],
        ['utilisateurs', 'utilisateurs', 'people', 'Membres', 'admin', 0],
        ['candidatures', 'candidatures', 'person-plus', 'Candidatures équipe', 'admin', $compteurs['candidatures']],
        ['candidatures_auteur', 'candidatures_auteur', 'feather', 'Candidatures auteur', 'candidatures_auteur', $compteurs['candidatures_auteur'] ?? 0],
        ['en_ligne', 'en_ligne', 'broadcast', 'En ligne', 'admin', 0],
        ['newsletter', 'newsletter', 'megaphone', 'Newsletter', 'admin', 0],
    ],
    'Système' => [
        ['sante', 'sante', 'heart-pulse', 'Santé du site', 'admin', 0],
        ['droits_equipe', 'droits_equipe', 'shield-lock', 'Droits de l’équipe', 'admin', 0],
        ['parametres_site', 'parametres_site', 'gear', 'Paramètres du site', 'admin', 0],
        ['relances_emprunts', 'relances_emprunts', 'bell', 'Suivi des relances', 'catalogue', 0],
        ['regles_emprunts', 'regles_emprunts', 'book', 'Règles des emprunts', 'admin', 0],
        ['regles_recompenses', 'regles_recompenses', 'sliders', 'Règles de récompenses', 'admin', 0],
        ['logs', 'logs', 'journal-text', "Journal d'activité", 'admin', 0],
        ['captcha', 'captcha', 'puzzle', 'Images du CAPTCHA', 'admin', 0],
    ],
];

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$sectionActive = '';
foreach ($menu as $section => $liens) {
    if (in_array($pageActive, array_column($liens, 0), true)) {
        $sectionActive = $section;
    }
}
$filAriane = $filAriane ?? ($pageActive === 'index' || $sectionActive === '' ? [] : [[null, $sectionActive]]);
$actionsPage = $actionsPage ?? [];
$sousTitrePage = $sousTitrePage ?? '';
$iconesFlash = ['success' => 'check-circle-fill', 'danger' => 'x-octagon-fill', 'warning' => 'exclamation-circle-fill', 'info' => 'info-circle-fill'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titrePage) ?> · Back-office BIBLIOccaz</title>
    <link rel="icon" type="image/svg+xml" href="<?= urlStatique('favicon.svg', '../') ?>">
<?php include __DIR__ . '/_application_web.php'; ?>
    <link rel="preload" href="../ressources/polices/source-sans-3-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="../ressources/polices/fraunces-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= urlStatique('ressources/polices/polices.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.min.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-icons-1.11.0/bootstrap-icons.min.css', '../') ?>">
    <link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>">
<?php if (!empty($stylesPage)) echo $stylesPage; ?>
    <script src="<?= urlStatique('javascript/couvertures.js', '../') ?>" data-defaut="../img/default.png"></script>
</head>
<body class="admin">
<a class="lien-evitement" href="#contenu-admin">Aller au contenu</a>

<aside class="offcanvas-lg offcanvas-start admin-menu" tabindex="-1" id="menuAdmin" aria-labelledby="menuAdminTitre">
    <div class="offcanvas-header">
        <span class="offcanvas-title fw-bold" id="menuAdminTitre">Menu</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menuAdmin" aria-label="Fermer"></button>
    </div>
    <div class="admin-menu-contenu">
        <a href="./" class="admin-logo">
            <i class="bi bi-book-half"></i>
            <span>BIBLIOccaz<small>Back-office</small></span>
        </a>

        <?php if (estEquipe()): ?>
            <nav aria-label="Menu du back-office">
                <?php foreach ($menu as $section => $liens): ?>
                    <?php $visibles = array_filter($liens, fn($l) => aLeDroit($l[4])); ?>
                    <?php if (!$visibles) continue; ?>
                    <div class="admin-menu-section"><?= $section ?></div>
                    <?php foreach ($visibles as [$cle, $lien, $icone, $libelle, , $nb]): ?>
                        <a href="<?= $lien ?>" data-menu="<?= $cle ?>" class="admin-menu-lien <?= $pageActive === $cle ? 'actif' : '' ?>" <?= $pageActive === $cle ? 'aria-current="page"' : '' ?>>
                            <i class="bi bi-<?= $icone ?>"></i>
                            <span><?= $libelle ?></span>
                            <?php if ($nb > 0): ?><span class="badge rounded-pill ms-auto"><?= $nb ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <a href="apercu" class="admin-menu-lien admin-menu-site" target="_blank" rel="noopener" aria-label="Aperçu du site (nouvel onglet)">
            <i class="bi bi-eye"></i><span>Aperçu du site</span><i class="bi bi-box-arrow-up-right ms-auto small" aria-hidden="true"></i>
        </a>
    </div>
</aside>

<div class="admin-page">
    <header class="admin-barre">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuAdmin" aria-controls="menuAdmin" aria-label="Ouvrir le menu">
            <i class="bi bi-list"></i>
        </button>
        <nav aria-label="Fil d'Ariane">
            <ol class="fil-ariane-admin">
                <?php if ($pageActive === 'index'): ?>
                    <li><span aria-current="page">Tableau de bord</span></li>
                <?php else: ?>
                    <li><a href="./">Tableau de bord</a></li>
                    <?php foreach ($filAriane as [$lienAriane, $libelleAriane]): ?>
                        <li><?= $lienAriane ? '<a href="' . e($lienAriane) . '">' . e($libelleAriane) . '</a>' : e($libelleAriane) ?></li>
                    <?php endforeach; ?>
                    <li><span aria-current="page"><?= e($titrePage) ?></span></li>
                <?php endif; ?>
            </ol>
        </nav>
        <?php if (estEquipe()): ?>
            <div class="dropdown ms-auto">
                <button type="button" class="bouton-profil dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mon profil : <?= e($_SESSION['pseudo'] ?? '') ?>">
                    <span class="pastille-profil" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($_SESSION['pseudo'] ?? '?', 0, 1))) ?></span>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['pseudo'] ?? '') ?></span>
                    <span class="role-profil d-none d-md-inline"><?= ROLES_LIBELLES[$_SESSION['role']] ?? '' ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="mot_de_passe"><i class="bi bi-shield-lock me-2"></i>Changer mon mot de passe</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="POST" action="deconnexion"><?= champCsrf() ?><button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</button></form></li>
                </ul>
            </div>
        <?php endif; ?>
    </header>

    <main class="admin-contenu" id="contenu-admin" tabindex="-1">
        <div class="entete-page">
            <div>
                <h1><?= e($titrePage) ?></h1>
                <?php if ($sousTitrePage !== ''): ?><p><?= e($sousTitrePage) ?></p><?php endif; ?>
            </div>
            <?php if ($actionsPage): ?>
                <div class="actions-page">
                    <?php foreach ($actionsPage as [$lienAction, $iconeAction, $libelleAction, $classeAction]): ?>
                        <a href="<?= e($lienAction) ?>" class="btn <?= $classeAction ?>"><i class="bi bi-<?= $iconeAction ?> me-1" aria-hidden="true"></i> <?= e($libelleAction) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash[0]) ?> alert-dismissible flash-admin" role="<?= $flash[0] === 'danger' ? 'alert' : 'status' ?>" <?= $flash[0] === 'success' ? 'data-disparition' : '' ?>>
                <i class="bi bi-<?= $iconesFlash[$flash[0]] ?? 'info-circle-fill' ?>" aria-hidden="true"></i>
                <span><?= e($flash[1]) ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>
