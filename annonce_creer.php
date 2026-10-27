<?php
require_once 'config.php';
require_once 'annonces_lib.php';
require_once 'categories_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$raisonInterdiction = raisonInterdictionVente($pdo, $idUser);

$idAnnonce = (int) ($_GET['id'] ?? 0);
$annonce = null;
if ($idAnnonce) {
    $annonce = annonceParId($pdo, $idAnnonce);
    if (!$annonce || (int) $annonce['id_proprietaire'] !== $idUser) {
        header("Location: mes_annonces.php");
        exit();
    }
    if (!in_array($annonce['statut_annonce'], ANNONCE_STATUTS_MODIFIABLES, true)) {
        $_SESSION['flash_annonce'] = ['warning', "Cette annonce ne peut plus être modifiée."];
        header("Location: mes_annonces.php");
        exit();
    }
}
$photosExistantes = $annonce ? photosAnnonce($pdo, $idAnnonce) : [];

$erreurs = [];
$v = [
    'id_livre' => '', 'titre' => '', 'auteur' => '', 'id_categorie' => '', 'etat' => 'bon', 'prix' => '',
    'description' => '', 'mode_remise' => 'main_propre', 'ville_remise' => '',
];
if ($annonce) {
    $v = array_merge($v, [
        'id_livre' => (string) $annonce['id_livre'], 'titre' => $annonce['titre'], 'auteur' => (string) $annonce['auteur'],
        'etat' => $annonce['etat'], 'prix' => number_format((float) $annonce['prix'], 2, ',', ''),
        'description' => (string) $annonce['description'], 'mode_remise' => (string) $annonce['mode_remise'], 'ville_remise' => (string) $annonce['ville_remise'],
    ]);
} else {
    $stmt = $pdo->prepare("SELECT ville FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $v['ville_remise'] = (string) $stmt->fetchColumn();
    if (!empty($_GET['livre'])) {
        $stmt = $pdo->prepare("SELECT id_livre, titre, auteur, id_categorie FROM livre WHERE id_livre = ? AND is_valide = 1");
        $stmt->execute([(int) $_GET['livre']]);
        if ($livreChoisi = $stmt->fetch()) {
            $v = array_merge($v, array_map('strval', $livreChoisi));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saisie = array_map('trim', array_filter($_POST, 'is_string'));
    $v = array_merge($v, array_intersect_key($saisie, $v));
    if ($annonce) {
        $v['id_livre'] = (string) $annonce['id_livre'];
        $v['titre'] = $annonce['titre'];
    }

    if (!verifierCsrf()) { $erreurs[] = "Session expirée, merci de réessayer."; }
    if ($raisonInterdiction !== '') { $erreurs[] = $raisonInterdiction; }
    if (!$annonce && nombreAnnoncesActives($pdo, $idUser) >= ANNONCES_ACTIVES_MAX) {
        $erreurs[] = "Vous avez déjà " . ANNONCES_ACTIVES_MAX . " annonces en cours : retirez-en une avant d'en publier une nouvelle.";
    }
    if ($v['titre'] === '' || mb_strlen($v['titre']) > 255) { $erreurs[] = "Indiquez le titre du livre."; }
    if (mb_strlen($v['auteur']) > 255) { $erreurs[] = "Le nom de l'auteur est trop long."; }
    if (!isset(ETATS_LIVRE[$v['etat']])) { $erreurs[] = "Choisissez l'état du livre."; }
    $prix = lirePrix($v['prix']);
    if ($prix === null || $prix < ANNONCE_PRIX_MIN || $prix > ANNONCE_PRIX_MAX) {
        $erreurs[] = "Le prix doit être compris entre " . formaterPrix(ANNONCE_PRIX_MIN) . " et " . formaterPrix(ANNONCE_PRIX_MAX) . ".";
    }
    if (mb_strlen($v['description']) > ANNONCE_DESCRIPTION_MAX) { $erreurs[] = "La description dépasse " . ANNONCE_DESCRIPTION_MAX . " caractères."; }
    if (!isset(MODES_REMISE[$v['mode_remise']])) { $erreurs[] = "Choisissez comment remettre le livre."; }
    if ($v['mode_remise'] !== 'envoi' && $v['ville_remise'] === '') { $erreurs[] = "Indiquez la ville où vous pouvez remettre le livre en main propre."; }
    if (mb_strlen($v['ville_remise']) > 100) { $erreurs[] = "Le nom de la ville est trop long."; }

    $photosARetirer = array_values(array_intersect(
        array_map('intval', (array) ($_POST['retirer_photos'] ?? [])),
        array_map('intval', array_column($photosExistantes, 'id_photo'))
    ));
    $nouvellesPhotos = fichiersEnvoyes('photos');
    if (count($photosExistantes) - count($photosARetirer) + count($nouvellesPhotos) > ANNONCE_PHOTOS_MAX) {
        $erreurs[] = ANNONCE_PHOTOS_MAX . " photos au maximum par annonce.";
    }

    $idLivre = null;
    if (!$erreurs && $v['id_livre'] !== '') {
        $stmt = $pdo->prepare("SELECT id_livre FROM livre WHERE id_livre = ? AND is_valide = 1");
        $stmt->execute([(int) $v['id_livre']]);
        $idLivre = $stmt->fetchColumn() ?: null;
    }

    $fichiersEnregistres = [];
    if (!$erreurs) {
        [$fichiersEnregistres, $erreurPhotos] = enregistrerPhotosAnnonce($nouvellesPhotos);
        if ($erreurPhotos !== '') {
            $erreurs[] = $erreurPhotos;
        }
    }

    if (!$erreurs) {
        try {
            $pdo->beginTransaction();
            if (!$idLivre) {
                $categorie = ctype_digit($v['id_categorie']) ? (int) $v['id_categorie'] : null;
                $pdo->prepare("INSERT INTO livre (titre, auteur, id_categorie, is_valide) VALUES (?, ?, ?, 0)")
                    ->execute([$v['titre'], $v['auteur'] ?: null, $categorie]);
                $idLivre = (int) $pdo->lastInsertId();
            }
            if ($annonce) {
                $pdo->prepare("UPDATE exemplaire SET etat = ?, prix = ?, description = ?, mode_remise = ?, ville_remise = ?,
                                      statut_annonce = 'en_validation', motif_moderation = NULL, id_moderateur = NULL, date_moderation = NULL
                               WHERE id_exemplaire = ? AND id_proprietaire = ?")
                    ->execute([$v['etat'], $prix, $v['description'] ?: null, $v['mode_remise'], $v['ville_remise'] ?: null, $idAnnonce, $idUser]);
            } else {
                $pdo->prepare("INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite, id_proprietaire, statut_annonce,
                                                       description, mode_remise, ville_remise, date_creation)
                               VALUES (?, ?, ?, 'vente', 1, 1, ?, 'en_validation', ?, ?, ?, NOW())")
                    ->execute([$idLivre, $v['etat'], $prix, $idUser, $v['description'] ?: null, $v['mode_remise'], $v['ville_remise'] ?: null]);
                $idAnnonce = (int) $pdo->lastInsertId();
            }
            $fichiersRetires = [];
            foreach ($photosExistantes as $photo) {
                if (in_array((int) $photo['id_photo'], $photosARetirer, true)) {
                    $pdo->prepare("DELETE FROM exemplaire_photo WHERE id_photo = ? AND id_exemplaire = ?")->execute([$photo['id_photo'], $idAnnonce]);
                    $fichiersRetires[] = $photo['fichier'];
                }
            }
            $ordre = count($photosExistantes);
            foreach ($fichiersEnregistres as $fichier) {
                $pdo->prepare("INSERT INTO exemplaire_photo (id_exemplaire, fichier, ordre) VALUES (?, ?, ?)")->execute([$idAnnonce, $fichier, $ordre++]);
            }
            $pdo->commit();
            supprimerFichiersPhotos($fichiersRetires);
        } catch (PDOException $e) {
            $pdo->rollBack();
            supprimerFichiersPhotos($fichiersEnregistres);
            insertLog('ERREUR', "Annonce non enregistrée : " . $e->getMessage());
            $erreurs[] = "L'annonce n'a pas pu être enregistrée. Merci de réessayer.";
        }
    }

    if (!$erreurs) {
        insertLog('ANNONCE', ($annonce ? "Modification" : "Création") . " de l'annonce #$idAnnonce : " . $v['titre']);
        $_SESSION['flash_annonce'] = ['success', $annonce
            ? "Annonce modifiée : elle repasse en validation auprès de l'équipe."
            : "Annonce envoyée ! L'équipe la vérifie avant de la mettre en ligne, vous recevrez un mail dès qu'elle sera publiée."];
        header("Location: mes_annonces.php");
        exit();
    }
}

$livresCatalogue = $annonce ? [] : $pdo->query("SELECT id_livre, titre, auteur, id_categorie FROM livre WHERE is_valide = 1 ORDER BY titre")->fetchAll();
$arbreCategoriesAnnonce = arbreCategories($pdo);
$nbActives = nombreAnnoncesActives($pdo, $idUser);

$titreDocument = $annonce ? 'Modifier mon annonce' : 'Vendre un livre';
include 'header.php';
?>

<main class="page-formulaire">
    <div class="carte-formulaire carte-formulaire--annonce">
        <div class="carte-formulaire__icone"><?= iconeDessin('etiquette') ?></div>
        <h1><?= $annonce ? 'Modifier mon annonce' : 'Vendre un livre' ?></h1>
        <p class="carte-formulaire__intro">
            <?= $annonce
                ? "Après modification, l'annonce est de nouveau vérifiée par l'équipe avant d'être en ligne."
                : "Votre annonce est vérifiée par l'équipe avant sa mise en ligne. Le paiement se fait entre vous, au moment de l'échange." ?>
        </p>

        <?php if ($raisonInterdiction !== ''): ?>
            <div class="alert alert-warning" role="alert"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= htmlspecialchars($raisonInterdiction) ?></div>
            <p class="carte-formulaire__pied"><a href="vendre.php">Retour à Donner ou revendre</a></p>
        <?php else: ?>

        <?php if ($erreurs): ?>
            <div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3"><?php foreach ($erreurs as $erreur): ?><li><?= htmlspecialchars($erreur) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if ($annonce && $annonce['statut_annonce'] === 'refusee' && $annonce['motif_moderation']): ?>
            <div class="alert alert-warning small" role="status"><strong>Motif du refus :</strong> <?= htmlspecialchars($annonce['motif_moderation']) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="formulaire-annonce">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">

            <fieldset class="formulaire-annonce__bloc">
                <legend>Le livre</legend>
                <?php if ($annonce): ?>
                    <div class="livre-annonce-fixe">
                        <img src="<?= htmlspecialchars(urlImage($annonce['couverture'])) ?>" alt="">
                        <div><strong><?= htmlspecialchars($annonce['titre']) ?></strong><span><?= htmlspecialchars((string) $annonce['auteur']) ?></span></div>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="annonce-titre" class="form-label">Titre *</label>
                            <input type="text" name="titre" id="annonce-titre" class="form-control" list="annonce-livres" maxlength="255" required
                                   value="<?= htmlspecialchars($v['titre']) ?>" placeholder="Commencez à taper : les livres du catalogue sont proposés" autocomplete="off"
                                   aria-describedby="annonce-lien-info">
                            <datalist id="annonce-livres">
                                <?php foreach ($livresCatalogue as $livre): ?>
                                    <option value="<?= htmlspecialchars($livre['titre']) ?>"><?= htmlspecialchars((string) $livre['auteur']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                            <input type="hidden" name="id_livre" id="annonce-id-livre" value="<?= htmlspecialchars($v['id_livre']) ?>">
                            <div class="form-text" id="annonce-lien-info" aria-live="polite">
                                <?= $v['id_livre'] !== '' ? '✓ Livre du catalogue : votre annonce sera rattachée à sa fiche.' : "Livre absent du catalogue ? Saisissez-le : l'équipe créera sa fiche." ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="annonce-auteur" class="form-label">Auteur</label>
                            <input type="text" name="auteur" id="annonce-auteur" class="form-control" maxlength="255" value="<?= htmlspecialchars($v['auteur']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="annonce-categorie" class="form-label">Catégorie</label>
                            <select name="id_categorie" id="annonce-categorie" class="form-select">
                                <option value="">—</option>
                                <?= optionsCategories($arbreCategoriesAnnonce, $v['id_categorie']) ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </fieldset>

            <fieldset class="formulaire-annonce__bloc">
                <legend>Votre exemplaire</legend>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="annonce-etat" class="form-label">État *</label>
                        <select id="annonce-etat" name="etat" class="form-select" required>
                            <?php foreach (ETATS_LIVRE as $cle => $libelle): ?>
                                <option value="<?= $cle ?>" <?= $v['etat'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="annonce-prix" class="form-label">Prix *</label>
                        <div class="input-group">
                            <input type="text" id="annonce-prix" name="prix" class="form-control" inputmode="decimal" required value="<?= htmlspecialchars($v['prix']) ?>"
                                   placeholder="4,50" aria-describedby="annonce-prix-aide">
                            <span class="input-group-text">€</span>
                        </div>
                        <div class="form-text" id="annonce-prix-aide">Entre <?= formaterPrix(ANNONCE_PRIX_MIN) ?> et <?= formaterPrix(ANNONCE_PRIX_MAX) ?>.</div>
                    </div>
                    <div class="col-12">
                        <label for="annonce-description" class="form-label">Description</label>
                        <textarea id="annonce-description" name="description" rows="4" class="form-control" maxlength="<?= ANNONCE_DESCRIPTION_MAX ?>"
                                  placeholder="Édition, annotations, pages cornées, couverture…"><?= htmlspecialchars($v['description']) ?></textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset class="formulaire-annonce__bloc">
                <legend>Photos <span class="formulaire-annonce__facultatif">(facultatif, <?= ANNONCE_PHOTOS_MAX ?> au maximum)</span></legend>
                <?php if ($photosExistantes): ?>
                    <div class="photos-annonce-existantes">
                        <?php foreach ($photosExistantes as $index => $photo): ?>
                            <label class="photo-annonce-existante">
                                <img src="<?= htmlspecialchars(urlPhotoAnnonce($photo['fichier'], null)) ?>" alt="Photo <?= $index + 1 ?> de l'annonce">
                                <span><input type="checkbox" class="form-check-input" name="retirer_photos[]" value="<?= (int) $photo['id_photo'] ?>"> Retirer</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label for="annonce-photos" class="form-label">Ajouter des photos</label>
                <input id="annonce-photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control" aria-describedby="annonce-photos-aide">
                <div class="form-text" id="annonce-photos-aide">JPG, PNG ou WEBP, 2 Mo maximum par photo. Sans photo, la couverture du livre est affichée.</div>
            </fieldset>

            <fieldset class="formulaire-annonce__bloc">
                <legend>La remise</legend>
                <div class="choix-remise" role="radiogroup" aria-label="Mode de remise">
                    <?php foreach (MODES_REMISE as $cle => [$libelle, $icone]): ?>
                        <label class="choix-remise__option">
                            <input type="radio" name="mode_remise" value="<?= $cle ?>" class="btn-check" <?= $v['mode_remise'] === $cle ? 'checked' : '' ?>>
                            <span><i class="bi bi-<?= $icone ?>" aria-hidden="true"></i> <?= $libelle ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <label for="annonce-ville" class="form-label">Ville de remise en main propre</label>
                    <input id="annonce-ville" type="text" name="ville_remise" class="form-control" maxlength="100" value="<?= htmlspecialchars($v['ville_remise']) ?>"
                           placeholder="Ex. : Lyon 3e" autocomplete="address-level2" aria-describedby="annonce-ville-aide">
                    <div class="form-text" id="annonce-ville-aide">Seule la ville est affichée. Vous conviendrez du lieu exact avec l'acheteur.</div>
                </div>
            </fieldset>

            <div class="formulaire-annonce__envoi">
                <button type="submit" class="bouton bouton-principal"><?= $annonce ? 'Enregistrer et renvoyer en validation' : "Envoyer mon annonce à l'équipe" ?></button>
                <?php if (!$annonce): ?><span class="small text-muted"><?= $nbActives ?> / <?= ANNONCES_ACTIVES_MAX ?> annonces en cours</span><?php endif; ?>
            </div>
        </form>
        <p class="carte-formulaire__pied"><a href="mes_annonces.php">Mes annonces</a> · <a href="vendre.php">Donner ou revendre</a></p>
        <?php endif; ?>
    </div>
</main>

<?php if (!$annonce && $raisonInterdiction === ''): ?>
<script>
(function () {
    const livres = <?= json_encode(array_map(fn($l) => ['id' => $l['id_livre'], 't' => $l['titre'], 'a' => $l['auteur'], 'c' => $l['id_categorie']], $livresCatalogue), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    const titre = document.getElementById('annonce-titre');
    const info = document.getElementById('annonce-lien-info');
    titre.addEventListener('input', function () {
        const trouve = livres.find(l => l.t.toLowerCase() === titre.value.trim().toLowerCase());
        document.getElementById('annonce-id-livre').value = trouve ? trouve.id : '';
        info.textContent = trouve ? '✓ Livre du catalogue : votre annonce sera rattachée à sa fiche.' : "Livre absent du catalogue ? Saisissez-le : l'équipe créera sa fiche.";
        if (trouve) {
            document.getElementById('annonce-auteur').value = trouve.a || '';
            if (trouve.c) document.getElementById('annonce-categorie').value = trouve.c;
        }
    });
})();
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
