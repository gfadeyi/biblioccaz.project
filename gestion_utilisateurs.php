<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_candidature'], $_POST['user_id'])) {
    $userId = $_POST['user_id'];
    if ($_POST['action_candidature'] === 'accepter') {
        $stmt = $pdo->prepare("UPDATE user SET role = 'moderateur', statut = 'valide_moderateur' WHERE id = ?");
        $stmt->execute([$userId]);
        insertLog('ADMIN', "Candidature acceptée : Utilisateur ID " . $userId . " promu Modérateur (en attente de première connexion)");
    } elseif ($_POST['action_candidature'] === 'refus_temporaire') {
        $stmt = $pdo->prepare("UPDATE user SET statut = 'refuse_temporaire' WHERE id = ?");
        $stmt->execute([$userId]);
        insertLog('ADMIN', "Candidature refusée temporairement : Utilisateur ID " . $userId);
    } elseif ($_POST['action_candidature'] === 'refus_definitif') {
        $stmt = $pdo->prepare("UPDATE user SET statut = 'refuse_definitif' WHERE id = ?");
        $stmt->execute([$userId]);
        insertLog('ADMIN', "Candidature refusée définitivement : Utilisateur ID " . $userId);
    }
    header("Location: gestion_utilisateurs.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'changer_role' && isset($_POST['user_id'], $_POST['nouveau_role'])) {
        $stmt = $pdo->prepare("UPDATE user SET role = ? WHERE id = ?");
        $stmt->execute([$_POST['nouveau_role'], $_POST['user_id']]);
        insertLog('ADMIN', "Changement de rôle de l'utilisateur ID " . $_POST['user_id'] . " vers " . $_POST['nouveau_role']);
    }

    if ($_POST['action'] === 'supprimer' && isset($_POST['user_id'])) {
        $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
        $stmt->execute([$userId]);
        insertLog('ADMIN', "Suppression définitive de l'utilisateur ID " . $_POST['user_id']);
    }
    header("Location: gestion_utilisateurs.php");
    exit();
}

include 'header.php';

$stmt = $pdo->query("SELECT id, nom, prenom, pseudo, email, role, statut, est_verifie FROM user ORDER BY role ASC, nom ASC");
$allUsers = $stmt->fetchAll();

$equipe = [];
$membres = [];
$candidatures = [];

