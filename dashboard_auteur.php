<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'auteur') {
    header("Location: login.php");
    exit();
}

include 'header.php';

$stmt = $pdo->prepare("SELECT * FROM livre WHERE id_user_auteur = ? ORDER BY id_livre DESC");
$stmt->execute([$_SESSION['user_id']]);
$mesLivres = $stmt->fetchAll();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Mon Espace Auteur</h2>
            <p class="text-muted small mb-0">Suivez l'état de validation de vos œuvres soumises au catalogue.</p>
        </div>
        <div>
            <a href="proposer_ouvrage.php" class="btn btn-success rounded-pill px-4 fw-bold">
                Soumettre une œuvre
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold text-uppercase small text-success mb-0"><i class="bi bi-book me-2"></i>Mes propositions (<?= count($mesLivres) ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Couverture</th>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>Statut de validation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mesLivres)): ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">Vous n'avez pas encore proposé d'œuvre au catalogue.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mesLivres as $l): ?>
                        <tr>
                            <td class="ps-4">
                                <img src="img/<?= htmlspecialchars($l['couverture'] ?: 'default.jpg') ?>" class="rounded shadow-sm" style="width: 50px; height: 70px; object-fit: cover;">
                            </td>
                            <td><span class="fw-bold text-dark"><?= htmlspecialchars($l['titre']) ?></span></td>
                            <td class="text-muted small"><?= htmlspecialchars($l['auteur']) ?></td>
                            <td>
                                <?php if ($l['is_valide'] == 1): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-1.5 fs-7"><i class="bi bi-check-circle me-1"></i> Validé & Publié</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-success border border-success rounded-pill px-3 py-1.5 fs-7"><i class="bi bi-clock me-1"></i> En attente de modération</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>