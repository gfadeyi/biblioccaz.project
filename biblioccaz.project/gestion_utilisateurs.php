<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM user ORDER BY role ASC, pseudo ASC");
$users = $stmt->fetchAll();

include 'header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Gestion des Membres</h2>
        <span class="badge bg-success rounded-pill"><?= count($users) ?> Utilisateurs</span>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th class="text-center">Statut</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-person text-secondary"></i>
                            </div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                                <small class="text-muted">@<?= htmlspecialchars($user['pseudo']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Administrateur</span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Client</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php 
                        $status = $user['statut'] ?? 'actif';
                        if ($status === 'actif'): ?>
                            <span class="badge rounded-pill bg-success px-3">Actif</span>
                        <?php elseif ($status === 'suspendu'): ?>
                            <span class="badge rounded-pill bg-warning text-dark px-3">Suspendu</span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-danger px-3">Banni</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="modifier_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <?php if ($status !== 'banni'): ?>
                                    <a href="action_admin.php?id=<?= $user['id'] ?>&action=bannir" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bannir cet utilisateur ?');">
                                        <i class="bi bi-slash-circle"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="action_admin.php?id=<?= $user['id'] ?>&action=activer" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>