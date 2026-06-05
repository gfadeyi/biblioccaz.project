<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'auteur') {
    header("Location: login.php");
    exit();
}

$erreur = "";
$succes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $auteur_nom = trim($_POST['auteur'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $editeur = trim($_POST['editeur'] ?? '');
    $annee_parution = trim($_POST['annee_parution'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $poids = trim($_POST['poids'] ?? '');
    $reliure = trim($_POST['reliure'] ?? '');
    $nb_pages = intval($_POST['nb_pages'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $couverture = 'default.jpg';

    if (isset($_FILES['couverture']) && $_FILES['couverture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['couverture']['tmp_name'];
        $fileName = $_FILES['couverture']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $extensionsAutorisees)) {
            $nouveauNom = bin2hex(random_bytes(10)) . '.' . $fileExtension;
            $destination = 'img/' . $nouveauNom;
            if (move_uploaded_file($fileTmpPath, $destination)) {
                $couverture = $nouveauNom;
            }
        }
    }

    if (!$titre || !$auteur_nom) {
        $erreur = "Le titre et le nom de l'auteur sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO livre (titre, auteur, isbn, editeur, annee_parution, dimensions, poids, reliure, nb_pages, description, prix, couverture, is_valide, id_user_auteur) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)");
        if ($stmt->execute([$titre, $auteur_nom, $isbn, $editeur, $annee_parution, $dimensions, $poids, $reliure, $nb_pages, $description, $prix, $couverture, $_SESSION['user_id']])) {
            insertLog('AUTEUR', "Proposition du livre '" . $titre . "' par l'auteur ID " . $_SESSION['user_id']);
            $succes = "Votre œuvre a été soumise avec succès ! Elle est désormais en attente de validation par l'équipe de modération.";
        } else {
            $erreur = "Une erreur technique est survenue lors de l'enregistrement.";
        }
    }
}

include 'header.php';
?>

<div class="container my-5" style="max-width: 750px;">
    <div class="card shadow border-0" style="border-radius: 15px;">
        <div class="card-body p-4">
            <h2 class="fw-bold text-success mb-2">Proposer une œuvre</h2>
            <p class="text-muted small mb-4">Ajoutez une nouvelle fiche d'ouvrage complète pour la soumettre au comité de lecture et de modération.</p>

            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <?php if ($succes): ?>
                <div class="alert alert-success py-3 text-center mb-0" style="border-radius: 10px;">
                    <i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i>
                    <?= htmlspecialchars($succes) ?>
                    <div class="mt-3">
                        <a href="dashboard_auteur.php" class="btn btn-success rounded-pill fw-bold px-3">Mon espace auteur</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Titre de l'œuvre *</label>
                            <input type="text" name="titre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nom d'auteur à afficher *</label>
                            <input type="text" name="auteur" value="<?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Code ISBN</label>
                            <input type="text" name="isbn" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Éditeur</label>
                            <input type="text" name="editeur" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Année de parution</label>
                            <input type="text" name="annee_parution" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Dimensions</label>
                            <input type="text" name="dimensions" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Poids (g)</label>
                            <input type="text" name="poids" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Type de reliure</label>
                            <input type="text" name="reliure" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Nombre de pages</label>
                            <input type="number" name="nb_pages" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Prix indicatif (€)</label>
                            <input type="number" step="0.01" name="prix" value="0.00" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Image de couverture</label>
                            <input type="file" name="couverture" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Synopsis / Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Décrivez brièvement le contenu ou l'intrigue de votre livre..."></textarea>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-pill">SOUMETTRE L'OUVRAGE</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>