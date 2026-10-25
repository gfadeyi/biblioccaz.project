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
        ['inventaire', 'inventaire.php', 'box-seam', 'Inventaire & stock', 'catalogue', 0],
        ['livre', 'livre.php', 'plus-circle', 'Ajouter un livre', 'catalogue', 0],
        ['categories', 'categories.php', 'tags', 'Catégories & tags', 'admin', 0],
    ],
    'Communauté' => [
        ['moderation_avis', 'moderation_avis.php', 'chat-square-text', 'Avis signalés', 'moderation', $compteurs['avis']],
        ['dons', 'dons.php', 'gift', 'Dons', 'equipe', 0],
        ['evenements', 'evenements.php', 'calendar-event', 'Événements', 'evenements', 0],
        ['utilisateurs', 'utilisateurs.php', 'people', 'Membres', 'admin', $compteurs['candidatures']],
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titrePage) ?> · Back-office BIBLIOccaz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
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
            <nav>
                <?php foreach ($menu as $section => $liens): ?>
                    <?php $visibles = array_filter($liens, fn($l) => aLeDroit($l[4])); ?>
                    <?php if (!$visibles) continue; ?>
                    <div class="admin-menu-section"><?= $section ?></div>
                    <?php foreach ($visibles as [$cle, $lien, $icone, $libelle, , $nb]): ?>
                        <a href="<?= $lien ?>" class="admin-menu-lien <?= $pageActive === $cle ? 'actif' : '' ?>">
                            <i class="bi bi-<?= $icone ?>"></i>
                            <span><?= $libelle ?></span>
                            <?php if ($nb > 0): ?><span class="badge rounded-pill bg-danger ms-auto"><?= $nb ?></span><?php endif; ?>
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
        <h1 class="admin-titre"><?= e($titrePage) ?></h1>
        <?php if (estEquipe()): ?>
            <div class="dropdown ms-auto">
                <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-sm-inline"><?= e($_SESSION['pseudo'] ?? '') ?></span>
                    <span class="badge bg-dark-subtle text-dark-emphasis"><?= ROLES_LIBELLES[$_SESSION['role']] ?? '' ?></span>
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
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash[0]) ?> alert-dismissible fade show" role="alert">
                <?= e($flash[1]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>
