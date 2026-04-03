<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
include 'header.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM livre WHERE id_livre = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch();

if (isset($_POST['modifier'])) {
    $nomImage = $livre['couverture']; 
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $nomImage = time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
        move_uploaded_file($_FILES['image']['tmp_name'], "img/" . $nomImage);
    }

    $description = $_POST['description'] ?? '';

    $update = $pdo->prepare("UPDATE livre SET titre = ?, auteur = ?, couverture = ?, description = ? WHERE id_livre = ?");
    $update->execute([$_POST['titre'], $_POST['auteur'], $nomImage, $description, $id]);
    
    echo "<script>window.location.href='admin.php';</script>";
    exit();
}
?>

<div class="container mt-5">
    <div class="col-md-6 mx-auto">
        <div class="card p-4 shadow border-0">
            <h4 class="mb-4" style="color: #274e13;">Modifier le livre</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="small fw-bold">Titre</label>
                    <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($livre['titre']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Auteur</label>
                    <input type="text" name="auteur" class="form-control" value="<?= htmlspecialchars($livre['auteur']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Résumé / Description</label>
                    <textarea name="description" class="form-control" rows="8"><?= htmlspecialchars($livre['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Changer l'image</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <button type="submit" name="modifier" class="btn text-white w-100" style="background-color: #274e13;">Enregistrer les modifications</button>
                <a href="admin.php" class="btn btn-link w-100 mt-2 text-decoration-none" style="color: #666;">Annuler</a>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>