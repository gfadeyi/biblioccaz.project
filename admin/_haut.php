<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
$titrePage = $titrePage ?? 'Back-office';
$pageActive = $pageActive ?? '';
$compteurs = estEquipe() ? compteursEquipe($pdo) : ['livres' => 0, 'avis' => 0, 'candidatures' => 0];

$menu = [
    'Pilotage' => [
        ['index', 'index.php', 'speedometer2', 'Tableau de bord', 'equipe', 0],
    ],
    'Catalogue' => [
        ['moderation_livres', 'moderation_livres.php', 'shield-check', 'Fiches à valider', 'moderation', $compteurs['livres']],
        ['annonces', 'annonces.php', 'shop', 'Annonces', 'moderation', $compteurs['annonces'] ?? 0],
        ['inventaire', 'inventaire.php', 'box-seam', 'Inventaire & stock', 'catalogue', 0],
        ['livre', 'livre.php', 'plus-circle', 'Ajouter un livre', 'catalogue', 0],
        ['categories', 'categories.php', 'tags', 'Catégories & tags', 'admin', 0],
    ],
    'Ventes' => [
        ['commandes', 'commandes.php', 'bag-check', 'Commandes', 'catalogue', 0],
    ],
    'Communauté' => [
        ['avis_a_valider', 'avis_a_valider.php', 'patch-check', 'Avis à valider', 'moderation', $compteurs['avis_a_valider'] ?? 0],
        ['moderation_avis', 'moderation_avis.php', 'chat-square-text', 'Avis signalés', 'moderation', $compteurs['avis']],
        ['messages', 'messages.php', 'chat-dots', 'Messages', 'moderation', $compteurs['messages'] ?? 0],
        ['membres_points', 'membres_points.php', 'award', 'Niveaux & badges', 'equipe', 0],
        ['dons', 'dons.php', 'gift', 'Dons', 'equipe', 0],
        ['evenements', 'evenements.php', 'calendar-event', 'Événements', 'evenements', $compteurs['evenements_proposes'] ?? 0],
        ['utilisateurs', 'utilisateurs.php', 'people', 'Membres', 'admin', 0],
        ['candidatures', 'candidatures.php', 'person-plus', 'Candidatures', 'admin', $compteurs['candidatures']],
        ['en_ligne', 'en_ligne.php', 'broadcast', 'En ligne', 'admin', 0],
        ['newsletter', 'newsletter.php', 'megaphone', 'Newsletter', 'admin', 0],
    ],
    'Système' => [
        ['logs', 'logs.php', 'journal-text', "Journal d'activité", 'admin', 0],
        ['captcha', 'captcha.php', 'puzzle', 'Images du CAPTCHA', 'admin', 0],
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titrePage) ?> · Back-office BIBLIOccaz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>">
</head>
<body class="admin">

<aside class="offcanvas-lg offcanvas-start admin-menu" tabindex="-1" id="menuAdmin" aria-labelledby="menuAdminTitre">
    <div class="offcanvas-header">
        <span class="offcanvas-title fw-bold" id="menuAdminTitre">Menu</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menuAdmin" aria-label="Fermer"></button>
    </div>
    <div class="admin-menu-contenu">
        <a href="index.php" class="admin-logo">
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
                        <a href="<?= $lien ?>" class="admin-menu-lien <?= $pageActive === $cle ? 'actif' : '' ?>" <?= $pageActive === $cle ? 'aria-current="page"' : '' ?>>
                            <i class="bi bi-<?= $icone ?>"></i>
                            <span><?= $libelle ?></span>
                            <?php if ($nb > 0): ?><span class="badge rounded-pill ms-auto"><?= $nb ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <a href="../index.php" class="admin-menu-lien admin-menu-site">
            <i class="bi bi-shop"></i><span>Voir le site client</span><i class="bi bi-box-arrow-up-right ms-auto small"></i>
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
                    <li><a href="index.php">Tableau de bord</a></li>
                    <?php foreach ($filAriane as [$lienAriane, $libelleAriane]): ?>
                        <li><?= $lienAriane ? '<a href="' . e($lienAriane) . '">' . e($libelleAriane) . '</a>' : e($libelleAriane) ?></li>
                    <?php endforeach; ?>
                    <li><span aria-current="page"><?= e($titrePage) ?></span></li>
                <?php endif; ?>
            </ol>
        </nav>
        <?php if (estEquipe()): ?>
            <div class="dropdown ms-auto">
                <button class="bouton-profil dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="pastille-profil" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($_SESSION['pseudo'] ?? '?', 0, 1))) ?></span>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['pseudo'] ?? '') ?></span>
                    <span class="role-profil d-none d-md-inline"><?= ROLES_LIBELLES[$_SESSION['role']] ?? '' ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="mot_de_passe.php"><i class="bi bi-shield-lock me-2"></i>Changer mon mot de passe</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="deconnexion.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </header>

    <main class="admin-contenu">
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
