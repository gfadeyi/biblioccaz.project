<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: inventaire_admin.php"); exit(); }

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = trim($_POST['titre']);
    $auteur = trim($_POST['auteur']);
    $description = trim($_POST['description']);
    $isbn = trim($_POST['isbn']);

    if (empty($titre) || empty($auteur) || empty($description)) {
        $error = "Le titre, l'auteur et la description sont obligatoires.";
    } elseif (!empty($isbn) && strlen($isbn) !== 13 && strlen($isbn) !== 10) {
        $error = "L'ISBN doit comporter soit 10 caractères, soit 13 caractères.";
    } else {
        $isbn_val = !empty($isbn) ? $isbn : null;
        $editeur = !empty($_POST['editeur']) ? $_POST['editeur'] : null;
        $annee = !empty($_POST['annee_parution']) ? $_POST['annee_parution'] : null;
        $pages = !empty($_POST['nb_pages']) ? $_POST['nb_pages'] : null;
        $poids = !empty($_POST['poids']) ? $_POST['poids'] : null;
        $dimensions = !empty($_POST['dimensions']) ? $_POST['dimensions'] : null;
        $reliure = !empty($_POST['reliure']) ? $_POST['reliure'] : 'Broché';

        if (!empty($_FILES['image']['name'])) {
            $nom_image = $_FILES['image']['name'];
            $target = "img/" . basename($nom_image);
            move_uploaded_file($_FILES['image']['tmp_name'], $target);
            
            $sql = "UPDATE livre SET titre=?, auteur=?, description=?, couverture=?, isbn=?, editeur=?, annee_parution=?, nb_pages=?, poids=?, dimensions=?, reliure=? WHERE id_livre=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titre, $auteur, $description, $nom_image, $isbn_val, $editeur, $annee, $pages, $poids, $dimensions, $reliure, $id]);
        } else {
            $sql = "UPDATE livre SET titre=?, auteur=?, description=?, isbn=?, editeur=?, annee_parution=?, nb_pages=?, poids=?, dimensions=?, reliure=? WHERE id_livre=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titre, $auteur, $description, $isbn_val, $editeur, $annee, $pages, $poids, $dimensions, $reliure, $id]);
        }
        $message = "success";
    }
}

$stmt = $pdo->prepare("SELECT * FROM livre WHERE id_livre = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch();

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex align-items-center mb-4">
                <a href="inventaire_admin.php" class="btn btn-outline-secondary me-3 btn-sm rounded-circle shadow-sm"><i class="bi bi-arrow-left"></i></a>
                <h2 class="mb-0 fw-bold">Modifier la fiche livre</h2>
            </div>

            <?php if ($message == "success"): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3">
                    <i class="bi bi-check-circle-fill me-2"></i>Modifications enregistrées.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
                <form action="modifier_livre.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                    <h5 class="fw-bold mb-3 text-success">Informations obligatoires</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Titre</label>
                            <input type="text" name="titre" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['titre']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Auteur</label>
                            <input type="text" name="auteur" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['auteur']) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-end">
                        <div class="col-md-3 text-center">
                            <label class="form-label fw-bold small d-block">Couverture actuelle</label>
                            <img src="img/<?= $livre['couverture'] ?: 'default.png' ?>" class="img-thumbnail" style="height: 140px; width: 100px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold small">Changer l'image</label>
                            <input type="file" name="image" class="form-control bg-light border-0" accept="image/*">
                        </div>
                    </div>

                    <hr class="my-4 text-muted">
                    <h5 class="fw-bold mb-3 text-success">Caractéristiques</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">ISBN (10 ou 13 chiffres)</label>
                            <input type="text" name="isbn" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['isbn'] ?? '') ?>" maxlength="13">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Éditeur</label>
                            <input type="text" name="editeur" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['editeur'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Année de parution</label>
                            <input type="text" name="annee_parution" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['annee_parution'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Pages</label>
                            <input type="number" name="nb_pages" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['nb_pages'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Poids (kg)</label>
                            <input type="number" name="poids" step="0.0001" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['poids'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Dimensions</label>
                            <input type="text" name="dimensions" class="form-control bg-light border-0" value="<?= htmlspecialchars($livre['dimensions'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Reliure</label>
                            <select name="reliure" class="form-select bg-light border-0">
                                <option value="Broché" <?= ($livre['reliure'] == 'Broché') ? 'selected' : '' ?>>Broché</option>
                                <option value="Relié" <?= ($livre['reliure'] == 'Relié') ? 'selected' : '' ?>>Relié</option>
                                <option value="Poche" <?= ($livre['reliure'] == 'Poche') ? 'selected' : '' ?>>Poche</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Résumé / Description</label>
                        <textarea name="description" class="form-control bg-light border-0" rows="6" required><?= htmlspecialchars($livre['description']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm" style="background-color: #274e13; border: none;">
                        METTRE À JOUR
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>