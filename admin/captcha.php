<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

$dossier = __DIR__ . '/../img/captcha/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('captcha.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $f = $_FILES['captcha_file'] ?? null;
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
        $info = ($f && $f['error'] === UPLOAD_ERR_OK) ? @getimagesize($f['tmp_name']) : false;

        if (!$info || !isset($types[$info[2]])) {
            rediriger('captcha.php', 'danger', "Le fichier doit être une image JPG ou PNG.");
        }
        if ($f['size'] > 2 * 1024 * 1024) {
            rediriger('captcha.php', 'danger', "L'image est trop lourde (2 Mo maximum).");
        }
        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($f['name'], PATHINFO_FILENAME)) ?: 'image';
        $nom = substr($base, 0, 40) . '_' . bin2hex(random_bytes(3)) . '.' . $types[$info[2]];
        if (move_uploaded_file($f['tmp_name'], $dossier . $nom)) {
            insertLog('ADMIN', "Image CAPTCHA ajoutée : $nom");
            rediriger('captcha.php', 'success', "Image ajoutée au puzzle.");
        }
        rediriger('captcha.php', 'danger', "Impossible d'enregistrer l'image (droits du dossier img/captcha/ ?).");
    }

    if ($action === 'supprimer') {
        $nom = basename($_POST['fichier'] ?? '');
        if (preg_match('/\.(jpe?g|png)$/i', $nom) && is_file($dossier . $nom)) {
            unlink($dossier . $nom);
            insertLog('ADMIN', "Image CAPTCHA supprimée : $nom");
            rediriger('captcha.php', 'success', "Image supprimée.");
        }
        rediriger('captcha.php', 'warning', "Image introuvable.");
    }
    rediriger('captcha.php');
}

$images = is_dir($dossier) ? array_values(preg_grep('/\.(jpe?g|png)$/i', scandir($dossier))) : [];

$titrePage = 'Images du CAPTCHA';
$pageActive = 'captcha';
include __DIR__ . '/_haut.php';
?>

<form method="POST" enctype="multipart/form-data" class="carte p-4 mb-4" style="max-width: 720px;">
    <?= champCsrf() ?>
    <input type="hidden" name="action" value="ajouter">
    <h2 class="h6 fw-bold">Ajouter un fond de puzzle</h2>
    <div class="d-flex flex-wrap gap-2">
        <input type="file" name="captcha_file" class="form-control" aria-label="Nouvelle image du captcha (JPG ou PNG)" style="max-width: 420px;" accept="image/jpeg,image/png" required>
        <button class="btn btn-vert"><i class="bi bi-upload me-1"></i> Envoyer</button>
    </div>
    <div class="form-text">Idéalement 300 × 150 pixels. JPG ou PNG, 2 Mo maximum.</div>
</form>

<h2 class="h6 fw-bold mb-3">Images en service (<?= count($images) ?>)</h2>
<?php if (!$images): ?>
    <p class="text-muted">Aucune image : le puzzle utilise le logo du site.</p>
<?php endif; ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
    <?php foreach ($images as $img): ?>
        <div class="col">
            <div class="carte overflow-hidden h-100">
                <img src="../img/captcha/<?= rawurlencode($img) ?>" alt="" style="width: 100%; height: 130px; object-fit: cover;">
                <div class="d-flex justify-content-between align-items-center p-2">
                    <small class="text-muted text-truncate"><?= e($img) ?></small>
                    <form method="POST" data-confirmer="Retirer cette image du CAPTCHA ?">
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="fichier" value="<?= e($img) ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
