<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'gestionnaire')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id_livre'])) {
    $id = intval($_POST['id_livre']);
    $action = $_POST['action'];

    if ($action === 'modifier_description' && isset($_POST['description'])) {
        $desc = trim($_POST['description']);
        $stmt = $pdo->prepare("UPDATE livre SET description = ? WHERE id_livre = ?");
        $stmt->execute([$desc, $id]);
        insertLog('MODERATION', "Modification description du livre ID #" . $id);
        header("Location: moderation_catalogue.php");
        exit();
    }

    if ($action === 'valider') {
        $pdo->prepare("UPDATE livre SET is_valide = 1 WHERE id_livre = ?")->execute([$id]);
        insertLog('MODERATION', "Validation du livre ID #" . $id);
        header("Location: moderation_catalogue.php");
        exit();
    }

    if ($action === 'refuser') {
        if ($_SESSION['role'] !== 'admin') {
            echo "<script>alert('Action interdite : Seul un administrateur peut supprimer ou refuser définitivement un ouvrage.'); window.location.href='moderation_catalogue.php';</script>";
            exit();
        }
        $pdo->prepare("DELETE FROM livre WHERE id_livre = ?")->execute([$id]);
        insertLog('MODERATION', "Suppression du livre ID #" . $id);
        header("Location: moderation_catalogue.php");
        exit();
    }
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
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 15px;">
                    <div class="d-flex align-items-start">
                        <img src="img/<?= htmlspecialchars($l['couverture'] ?: 'default.jpg') ?>" class="rounded me-3 shadow-sm" style="width: 100px; height: 140px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($l['titre']) ?></h6>
                            <p class="text-muted small mb-3">Par : <strong><?= htmlspecialchars($l['auteur']) ?></strong></p>
                            
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                <input type="hidden" name="action" value="modifier_description">
                                <div class="input-group input-group-sm">
                                    <textarea name="description" class="form-control form-control-sm" rows="2" style="font-size:0.8rem;" placeholder="Aucune description fournie..."><?= htmlspecialchars($l['description'] ?? '') ?></textarea>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Enregistrer les modifications"><i class="bi bi-save"></i> Modif.</button>
                                </div>
                            </form>

                            <div class="d-flex gap-2">
                                <form method="POST" class="w-50 m-0">
                                    <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                    <input type="hidden" name="action" value="valider">
                                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold rounded-3">Valider</button>
                                </form>
                                
                                <form method="POST" class="w-50 m-0" onsubmit="return confirm('Refuser et supprimer definitivement ce livre ?');">
                                    <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                    <input type="hidden" name="action" value="refuser">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 fw-bold rounded-3" <?= $_SESSION['role'] !== 'admin' ? 'disabled title="Seul un administrateur possède ce privilège"' : '' ?>>Refuser</button>
                                </form>
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