<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
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
                                <img src="img/<?php echo htmlspecialchars($livre['couverture']); ?>" 
                                     style="width: 45px; height: 60px; object-fit: cover; border-radius: 4px;" class="me-3 shadow-sm">
                            <?php else: ?>
                                <div class="bg-light me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 60px; border-radius: 4px;">
                                    <i class="bi bi-book text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($livre['titre']); ?></div>
                                <small class="text-muted">ID: #<?php echo $livre['id_livre']; ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($livre['auteur']); ?></td>
                    <td class="text-center">
                        <span class="badge rounded-pill <?php echo ($livre['nb_stock'] > 0) ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $livre['nb_stock']; ?> ex.
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#ex-<?php echo $livre['id_livre']; ?>">
                            <i class="bi bi-eye"></i> Stock
                        </button>
                        <a href="modifier_livre.php?id=<?php echo $livre['id_livre']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                
                <tr class="collapse" id="ex-<?php echo $livre['id_livre']; ?>" style="background-color: #fcfcfc;">
                    <td colspan="4" class="p-4">
                        <div class="row">
                            <div class="col-md-7">
                                <h6 class="fw-bold mb-3">Exemplaires en vente :</h6>
                                <table class="table table-sm table-borderless">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>ID</th>
                                            <th>État</th>
                                            <th>Prix</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmtEx = $pdo->prepare("SELECT * FROM exemplaire WHERE id_livre = ?");
                                        $stmtEx->execute([$livre['id_livre']]);
                                        $exemplaires = $stmtEx->fetchAll();
                                        foreach ($exemplaires as $ex):
                                        ?>
                                        <tr>
                                            <td>#<?php echo $ex['id_exemplaire']; ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ex['etat']); ?></span></td>
                                            <td class="fw-bold"><?php echo number_format($ex['prix'], 2); ?> €</td>
                                            <td class="text-end">
                                                <a href="inventaire_admin.php?del_ex=<?php echo $ex['id_exemplaire']; ?>" 
                                                   class="text-danger" onclick="return confirm('Supprimer cet exemplaire ?')">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-5 border-start">
                                <h6 class="fw-bold mb-3">Ajouter un exemplaire :</h6>
                                <form action="ajouter_exemplaire_action.php" method="POST" class="row g-2">
                                    <input type="hidden" name="id_livre" value="<?php echo $livre['id_livre']; ?>">
                                    <div class="col-6">
                                        <input type="number" step="0.01" name="prix" class="form-control form-control-sm" placeholder="Prix €" required>
                                    </div>
                                    <div class="col-6">
                                        <select name="etat" class="form-select form-select-sm">
                                            <option value="Neuf">Neuf</option>
                                            <option value="Très bon état">Très bon état</option>
                                            <option value="Bon état">Bon état</option>
                                            <option value="Usé">Usé</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-sm btn-success w-100">Confirmer l'ajout</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>