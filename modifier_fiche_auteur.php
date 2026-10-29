<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'auteurs_lib.php';

if (($_SESSION['role'] ?? '') !== 'auteur') {
    header('Location: ' . (isset($_SESSION['user_id']) ? 'candidature_auteur.php' : 'login.php'));
    exit();
}
$idAuteur = (int) $_SESSION['user_id'];
$disponible = candidaturesAuteurDisponibles($pdo);
$fiche = $disponible ? ficheAuteurDe($pdo, $idAuteur) : null;
$erreurs = [];

if ($fiche) {
    $valeursAuteur = valeursFormulaireAuteur($fiche, false);
    $ouvragesAuteur = $fiche['ouvrages'];
} else {
    $stmt = $pdo->prepare('SELECT prenom, nom FROM user WHERE id = ?');
    $stmt->execute([$idAuteur]);
    $identite = $stmt->fetch() ?: ['prenom' => '', 'nom' => ''];
    $valeursAuteur = valeursFormulaireAuteur(['nom_plume' => trim($identite['prenom'] . ' ' . $identite['nom'])], false);
    $stmt = $pdo->prepare('SELECT titre, editeur, annee_parution AS annee, isbn FROM livre WHERE id_user_auteur = ? AND is_valide = 1 ORDER BY annee_parution DESC, id_livre DESC LIMIT ' . AUTEUR_OUVRAGES_MAX);
    $stmt->execute([$idAuteur]);
    $ouvragesAuteur = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $disponible) {
    $saisie = saisieFicheAuteur($_POST, false);
    $valeursAuteur = $saisie['valeurs'];
    $ouvragesAuteur = $saisie['ouvrages'];
    $erreurs = $saisie['erreurs'];
    if (!verifierCsrf()) {
        $erreurs = ['Votre session a expiré : vérifiez vos informations, ajoutez de nouveau la photo si besoin, puis renvoyez le formulaire.'];
    }
    $photo = null;
    if (!$erreurs) {
        [$photo, $erreurPhoto] = photoAuteurEnvoyee();
        if ($erreurPhoto !== '') {
            $erreurs[] = $erreurPhoto;
        }
    }
    if (!$erreurs) {
        try {
            $resultat = enregistrerFicheAuteur($pdo, $idAuteur, $valeursAuteur, $ouvragesAuteur, $photo, !empty($_POST['retirer_photo']));
            insertLog('AUTEUR', ($resultat['creation'] ? 'Page auteur créée' : 'Page auteur modifiée') . " par @{$_SESSION['pseudo']} (« {$valeursAuteur['nom_plume']} »)");
            $_SESSION['flash_auteur'] = ['success', $resultat['creation'] ? 'Votre page auteur est créée et publiée.' : 'Votre page auteur est mise à jour.'];
            header('Location: dashboard_auteur.php');
            exit();
        } catch (DomainException $e) {
            $erreurs[] = $e->getMessage();
        }
        if ($photo) {
            effacerPhotoAuteurInutilisee($pdo, $photo);
        }
    }
}

$photoAuteurActuelle = $fiche ? urlPhotoAuteur($fiche['photo']) : null;
$avecMotivation = false;
$csrf = jetonCsrf();
$scriptsPage = ['fiche_auteur.js'];
$titreDocument = 'Ma page auteur';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-proposition page-candidature-auteur">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <a href="dashboard_auteur.php">Espace créateur</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Ma page auteur</span>
    </nav>
    <h1 class="titre-page-compte"><?= $fiche ? 'Modifier ma page auteur' : 'Créer ma page auteur' ?></h1>
    <p class="text-muted mb-4">Vos modifications sont publiées tout de suite. L'équipe peut masquer une page qui ne respecte pas les règles du site.</p>

    <?php if (!$disponible): ?>
        <div class="alert alert-info rounded-3" role="status"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Les pages auteur ouvrent bientôt. Revenez dans quelques jours.</div>
    <?php else: ?>
        <?php if ($fiche && $fiche['est_masquee']): ?>
            <div class="alert alert-warning rounded-3" role="status"><i class="bi bi-eye-slash me-1" aria-hidden="true"></i> Votre page est masquée par l'équipe depuis le <?= date('d/m/Y', strtotime($fiche['date_masquage'])) ?>. Vous pouvez la corriger ; elle reviendra en ligne quand l'équipe la réaffichera.</div>
        <?php elseif ($fiche): ?>
            <p class="mb-3"><a href="auteur.php?a=<?= rawurlencode($fiche['slug']) ?>" class="lien-voir-tout"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Voir ma page publique</a></p>
        <?php endif; ?>

        <?php if ($erreurs): ?>
            <div class="alert alert-danger rounded-3" role="alert">
                <p class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Merci de corriger ces points :</p>
                <ul class="mb-0">
                    <?php foreach ($erreurs as $erreurFiche): ?>
                        <li><?= htmlspecialchars($erreurFiche) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="carte-compte formulaire-proposition fiche-auteur-form">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <?php include 'partie_formulaire_auteur.php'; ?>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="bouton bouton-principal"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= $fiche ? 'Enregistrer' : 'Publier ma page' ?></button>
                <a href="dashboard_auteur.php" class="bouton bouton-discret">Annuler</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
