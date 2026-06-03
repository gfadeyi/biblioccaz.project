<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'header.php';

$query = $pdo->query("
    SELECT l.*, SUM(e.quantite) as nb_stock 
    FROM livre l 
    LEFT JOIN exemplaire e ON l.id_livre = e.id_livre 
    GROUP BY l.id_livre 
    ORDER BY l.id_livre DESC
");
$livres = $query->fetchAll();
?>

<div class="container mt-5">

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            Le livre a été ajouté avec succès au catalogue !
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="admin.php" class="btn btn-outline-secondary me-3 btn-sm rounded-circle shadow-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h2 class="fw-bold mb-0">Gestion Stock & Inventaire</h2>
        </div>
        <a href="ajouter_livre.php" class="btn btn-success shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-2"></i>Nouveau Titre
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
                                <img src="img/<?= rawurlencode($livre['couverture']); ?>" style="width: 45px; height: 60px; object-fit: cover; border-radius: 4px;" class="me-3 shadow-sm">
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
                            <?= (int)$livre['nb_stock']; ?> ex.
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="gerer_exemplaires.php?id=<?= $livre['id_livre']; ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-box-seam me-1"></i> Stock
                            </a>
                            <a href="modifier_livre.php?id=<?= $livre['id_livre']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>