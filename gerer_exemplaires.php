<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id_livre = $_GET['id'] ?? null;
if (!$id_livre) { header("Location: inventaire_admin.php"); exit(); }

if (isset($_GET['action']) && isset($_GET['ex_id'])) {
    $ex_id = $_GET['ex_id'];
    if ($_GET['action'] == 'plus') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite + 1 WHERE id_exemplaire = ?")->execute([$ex_id]);
        insertLog('STOCK', "Augmentation stock (+1) exemplaire ID " . $ex_id);
    } elseif ($_GET['action'] == 'moins') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite - 1 WHERE id_exemplaire = ?")->execute([$ex_id]);
        $pdo->prepare("DELETE FROM exemplaire WHERE quantite <= 0")->execute();
        insertLog('STOCK', "Diminution/Suppression stock exemplaire ID " . $ex_id);
    }
    header("Location: gerer_exemplaires.php?id=$id_livre");
    exit();
}

if (isset($_POST['add_ex'])) {
    $etat = $_POST['etat'];
    $prix = $_POST['prix'];
    $quantite = $_POST['quantite'] ?? 1;
    $type_offre = 'vente'; 
    $is_disponible = 1;
    $id_user = $_SESSION['user_id'];

    $sql = "INSERT INTO exemplaire (etat, prix, type_offre, is_disponible, id_livre, id_user, quantite) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$etat, $prix, $type_offre, $is_disponible, $id_livre, $id_user, $quantite]);
    insertLog('STOCK', "Ajout de $quantite ex. ($etat) pour le livre ID $id_livre");
    header("Location: gerer_exemplaires.php?id=$id_livre");
    exit();
}

if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ?")->execute([$_GET['del']]);
    insertLog('STOCK', "Suppression offre exemplaire ID " . $_GET['del']);
    header("Location: gerer_exemplaires.php?id=$id_livre");
    exit();
}

$stmtLivre = $pdo->prepare("SELECT titre, couverture FROM livre WHERE id_livre = ?");
$stmtLivre->execute([$id_livre]);
$livre = $stmtLivre->fetch();

$stmtEx = $pdo->prepare("SELECT * FROM exemplaire WHERE id_livre = ? AND quantite > 0 ORDER BY id_exemplaire DESC");
$stmtEx->execute([$id_livre]);
$exemplaires = $stmtEx->fetchAll();

include 'header.php';
?>

<div class="container mt-5">
    <div class="d-flex align-items-center mb-4">
        <a href="inventaire_admin.php" class="btn btn-outline-secondary me-3 btn-sm rounded-circle shadow-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="fw-bold mb-0">Gestion des exemplaires</h2>
            <p class="text-muted mb-0">Livre : <?= htmlspecialchars($livre['titre']) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
                <img src="img/<?= htmlspecialchars($livre['couverture'] ?: 'default.png') ?>" class="rounded shadow-sm mb-3 mx-auto d-block" style="width: 120px; height: 170px; object-fit: cover;">
                <form action="gerer_exemplaires.php?id=<?= $id_livre ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ÉTAT DU LIVRE</label>
                        <select name="etat" class="form-select border-0 bg-light">
                            <option value="neuf">Neuf</option>
                            <option value="tres bon">Très bon état</option>
                            <option value="bon" selected>Bon état</option>
                            <option value="use">Usé</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">PRIX DE VENTE (€)</label>
                        <input type="number" name="prix" step="0.01" class="form-control border-0 bg-light" placeholder="Ex: 12.50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">QUANTITÉ INITIALE</label>
                        <input type="number" name="quantite" class="form-control border-0 bg-light" value="1" min="1">
                    </div>
                    <button type="submit" name="add_ex" class="btn btn-success w-100 rounded-pill fw-bold py-2 mt-2" style="background-color: #274e13; border: none;">
                        + AJOUTER AU STOCK
                    </button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">État</th>
                            <th>Prix</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exemplaires)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Aucun exemplaire en stock.</td></tr>
                        <?php else: ?>
                            <?php foreach ($exemplaires as $ex): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-white text-dark border rounded-pill px-3">
                                        <?= htmlspecialchars(ucfirst($ex['etat'])) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success"><?= number_format($ex['prix'], 2) ?> €</td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center border rounded-pill bg-light p-1">
                                        <a href="gerer_exemplaires.php?id=<?= $id_livre ?>&ex_id=<?= $ex['id_exemplaire'] ?>&action=moins" class="btn btn-sm btn-white rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width:24px; height:24px; text-decoration: none; color: black;">-</a>
                                        <span class="mx-3 fw-bold"><?= $ex['quantite'] ?></span>
                                        <a href="gerer_exemplaires.php?id=<?= $id_livre ?>&ex_id=<?= $ex['id_exemplaire'] ?>&action=plus" class="btn btn-sm btn-white rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width:24px; height:24px; text-decoration: none; color: black;">+</a>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="gerer_exemplaires.php?id=<?= $id_livre ?>&del=<?= $ex['id_exemplaire'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Supprimer cette offre ?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>