<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'header.php';

$stmt = $pdo->query("SELECT id, nom, prenom, pseudo, email, role, statut, est_verifie FROM user ORDER BY role ASC, nom ASC");
$allUsers = $stmt->fetchAll();

$admins = [];
$clients = [];

foreach ($allUsers as $u) {
    if ($u['role'] === 'admin') {
        $admins[] = $u;
    } else {
        $clients[] = $u;
    }
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Gestion du Personnel & des Membres</h2>
            <p class="text-muted small mb-0">Gestion des privilèges d'accès et modération du catalogue.</p>
        </div>
        <div>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-2"></i>Retour au Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-uppercase small mb-0"><i class="bi bi-shield-lock me-2"></i>Équipe d'Administration (<?= count($admins) ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Administrateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3 text-secondary">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?></div>
                                    <div class="text-muted small">@<?= htmlspecialchars($admin['pseudo']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($admin['email']) ?></td>
                        <td><span class="badge bg-danger rounded-pill px-3 py-1 fs-7">Administrateur</span></td>
                        <td><span class="badge bg-success rounded-pill px-2 py-1 fs-7">Actif</span></td>
                        <td class="text-end pe-4">
                            <a href="modifier_profil.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-uppercase small text-secondary mb-0"><i class="bi bi-people me-2"></i>Liste des Membres / Clients (<?= count($clients) ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Aucun membre inscrit pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): 
                            $userStatut = strtolower(trim($client['statut'] ?? ''));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-3 text-secondary">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?></div>
                                        <div class="text-muted small">@<?= htmlspecialchars($client['pseudo']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($client['email']) ?></td>
                            <td><span class="badge bg-primary rounded-pill px-3 py-1 fs-7">Client</span></td>
                            <td>
                                <?php if ($userStatut === 'banni'): ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1 fs-7">Banni</span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-2 py-1 fs-7">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="modifier_profil.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                    <?php if ($userStatut === 'banni'): ?>
                                        <a href="action_admin.php?action=deban&id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i></a>
                                    <?php else: ?>
                                        <a href="action_admin.php?action=ban&id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i></a>
                                    <?php endif; ?>
                                </div>
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