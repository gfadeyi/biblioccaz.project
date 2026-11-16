<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../apercu_lib.php';
if (!estEquipe()) {
    $_SESSION['admin_retour'] = 'apercu';
    header('Location: login');
    exit();
}
if (isset($_GET['quitter'])) {
    fermerApercuBackOffice();
    rediriger('./');
}
$clientDisponible = clientApercu($pdo) !== null;
$vue = ($_GET['vue'] ?? '') === 'client' && $clientDisponible ? 'client' : 'invite';
$format = ($_GET['format'] ?? '') === 'mobile' ? 'mobile' : 'desktop';
if (!ouvrirApercuBackOffice((int) $_SESSION['user_id'], $vue)) {
    rediriger('./', 'danger', "L'aperçu du site est momentanément indisponible.");
}
insertLog('ADMIN', 'Aperçu du site en lecture seule (' . APERCU_VUES[$vue] . ')');
$lien = fn(string $v, string $f): string => 'apercu?' . http_build_query(['vue' => $v, 'format' => $f]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Aperçu du site · Back-office BIBLIOccaz</title>
    <link rel="icon" type="image/svg+xml" href="<?= urlStatique('favicon.svg', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.min.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('ressources/bootstrap-icons-1.11.0/bootstrap-icons.min.css', '../') ?>">
    <link rel="stylesheet" href="<?= urlStatique('admin/admin.css', '../') ?>">
</head>
<body class="admin apercu-outil">
<header class="apercu-barre" role="toolbar" aria-label="Contrôles de l’aperçu">
    <span class="apercu-barre__titre"><i class="bi bi-eye" aria-hidden="true"></i> Aperçu du site <span class="apercu-barre__etat">lecture seule</span></span>
    <nav class="apercu-choix" aria-label="Vue simulée">
        <a href="<?= e($lien('invite', $format)) ?>" class="<?= $vue === 'invite' ? 'actif' : '' ?>"<?= $vue === 'invite' ? ' aria-current="true"' : '' ?>><i class="bi bi-person" aria-hidden="true"></i> Vue Invité</a>
        <?php if ($clientDisponible): ?>
            <a href="<?= e($lien('client', $format)) ?>" class="<?= $vue === 'client' ? 'actif' : '' ?>"<?= $vue === 'client' ? ' aria-current="true"' : '' ?>><i class="bi bi-person-check" aria-hidden="true"></i> Vue Client</a>
        <?php else: ?>
            <span class="indisponible" title="Compte de démonstration absent : appliquer sql/maj_apercu_client.sql"><i class="bi bi-person-check" aria-hidden="true"></i> Vue Client indisponible</span>
        <?php endif; ?>
    </nav>
    <nav class="apercu-choix" aria-label="Largeur d’affichage">
        <a href="<?= e($lien($vue, 'desktop')) ?>" class="<?= $format === 'desktop' ? 'actif' : '' ?>"<?= $format === 'desktop' ? ' aria-current="true"' : '' ?>><i class="bi bi-display" aria-hidden="true"></i> Desktop</a>
        <a href="<?= e($lien($vue, 'mobile')) ?>" class="<?= $format === 'mobile' ? 'actif' : '' ?>"<?= $format === 'mobile' ? ' aria-current="true"' : '' ?>><i class="bi bi-phone" aria-hidden="true"></i> Mobile</a>
    </nav>
    <a href="apercu?quitter=1" class="apercu-barre__sortie"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Quitter l’aperçu</a>
</header>
<main class="apercu-zone apercu-zone--<?= $format ?>">
    <?php if ($format === 'mobile'): ?>
        <p class="apercu-note">Largeur de 390 px : la mise en page mobile du site, sans émulation du matériel (tactile, encoche, navigateur).</p>
    <?php endif; ?>
    <iframe class="apercu-cadre" src="../" title="Site en aperçu, <?= e(APERCU_VUES[$vue]) ?>" sandbox="allow-same-origin allow-scripts allow-forms allow-popups" referrerpolicy="same-origin"></iframe>
</main>
</body>
</html>
