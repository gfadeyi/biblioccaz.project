<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($_GET['action'] === 'suspendre') {
        $pdo->prepare("UPDATE user SET statut = 'suspendu' WHERE id = ? AND role != 'admin'")->execute([$id]);
    } elseif ($_GET['action'] === 'activer') {
        $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?")->execute([$id]);
    }
    header("Location: gestion_utilisateurs.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $stmt = $pdo->prepare("UPDATE user SET role = ?, solde_points = ? WHERE id = ?");
    $stmt->execute([$_POST['role'], $_POST['solde_points'], $_POST['user_id']]);
    header("Location: gestion_utilisateurs.php");
    exit();
}
include 'header.php';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$stmt = $pdo->prepare("SELECT * FROM user WHERE pseudo LIKE ? OR email LIKE ? ORDER BY id DESC");
$stmt->execute(["%$search%", "%$search%"]);
$users = $stmt->fetchAll();
?>
<div class="container mt-5">
    <h2 class="fw-bold mb-4">Gestion des Membres</h2>
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <table class="table align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-4">Membre</th>
                    <th class="text-center">Points</th>
                    <th class="text-center">Statut</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="ps-4 py-3">
                        <span class="fw-bold"><?= htmlspecialchars($u['pseudo']) ?></span><br>
                        <small class="text-muted"><?= strtoupper($u['role']) ?></small>
                    </td>
                    <td class="text-center">
                        <?php if ($u['role'] !== 'admin'): ?>
                            <form action="gestion_utilisateurs.php" method="POST" class="d-inline-flex">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="role" value="<?= $u['role'] ?>">
                                <input type="number" name="solde_points" value="<?= $u['solde_points'] ?>" class="form-control form-control-sm text-center" style="width: 70px;">
                                <button type="submit" name="update_user" class="btn btn-sm text-success"><i class="bi bi-save"></i></button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge rounded-pill <?= $u['statut'] === 'suspendu' ? 'bg-danger' : 'bg-success' ?>"><?= $u['statut'] ?></span>
                    </td>
                    <td class="text-end pe-4">
                        <?php if ($u['role'] !== 'admin'): ?>
                            <a href="gestion_utilisateurs.php?action=<?= $u['statut'] === 'actif' ? 'suspendre' : 'activer' ?>&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-power"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>