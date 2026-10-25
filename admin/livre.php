<?php
// Ajout (livre.php) ou modification (livre.php?id=12) d'une fiche livre.
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../categories_lib.php';
exigerDroit('catalogue');

$id = (int) ($_GET['id'] ?? 0);
$livre = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM livre WHERE id_livre = ?");
    $stmt->execute([$id]);
    $livre = $stmt->fetch();
    if (!$livre) {
        rediriger('inventaire.php', 'warning', "Ce livre n'existe pas.");
    }
}

$erreur = '';
$valeurs = $livre ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($id ? "livre.php?id=$id" : 'livre.php');

    $champs = ['titre', 'auteur', 'description', 'isbn', 'editeur', 'annee_parution', 'nb_pages', 'poids', 'dimensions', 'reliure'];
    foreach ($champs as $c) {
        $valeurs[$c] = trim($_POST[$c] ?? '');
    }
    $isbn = str_replace(['-', ' '], '', $valeurs['isbn']);

    if ($valeurs['titre'] === '' || $valeurs['auteur'] === '' || $valeurs['description'] === '') {
        $erreur = "Le titre, l'auteur et la description sont obligatoires.";
    } elseif (!$id && $isbn === '') {
        $erreur = "L'ISBN est obligatoire pour un nouveau livre.";
    } elseif ($isbn !== '' && !preg_match('/^(\d{9}[\dXx]|\d{13})$/', $isbn)) {
        $erreur = "L'ISBN doit comporter 10 ou 13 chiffres.";
    } else {
        [$image, $erreur] = enregistrerCouverture('image');
    }

    if ($erreur === '') {
        $donnees = [
            $valeurs['titre'], $valeurs['auteur'], $valeurs['description'],
            $isbn ?: null,
            $valeurs['editeur'] ?: null,
            $valeurs['annee_parution'] !== '' ? (int) $valeurs['annee_parution'] : null,
            $valeurs['nb_pages'] !== '' ? (int) $valeurs['nb_pages'] : null,
            $valeurs['poids'] ?: null,
            $valeurs['dimensions'] ?: null,
            in_array($valeurs['reliure'], ['Broché', 'Relié', 'Poche'], true) ? $valeurs['reliure'] : 'Broché',
        ];

        if ($id) {
            $sql = "UPDATE livre SET titre = ?, auteur = ?, description = ?, isbn = ?, editeur = ?, annee_parution = ?,
                    nb_pages = ?, poids = ?, dimensions = ?, reliure = ?" . ($image ? ", couverture = ?" : "") . " WHERE id_livre = ?";
            if ($image) {
                $donnees[] = $image;
            }
            $donnees[] = $id;
            $pdo->prepare($sql)->execute($donnees);
        } else {
            $donnees[] = $image ?: 'default.png';
            $pdo->prepare("INSERT INTO livre (titre, auteur, description, isbn, editeur, annee_parution, nb_pages, poids, dimensions, reliure, couverture)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute($donnees);
            $id = (int) $pdo->lastInsertId();
        }

        enregistrerCategorieEtTags($pdo, $id, $_POST);
        insertLog('CATALOGUE', ($livre ? "Modification" : "Ajout") . " du livre #$id : " . $valeurs['titre']);

        if ($livre) {
            rediriger("livre.php?id=$id", 'success', 'Modifications enregistrées.');
        }
        rediriger("exemplaires.php?id=$id", 'success', "Livre ajouté. Ajoutez maintenant ses exemplaires en stock.");
    }
}

// Pour partie_champs_categorie.php
$categorieActuelle = $_POST['id_categorie'] ?? ($livre['id_categorie'] ?? null);
$tagsActuels = $_POST['tags'] ?? ($livre ? array_column(tagsDuLivre($pdo, $id), 'id_tag') : []);

$titrePage = $livre ? 'Modifier la fiche' : 'Ajouter un livre';
$pageActive = $livre ? 'inventaire' : 'livre';
include __DIR__ . '/_haut.php';
$v = fn(string $c) => e((string) ($valeurs[$c] ?? ''));
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="inventaire.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Inventaire</a>
    <?php if ($livre): ?>
        <a href="exemplaires.php?id=<?= $id ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-box-seam"></i> Gérer le stock</a>
        <a href="../detail_livre.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Voir sur le site</a>
    <?php endif; ?>
</div>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($erreur) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="carte p-4">
    <?= champCsrf() ?>
    <h2 class="h6 fw-bold text-success mb-3">Informations principales</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" class="form-control" value="<?= $v('titre') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="auteur">Auteur(s) *</label>
            <input type="text" id="auteur" name="auteur" class="form-control" value="<?= $v('auteur') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="isbn">ISBN (10 ou 13 chiffres)<?= $livre ? '' : ' *' ?></label>
            <input type="text" id="isbn" name="isbn" class="form-control" value="<?= $v('isbn') ?>" maxlength="17" <?= $livre ? '' : 'required' ?>>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="image">Couverture (JPG, PNG ou WEBP, 2 Mo max)</label>
            <div class="d-flex gap-3 align-items-center">
                <?php if ($livre): ?><img src="<?= imageLivre($livre['couverture']) ?>" alt="Couverture actuelle" class="couverture-mini"><?php endif; ?>
                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
    </div>

    <h2 class="h6 fw-bold text-success mb-3">Caractéristiques</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label small fw-bold" for="editeur">Éditeur</label>
            <input type="text" id="editeur" name="editeur" class="form-control" value="<?= $v('editeur') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-bold" for="annee_parution">Année de parution</label>
            <input type="number" id="annee_parution" name="annee_parution" class="form-control" value="<?= $v('annee_parution') ?>" min="1400" max="<?= date('Y') + 1 ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-bold" for="nb_pages">Nombre de pages</label>
            <input type="number" id="nb_pages" name="nb_pages" class="form-control" value="<?= $v('nb_pages') ?>" min="1">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-bold" for="poids">Poids (kg)</label>
            <input type="number" id="poids" name="poids" step="0.0001" class="form-control" value="<?= $v('poids') ?>" placeholder="0.8040">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-bold" for="dimensions">Dimensions</label>
            <input type="text" id="dimensions" name="dimensions" class="form-control" value="<?= $v('dimensions') ?>" placeholder="13 x 18 x 1.4">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-bold" for="reliure">Reliure</label>
            <select id="reliure" name="reliure" class="form-select">
                <?php foreach (['Broché', 'Relié', 'Poche'] as $r): ?>
                    <option <?= ($valeurs['reliure'] ?? 'Broché') === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php include __DIR__ . '/../partie_champs_categorie.php'; ?>
    </div>

    <div class="mb-4">
        <label class="form-label small fw-bold" for="description">Résumé / description *</label>
        <textarea id="description" name="description" class="form-control" rows="6" required><?= $v('description') ?></textarea>
    </div>

    <button class="btn btn-vert px-4 py-2 fw-bold"><?= $livre ? 'Enregistrer les modifications' : 'Ajouter au catalogue' ?></button>
</form>

<?php include __DIR__ . '/_bas.php'; ?>
