<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $description = $_POST['description'];
    $prix = $_POST['prix'];
    $etat = $_POST['etat'];
    
    $nom_image = $_FILES['image']['name'];
    $target = "img/" . basename($nom_image);

    try {
        $pdo->beginTransaction();

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            
            $stmtLivre = $pdo->prepare("INSERT INTO livre (titre, auteur, description, couverture) VALUES (?, ?, ?, ?)");
            $stmtLivre->execute([$titre, $auteur, $description, $nom_image]);

            $id_livre = $pdo->lastInsertId();

            $stmtEx = $pdo->prepare("INSERT INTO exemplaire (id_livre, prix, etat) VALUES (?, ?, ?)");
            $stmtEx->execute([$id_livre, $prix, $etat]);

            $pdo->commit();
            $message = "success";
        } else {
            $message = "upload_error";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "error";
    }
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-4">
                <a href="admin.php" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
                <h2 class="mb-0 fw-bold">Ajouter un nouveau titre</h2>
            </div>

            <?php if ($message == "success"): ?>
                <div class="alert alert-success border-0 shadow-sm">Livre et premier exemplaire ajoutés !</div>
            <?php elseif ($message == "upload_error"): ?>
                <div class="alert alert-danger border-0 shadow-sm">Erreur d'image.</div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4">
                <form action="ajouter_livre.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titre</label>
                        <input type="text" name="titre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Auteur</label>
                        <input type="text" name="auteur" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Prix (€)</label>
                            <input type="number" step="0.01" name="prix" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">État</label>
                            <select name="etat" class="form-select" required>
                                <option value="Neuf">Neuf</option>
                                <option value="Très bon état">Très bon état</option>
                                <option value="Bon état">Bon état</option>
                                <option value="Usé">Usé</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Couverture</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold" style="background-color: #274e13; border: none;">ENREGISTRER</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>