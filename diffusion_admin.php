<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] === 'unsubscribe') {
    $id = $_GET['id'];

    $stmtEmail = $pdo->prepare("SELECT email FROM user WHERE id = ?");
    $stmtEmail->execute([$id]);
    $user = $stmtEmail->fetch();

    if ($user) {
        $email = $user['email'];

        $stmt = $pdo->prepare("UPDATE user SET is_newsletter = 0 WHERE id = ?");
        $stmt->execute([$id]);

        $log_stmt = $pdo->prepare("INSERT INTO logs (action, details, date_action) VALUES ('Désinscription Newsletter', :details, NOW())");
        $log_stmt->execute(['details' => "L'admin a désabonné l'adresse : " . $email]);
    }

    header("Location: diffusion_admin.php");
    exit();
}

include 'header.php';

$stmt = $pdo->query("SELECT id, pseudo, email, is_newsletter, date_newsletter FROM user WHERE date_newsletter IS NOT NULL ORDER BY date_newsletter DESC");
$subscribers = $stmt->fetchAll();

$active_emails = [];
foreach ($subscribers as $s) {
    if ($s['is_newsletter'] == 1) {
        $active_emails[] = $s['email'];
    }
}
$copy_list = implode('; ', $active_emails);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-megaphone me-2 text-success"></i>Diffusion & Newsletter</h2>
            <p class="text-muted small mb-0">Historique complet des abonnements et désinscriptions.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="badge bg-primary px-4 py-2 rounded-pill fs-6 shadow-sm">
                <?= count($subscribers) ?> au total
            </div>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-2"></i>Retour au Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 15px; background-color: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-uppercase small text-muted">Extraire uniquement les abonnés actifs</h6>
            <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" onclick="navigator.clipboard.writeText('<?= $copy_list ?>')">
                <i class="bi bi-copy me-2"></i>
            </button>
        </div>
        <textarea class="form-control border-0 bg-white small shadow-inner" rows="2" readonly style="resize: none;"><?= $copy_list ?: 'Aucun abonné actif.' ?></textarea>
    </div>

    <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
        <table class="table align-middle mb-0">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th class="ps-4">Membre</th>
                    <th>Email</th>
                    <th class="text-center">Statut</th>
                    <th class="text-center">Date d'action</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Aucun historique trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($subscribers as $s): ?>
                <tr>
                    <td class="ps-4 py-3">
                        <span class="fw-bold"><?= htmlspecialchars($s['pseudo']) ?></span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($s['email']) ?></td>
                    <td class="text-center">
                        <?php if ($s['is_newsletter'] == 1): ?>
                            <span class="badge bg-light-success text-success border border-success rounded-pill px-3 py-1 small">Inscrit</span>
                        <?php else: ?>
                            <span class="badge bg-light-danger text-danger border border-danger rounded-pill px-3 py-1 small">Désinscrit</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center small">
                        <?= $s['date_newsletter'] ? date('d/m/Y à H:i', strtotime($s['date_newsletter'])) : '-' ?>
                    </td>
                    <td class="text-end pe-4">
                        <?php if ($s['is_newsletter'] == 1): ?>
                            <a href="diffusion_admin.php?action=unsubscribe&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger border-0" title="Désabonner de force">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card shadow-sm border-0 p-4 mb-5" style="border-radius: 15px; background-color: #f8f9fa;">
        <h5 class="fw-bold text-dark mb-2"><i class="bi bi-send me-2 text-primary"></i>Campagne d'envoi</h5>
        <p class="text-muted small">Cette action enverra le mail uniquement aux utilisateurs marqués comme "Inscrit".</p>
        <form method="POST" action="lancer_envoi_newsletter.php">
            <button type="submit" class="btn btn-success rounded-pill px-4 py-2 shadow-sm fw-bold">
                <i class="bi bi-rocket-takeoff me-2"></i> Lancer l'envoi de la Newsletter
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>