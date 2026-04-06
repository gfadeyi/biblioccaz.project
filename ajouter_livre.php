<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

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
        
        $nom_image = "default.png";
        if (!empty($_FILES['image']['name'])) {
            $nom_image = $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], "img/" . $nom_image);
        }

        $sql = "INSERT INTO livre (titre, auteur, description, couverture, isbn, editeur, annee_parution, nb_pages, poids, dimensions, reliure) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titre, $auteur, $description, $nom_image, $isbn_val, $editeur, $annee, $pages, $poids, $dimensions, $reliure]);
        
        insertLog($pdo, 'CATALOGUE', "Ajout du livre : " . $titre);

        header("Location: inventaire_admin.php?msg=success");
        exit();
    }
}

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="mb-4 fw-bold text-center">Ajouter un nouveau livre</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
                <form action="ajouter_livre.php" method="POST" enctype="multipart/form-data">
                    <h5 class="fw-bold mb-3 text-success">Informations obligatoires</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Titre</label>
                            <input type="text" name="titre" class="form-control bg-light border-0" placeholder="Ex: Le Petit Prince" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Auteur</label>
                            <input type="text" name="auteur" class="form-control bg-light border-0" placeholder="Ex: Antoine de Saint-Exupéry" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small">Image de couverture</label>
                            <input type="file" name="image" class="form-control bg-light border-0" accept="image/*">
                        </div>
                    </div>

                    <hr class="my-4 text-muted">
                    <h5 class="fw-bold mb-3 text-success">Caractéristiques</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">ISBN (10 ou 13 chiffres)</label>
                            <input type="text" name="isbn" class="form-control bg-light border-0" placeholder="Ex: 1026824087" maxlength="13">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Éditeur</label>
                            <input type="text" name="editeur" class="form-control bg-light border-0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Année de parution</label>
                            <input type="text" name="annee_parution" class="form-control bg-light border-0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Nb de pages</label>
                            <input type="number" name="nb_pages" class="form-control bg-light border-0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Poids (kg)</label>
                            <input type="number" name="poids" step="0.0001" class="form-control bg-light border-0" placeholder="Ex: 0.8040">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Dimensions</label>
                            <input type="text" name="dimensions" class="form-control bg-light border-0" placeholder="13 x 18 x 1.4">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold small">Reliure</label>
                            <select name="reliure" class="form-select bg-light border-0">
                                <option value="Broché" selected>Broché</option>
                                <option value="Relié">Relié</option>
                                <option value="Poche">Poche</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Résumé / Description</label>
                        <textarea name="description" class="form-control bg-light border-0" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm" style="background-color: #274e13; border: none;">
                        AJOUTER AU CATALOGUE
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>