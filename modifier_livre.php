<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
$query = $pdo->prepare("SELECT * FROM livre WHERE id_livre = :id");
$query->execute(['id' => $id]);
$livre = $query->fetch();

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $description = $_POST['description'];
    $nom_image = $livre['couverture'];

    if (!empty($_FILES['image']['name'])) {
        $nom_image = $_FILES['image']['name'];
        $target = "img/" . basename($nom_image);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $error_msg = "Erreur de transfert de l'image.";
        }
    }

    if (empty($error_msg)) {
        try {
            $sql = "UPDATE livre SET titre = :titre, auteur = :auteur, description = :description, couverture = :couverture WHERE id_livre = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titre' => $titre,
                'auteur' => $auteur,
                'description' => $description,
                'couverture' => $nom_image,
                'id' => $id
            ]);
            header("Location: inventaire_admin.php");
            exit();
        } catch (Exception $e) {
            $error_msg = "Erreur SQL : " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center mb-4">
                <a href="inventaire_admin.php" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
                <h2 class="mb-0">Modifier le livre</h2>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger border-0 shadow-sm"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4">
                <form action="modifier_livre.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titre</label>
                        <input type="text" name="titre" class="form-control" value="<?php echo htmlspecialchars($livre['titre']); ?>">
                    </div>
                    
                    <div class="text-center mb-3">
                        <?php if(!empty($livre['couverture'])): ?>
                            <img src="img/<?php echo htmlspecialchars($livre['couverture']); ?>" width="120" class="img-thumbnail border-0 shadow-sm">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Changer l'image (Optionnel)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Auteur</label>
                        <input type="text" name="auteur" class="form-control" value="<?php echo htmlspecialchars($livre['auteur']); ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($livre['description']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2" style="background-color: #274e13; border: none;">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>