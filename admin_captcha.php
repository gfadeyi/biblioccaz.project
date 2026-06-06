<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }


$dir = 'img/captcha/';
$message = "";
$messageClass = "";


if (isset($_FILES['captcha_file'])) {

    if (!is_dir($dir)) { mkdir($dir, 0755, true); }

    $targetFile = $dir . basename($_FILES['captcha_file']['name']);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    
    $check = getimagesize($_FILES['captcha_file']['tmp_name']);
    if ($check !== false) {
        if ($_FILES['captcha_file']['size'] > 2000000) { 
            $message = "Erreur : L'image est trop lourde (maximum 2 Mo).";
            $messageClass = "alert-danger";
        } elseif ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png") {
            $message = "Erreur : Seuls les formats JPG, JPEG et PNG sont autorisés.";
            $messageClass = "alert-danger";
        } else {
            if (move_uploaded_file($_FILES['captcha_file']['tmp_name'], $targetFile)) {
                $message = "Succès : L'image a été ajoutée avec succès !";
                $messageClass = "alert-success";
            } else {
                $message = "Erreur lors de l'envoi du fichier.";
                $messageClass = "alert-danger";
            }
        }
    } else {
        $message = "Le fichier envoyé n'est pas une image valide.";
        $messageClass = "alert-danger";
    }
}


if (isset($_GET['delete'])) {
    $fileToDelete = $_GET['delete'];
  
    if (strpos($fileToDelete, $dir) === 0 && file_exists($fileToDelete)) {
        unlink($fileToDelete);
        $message = "L'image a bien été supprimée du CAPTCHA.";
        $messageClass = "alert-success";
    }
}


$images = [];
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $images[] = $dir . $file;
        }
    }
}

include 'header.php'; 
?>

<div class="container mt-5">
    <h2 class="fw-bold mb-4">️ Gestion des images du CAPTCHA </h2>

    <?php if ($message): ?>
        <div class="alert <?= $messageClass ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Ajouter un nouveau fond de puzzle</h5>
        </div>
        <div class="card-body">
            <form action="admin_captcha.php" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <input type="file" name="captcha_file" class="form-control" required>
                    <div class="form-text">Dimensions idéales : 300x150 pixels. Formats acceptés : .jpg, .jpeg, .png</div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">📁 Envoyer l'image</button>
                </div>
            </form>
        </div>
    </div>

    <h4 class="fw-bold mb-3">Images actuellement en service (<?= count($images) ?>)</h4>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if (empty($images)): ?>
            <div class="col-12 text-center text-muted py-5">
                <p>Aucune image personnalisée dans le dossier <code>img/captcha/</code>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($images as $img): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm overflow-hidden">
                        <img src="<?= $img ?>?t=<?= time() ?>" class="card-img-top" style="height: 140px; object-fit: cover;" alt="Fond Captcha">
                        <div class="card-body p-2 bg-light d-flex justify-content-between align-items-center">
                            <small class="text-muted text-truncate" style="max-width: 130px;"><?= basename($img) ?></small>
                            <a href="admin_captcha.php?delete=<?= urlencode($img) ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Voulez-vous vraiment supprimer cette image du CAPTCHA ?');">
                                🗑️ Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>