<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'upload_lib.php';
require_once 'categories_lib.php';

if (($_SESSION['role'] ?? '') !== 'auteur') {
    header("Location: login.php");
    exit();
}
$idAuteur = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT prenom, nom, pseudo FROM user WHERE id = ?");
$stmt->execute([$idAuteur]);
$moi = $stmt->fetch();
$monNom = trim(($moi['prenom'] ?? '') . ' ' . ($moi['nom'] ?? '')) ?: $moi['pseudo'];

$champsTexte = ['titre' => 255, 'auteur' => 255, 'isbn' => 20, 'editeur' => 150, 'dimensions' => 100, 'poids' => 50, 'reliure' => 50];
$valeurs = ['auteur' => $monNom];
$erreur = '';
$categorieActuelle = null;
$tagsActuels = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_merge(array_keys($champsTexte), ['annee_parution', 'nb_pages', 'prix', 'description']) as $champ) {
        $valeurs[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    $categorieActuelle = (int) ($_POST['id_categorie'] ?? 0) ?: null;
    $tagsActuels = array_map('intval', (array) ($_POST['tags'] ?? []));
    $isbnChiffres = preg_replace('/[^0-9X]/i', '', $valeurs['isbn']);

    if (!verifierCsrf()) {
        $erreur = "Session expirée, merci de réessayer.";
    } elseif ($valeurs['titre'] === '' || $valeurs['auteur'] === '') {
        $erreur = "Le titre et le nom de l'auteur sont obligatoires.";
    } else {
        foreach ($champsTexte as $champ => $longueurMax) {
            if (mb_strlen($valeurs[$champ]) > $longueurMax) {
                $erreur = "Un des champs est trop long (« $champ » : $longueurMax caractères maximum).";
                break;
            }
        }
    }
    if ($erreur === '' && $valeurs['isbn'] !== '' && !in_array(strlen($isbnChiffres), [10, 13], true)) {
        $erreur = "L'ISBN doit contenir 10 ou 13 chiffres.";
    }
    if ($erreur === '' && $valeurs['annee_parution'] !== '' && (!ctype_digit($valeurs['annee_parution']) || (int) $valeurs['annee_parution'] < 1450 || (int) $valeurs['annee_parution'] > (int) date('Y') + 1)) {
        $erreur = "L'année de parution n'est pas valide.";
    }
    if ($erreur === '' && $valeurs['nb_pages'] !== '' && (!ctype_digit($valeurs['nb_pages']) || (int) $valeurs['nb_pages'] > 10000)) {
        $erreur = "Le nombre de pages n'est pas valide.";
    }
    $prix = str_replace(',', '.', $valeurs['prix']);
    if ($erreur === '' && $prix !== '' && (!is_numeric($prix) || (float) $prix < 0 || (float) $prix > 1000)) {
        $erreur = "Le prix indicatif doit être compris entre 0 et 1 000 €.";
    }
    if ($erreur === '' && $categorieActuelle) {
        $stmt = $pdo->prepare("SELECT 1 FROM categorie WHERE id_categorie = ?");
        $stmt->execute([$categorieActuelle]);
        if (!$stmt->fetchColumn()) {
            $erreur = "Choisissez une catégorie de la liste.";
        }
    }
    $couverture = null;
    if ($erreur === '') {
        [$couverture, $erreur] = enregistrerCouverture('couverture');
    }

    if ($erreur === '') {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO livre (titre, auteur, isbn, editeur, annee_parution, dimensions, poids, reliure, nb_pages, description, prix, couverture, is_valide, id_user_auteur)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)")
                ->execute([
                    $valeurs['titre'], $valeurs['auteur'], $valeurs['isbn'] ?: null, $valeurs['editeur'] ?: null,
                    $valeurs['annee_parution'] !== '' ? (int) $valeurs['annee_parution'] : null,
                    $valeurs['dimensions'] ?: null, $valeurs['poids'] ?: null, $valeurs['reliure'] ?: null,
                    $valeurs['nb_pages'] !== '' ? (int) $valeurs['nb_pages'] : null, $valeurs['description'] ?: null,
                    $prix !== '' ? round((float) $prix, 2) : null, $couverture, $idAuteur,
                ]);
            $idLivre = (int) $pdo->lastInsertId();
            enregistrerCategorieEtTags($pdo, $idLivre, ['id_categorie' => $categorieActuelle, 'tags' => $tagsActuels]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        insertLog('AUTEUR', "Proposition du livre #$idLivre « {$valeurs['titre']} » par l'auteur #$idAuteur");
        $_SESSION['flash_auteur'] = ['success', "Merci ! « {$valeurs['titre']} » est envoyé à l'équipe de modération. Il apparaîtra dans le catalogue une fois validé."];
        header("Location: dashboard_auteur.php");
        exit();
    }
}

$v = fn(string $champ) => htmlspecialchars((string) ($valeurs[$champ] ?? ''));
$csrf = jetonCsrf();
include 'header.php';
?>

<div class="container mt-4 mb-5 page-proposition">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <a href="dashboard_auteur.php">Espace créateur</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Proposer un livre</span>
    </nav>
    <h1 class="titre-page-compte">Proposer un livre</h1>

    <?php if ($erreur): ?>
        <div class="alert alert-danger rounded-3" role="alert"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="grille-proposition">
        <form method="POST" enctype="multipart/form-data" class="carte-compte formulaire-proposition">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small" for="titre">Titre de l'œuvre *</label>
                    <input type="text" id="titre" name="titre" class="form-control" maxlength="255" value="<?= $v('titre') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small" for="auteur">Nom d'auteur à afficher *</label>
                    <input type="text" id="auteur" name="auteur" class="form-control" maxlength="255" value="<?= $v('auteur') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small" for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" class="form-control" maxlength="20" value="<?= $v('isbn') ?>" placeholder="978…">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small" for="editeur">Éditeur</label>
                    <input type="text" id="editeur" name="editeur" class="form-control" maxlength="150" value="<?= $v('editeur') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-bold small" for="annee_parution">Année de parution</label>
                    <input type="number" id="annee_parution" name="annee_parution" class="form-control" min="1450" max="<?= date('Y') + 1 ?>" value="<?= $v('annee_parution') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-bold small" for="nb_pages">Nombre de pages</label>
                    <input type="number" id="nb_pages" name="nb_pages" class="form-control" min="1" max="10000" value="<?= $v('nb_pages') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-bold small" for="prix">Prix indicatif (€)</label>
                    <input type="number" id="prix" name="prix" class="form-control" step="0.01" min="0" max="1000" value="<?= $v('prix') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-bold small" for="reliure">Reliure</label>
                    <input type="text" id="reliure" name="reliure" class="form-control" maxlength="50" value="<?= $v('reliure') ?>" placeholder="Broché, relié…">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-bold small" for="dimensions">Dimensions</label>
                    <input type="text" id="dimensions" name="dimensions" class="form-control" maxlength="100" value="<?= $v('dimensions') ?>" placeholder="14 x 21 cm">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-bold small" for="poids">Poids (g)</label>
                    <input type="text" id="poids" name="poids" class="form-control" maxlength="50" value="<?= $v('poids') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small" for="couverture">Couverture (JPG, PNG ou WebP, 2 Mo maximum)</label>
                    <input type="file" id="couverture" name="couverture" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <?php include 'partie_champs_categorie.php'; ?>
                <div class="col-12">
                    <label class="form-label fw-bold small" for="description">Résumé</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Décrivez brièvement le contenu ou l'intrigue de votre livre…"><?= $v('description') ?></textarea>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="bouton bouton-principal"><i class="bi bi-send" aria-hidden="true"></i> Envoyer mon livre</button>
                <a href="dashboard_auteur.php" class="bouton bouton-discret">Annuler</a>
            </div>
        </form>

        <aside class="carte-compte etapes-proposition">
            <h2 class="carte-compte__titre"><?= iconeDessin('plume') ?> Comment ça marche ?</h2>
            <ol>
                <li><strong>Vous remplissez la fiche</strong> de votre livre, avec sa couverture.</li>
                <li><strong>L'équipe la vérifie</strong> et la complète si besoin.</li>
                <li><strong>Une fois validée</strong>, la fiche rejoint le catalogue et vous pouvez l'associer à vos événements.</li>
            </ol>
            <p class="small text-muted mb-0">Vous suivez l'état de vos livres depuis votre espace créateur.</p>
        </aside>
    </div>
</div>

<?php include 'footer.php'; ?>
