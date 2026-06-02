<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $admin_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];

    if ($_GET['action'] === 'valider') {
        $pdo->prepare("UPDATE livre SET is_valide = 1 WHERE id_livre = ?")->execute([$id]);
        $stmtLog = $pdo->prepare("INSERT INTO logs (id_user, action_type, description, adresse_ip) VALUES (?, 'MODERATION', ?, ?)");
        $stmtLog->execute([$admin_id, "Validation du livre ID #$id", $ip]);
    } elseif ($_GET['action'] === 'refuser') {
        $pdo->prepare("DELETE FROM livre WHERE id_livre = ?")->execute([$id]);
        $stmtLog = $pdo->prepare("INSERT INTO logs (id_user, action_type, description, adresse_ip) VALUES (?, 'MODERATION', ?, ?)");
        $stmtLog->execute([$admin_id, "Suppression du livre ID #$id", $ip]);
    }
    header("Location: moderation_catalogue.php");
    exit();
}

include 'header.php';

$livres = $pdo->query("SELECT * FROM livre WHERE is_valide = 0 ORDER BY id_livre DESC")->fetchAll();
?>

<div class="container mt-5">
    <div class="d-flex align-items-center mb-4">
        <a href="admin.php" class="btn btn-outline-secondary me-3 btn-sm rounded-circle shadow-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-warning"></i>Modération Catalogue</h2>
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