<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['del_ex'])) {
    $stmt = $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ?");
    $stmt->execute([$_GET['del_ex']]);
    header("Location: inventaire_admin.php");
    exit();
}

include 'header.php';

$query = $pdo->query("
    SELECT l.*, COUNT(e.id_exemplaire) as nb_stock 
    FROM livre l 
    LEFT JOIN exemplaire e ON l.id_livre = e.id_livre 
    GROUP BY l.id_livre 
    ORDER BY l.id_livre DESC
");
$livres = $query->fetchAll();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Gestion Stock & Inventaire</h2>
        <a href="ajouter_livre.php" class="btn btn-success shadow-sm">
            <i class="bi bi-plus-circle"></i> Nouveau Titre
        </a>
    </div>
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Livre</th>
                    <th>Auteur</th>
                    <th class="text-center">En Stock</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($livres as $livre): ?>
                <tr style="border-bottom: 2px solid #f8f9fa;">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center">
                            <?php if (!empty($livre['couverture'])): ?>
                                <img src="img/<?= htmlspecialchars($livre['couverture']); ?>" style="width: 45px; height: 60px; object-fit: cover; border-radius: 4px;" class="me-3 shadow-sm">
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($livre['titre']); ?></div>
                                <small class="text-muted">ID: #<?= $livre['id_livre']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($livre['auteur']); ?></td>
                    <td class="text-center">
                        <span class="badge rounded-pill <?= ($livre['nb_stock'] > 0) ? 'bg-success' : 'bg-danger'; ?>">
                            <?= $livre['nb_stock']; ?> ex.
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="modifier_livre.php?id=<?= $livre['id_livre']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>