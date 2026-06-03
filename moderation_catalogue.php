<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];

    if ($_GET['action'] === 'valider') {
        $pdo->prepare("UPDATE livre SET is_valide = 1 WHERE id_livre = ?")->execute([$id]);
        insertLog('MODERATION', "Validation du livre ID #" . $id);
    } elseif ($_GET['action'] === 'refuser') {
        $pdo->prepare("DELETE FROM livre WHERE id_livre = ?")->execute([$id]);
        insertLog('MODERATION', "Suppression du livre ID #" . $id);
    }
    header("Location: moderation_catalogue.php");
    exit();
}

include 'header.php';

$livres = $pdo->query("SELECT * FROM livre WHERE is_valide = 0 ORDER BY id_livre DESC")->fetchAll();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check me-2 text-warning"></i>Modération Catalogue</h2>
            <p class="text-muted small mb-0">Validation des nouvelles fiches de livres soumis.</p>
        </div>
        <div>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-2"></i>Retour au Dashboard
            </a>
        </div>
    </div>

    <?php if (empty($livres)): ?>
        <div class="alert alert-info border-0 shadow-sm" style="border-radius: 10px;">
            <i class="bi bi-info-circle me-2"></i>Aucune fiche livre en attente de validation.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($livres as $l): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 15px;">
                    <div class="d-flex align-items-center">
                        <img src="img/<?= htmlspecialchars($l['couverture'] ?: 'default.jpg') ?>" class="rounded me-3 shadow-sm" style="width: 80px; height: 110px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($l['titre']) ?></h6>
                            <p class="text-muted small mb-2 text-truncate" style="max-width: 200px;"><?= htmlspecialchars($l['auteur']) ?></p>
                            <div class="d-flex gap-2 mt-2">
                                <a href="moderation_catalogue.php?action=valider&id=<?= $l['id_livre'] ?>" class="btn btn-sm btn-success w-50 fw-bold">Valider</a>
                                <a href="moderation_catalogue.php?action=refuser&id=<?= $l['id_livre'] ?>" class="btn btn-sm btn-outline-danger w-50 fw-bold" onclick="return confirm('Refuser ce livre ?')">Refuser</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>