foreach ($allUsers as $u) {
    $userRole = isset($u['role']) ? strtolower(trim($u['role'])) : '';
    $userStatut = isset($u['statut']) ? strtolower(trim($u['statut'])) : '';

    if ($userStatut === 'en_attente_moderateur') {
        $candidatures[] = $u;
    } elseif ($userRole === 'admin' || $userRole === 'moderateur' || $userRole === 'gestionnaire') {
        $equipe[] = $u;
    } else {
        $membres[] = $u;
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

    <?php if (!empty($candidatures)): ?>
    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden; border: 2px solid #ffc107 !important;">
        <div class="card-header bg-warning text-dark py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-uppercase small mb-0"><i class="bi bi-file-earmark-person me-2"></i>Candidatures Modérateur en attente (<?= count($candidatures) ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Candidat</th>
                        <th>Email</th>
                        <th class="text-end pe-4">Décision Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $cAnd): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($cAnd['prenom'] . ' ' . $cAnd['nom']) ?></div>
                            <div class="text-muted small">@<?= htmlspecialchars($cAnd['pseudo']) ?></div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($cAnd['email']) ?></td>
                        <td class="text-end pe-4">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $cAnd['id'] ?>">
                                <input type="hidden" name="action_candidature" value="accepter">
                                <button type="submit" class="btn btn-sm btn-success fw-bold me-1 rounded-pill px-3"><i class="bi bi-check-lg"></i> Accepter</button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Refuser temporairement cette candidature ? L\'utilisateur pourra postuler à nouveau.');">
                                <input type="hidden" name="user_id" value="<?= $cAnd['id'] ?>">
                                <input type="hidden" name="action_candidature" value="refus_temporaire">
                                <button type="submit" class="btn btn-sm btn-outline-warning fw-bold me-1 rounded-pill px-3"><i class="bi bi-exclamation-triangle"></i> Rejeter temporairement</button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Refuser définitivement cette candidature ?');">
                                <input type="hidden" name="user_id" value="<?= $cAnd['id'] ?>">
                                <input type="hidden" name="action_candidature" value="refus_definitif">
                                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3"><i class="bi bi-slash-circle"></i> Rejeter définitivement</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-uppercase small mb-0"><i class="bi bi-shield-lock me-2"></i>Équipe d'Administration & Modération (<?= count($equipe) ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Personnel</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipe as $staff): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3 text-secondary">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($staff['prenom'] . ' ' . $staff['nom']) ?></div>
                                    <div class="text-muted small">@<?= htmlspecialchars($staff['pseudo']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($staff['email']) ?></td>
                        <td>
                            <?php 
                            $checkRole = strtolower(trim($staff['role']));
                            if ($checkRole === 'admin'): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-1 fs-7">Administrateur</span>
                            <?php elseif ($checkRole === 'moderateur' || $checkRole === 'gestionnaire'): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fs-7">Modérateur</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $checkStatut = strtolower(trim($staff['statut']));
                            if ($checkStatut === 'valide_moderateur'): ?>
                                <span class="badge bg-light text-success border border-success rounded-pill px-2 py-1 fs-7">Accepté (En attente co)</span>
                            <?php else: ?>
                                <span class="badge bg-success rounded-pill px-2 py-1 fs-7">Actif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" class="d-inline gap-2">
                                <input type="hidden" name="user_id" value="<?= $staff['id'] ?>">
                                <input type="hidden" name="action" value="changer_role">
                                <select name="nouveau_role" class="form-select form-select-sm d-inline-block w-auto me-2" onchange="this.form.submit()">
                                    <option value="admin" <?= strtolower(trim($staff['role'])) === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="moderateur" <?= (strtolower(trim($staff['role'])) === 'moderateur' || strtolower(trim($staff['role'])) === 'gestionnaire') ? 'selected' : '' ?>>Modérateur</option>
                                    <option value="auteur" <?= strtolower(trim($staff['role'])) === 'auteur' ? 'selected' : '' ?>>Auteur</option>
                                    <option value="client" <?= strtolower(trim($staff['role'])) === 'client' ? 'selected' : '' ?>>Client</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-uppercase small text-secondary mb-0"><i class="bi bi-people me-2"></i>Liste des Membres / Clients & Auteurs (<?= count($membres) ?>)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle Actuel</th>
                        <th>Attribuer Rôle</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($membres)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Aucun membre inscrit pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($membres as $client): 
                            $rawStatut = isset($client['statut']) ? strtolower(trim($client['statut'])) : '';
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
                            <td>
                                <?php if (strtolower(trim($client['role'])) === 'auteur'): ?>
                                    <span class="badge bg-info text-dark rounded-pill px-3 py-1 fs-7">Auteur</span>
                                <?php else: ?>
                                    <span class="badge bg-primary rounded-pill px-3 py-1 fs-7">Client</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="user_id" value="<?= $client['id'] ?>">
                                    <input type="hidden" name="action" value="changer_role">
                                    <select name="nouveau_role" class="form-select form-select-sm">
                                        <option value="client" <?= strtolower(trim($client['role'])) === 'client' ? 'selected' : '' ?>>Client</option>
                                        <option value="auteur" <?= strtolower(trim($client['role'])) === 'auteur' ? 'selected' : '' ?>>Auteur</option>
                                        <option value="moderateur">Modérateur</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Changer</button>
                                </form>
                            </td>
                            <td>
                                <?php if ($rawStatut === 'banni' || $rawStatut === 'refuse_definitif'): ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1 fs-7">Banni</span>
                                <?php elseif ($rawStatut === 'refuse_temporaire'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-7">Refusé Temp.</span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-2 py-1 fs-7">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="modifier_profil.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                    <?php if ($rawStatut === 'banni' || $rawStatut === 'refuse_definitif'): ?>
                                        <a href="action_admin.php?action=deban&id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i></a>
                                    <?php else: ?>
                                        <a href="action_admin.php?action=ban&id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i></a>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Supprimer définitivement cet utilisateur ? Cette action est irréversible.');" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $client['id'] ?>">
                                        <input type="hidden" name="action" value="supprimer">
                                        <button type="submit" class="btn btn-sm btn-danger border-start-0"><i class="bi bi-trash"></i></button>
                                    </form>
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