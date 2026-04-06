<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'header.php';

$stmt = $pdo->query("
    SELECT l.*, u.pseudo 
    FROM logs l 
    LEFT JOIN user u ON l.id_user = u.id 
    ORDER BY l.date_action DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2 class="fw-bold mb-4"><i class="bi bi-journal-text me-2 text-primary"></i>Journal d'activité</h2>
    
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <table class="table align-middle mb-0">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th class="ps-4">Date & Heure</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th class="text-end pe-4">Adresse IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Aucun log enregistré pour le moment.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="ps-4 small"><?= date('d/m/Y H:i', strtotime($log['date_action'])) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($log['pseudo'] ?? 'Système') ?>
                        </span>
                    </td>
                    <td>
                        <span class="fw-bold text-success small"><?= htmlspecialchars($log['action_type']) ?></span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($log['description']) ?></td>
                    <td class="text-end pe-4 small text-muted"><?= htmlspecialchars($log['adresse_ip']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>