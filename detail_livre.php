<?php
require_once 'config.php';
include 'header.php';

$id = $_GET['id'] ?? null;
$offre_index = $_GET['offre'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM livre WHERE id_livre = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch();

if (!$livre) {
    echo "<div class='container mt-5'>Livre introuvable.</div>";
    include 'footer.php'; exit;
}

$stmtEx = $pdo->prepare("SELECT id_exemplaire, etat, prix, quantite FROM exemplaire WHERE id_livre = ? ORDER BY prix ASC");
$stmtEx->execute([$id]);
$offres = $stmtEx->fetchAll();

$offre_selectionnee = $offres[$offre_index] ?? null;

function formaterEtat($etat) {
    $e = strtolower($etat);
    if ($e == 'tres bon' || $e == 'très bon') return 'Très bon';
    if ($e == 'bon') return 'Bon';
    if ($e == 'neuf') return 'Neuf';
    if ($e == 'use' || $e == 'usé') return 'Usé';
    return ucfirst($e);
}
?>

<style>
    .btn-custom-green { background-color: #274e13; color: white; font-weight: bold; border-radius: 50px; padding: 12px; border: none; }
    .btn-custom-green:hover { background-color: #1a330d; color: white; }
    .btn-admin-green { background-color: #274e13; color: white; border-radius: 50px; font-size: 0.85rem; padding: 10px; }
    .btn-state-active { background-color: #274e13 !important; color: white !important; border-radius: 50px; padding: 8px 20px; }
    .btn-state-inactive { border: 1px solid #6c757d; color: #6c757d; border-radius: 50px; padding: 8px 20px; }
</style>

<div class="container mt-5 mb-5">
    <div class="row g-5">
        <div class="col-md-4 text-center">
            <img src="img/<?= htmlspecialchars($livre['couverture'] ?? 'default.jpg') ?>" class="img-fluid rounded shadow-sm" style="max-height: 450px;">
        </div>

        <div class="col-md-5">
            <h1 class="fw-bold"><?= htmlspecialchars($livre['titre']) ?></h1>
            <p class="text-muted h5 mb-4">Par <?= htmlspecialchars($livre['auteur']) ?></p>
            
            <div class="mb-4">
                <h6 class="fw-bold mb-3">État des exemplaires</h6>
                <div class="d-flex gap-2 overflow-auto pb-2">
                    <?php foreach ($offres as $index => $offre): 
                        $is_active = ($index == $offre_index);
                        $etat_label = formaterEtat($offre['etat']);
                    ?>
                        <div class="<?= $is_active ? 'btn-state-active' : 'btn-state-inactive' ?> d-flex flex-column align-items-center" 
                             style="border-radius: 50px; padding: 8px 20px; cursor: default;">
                            <span class="fw-bold"><?= $etat_label ?></span>
                            <span class="small" style="font-size: 0.75rem;"><?= number_format($offre['prix'], 2) ?> €</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-4">
                <h5 class="fw-bold border-bottom pb-2">Descriptif</h5>
                <p class="text-secondary" style="text-align: justify;"><?= nl2br(htmlspecialchars($livre['description'])) ?></p>
            </div>

            <div class="mt-4">
                <h5 class="fw-bold border-bottom pb-2">Caractéristiques</h5>
                <table class="table table-sm mt-3">
                    <tr><th>Éditeur</th><td><?= htmlspecialchars($livre['editeur'] ?? '-') ?></td></tr>
                    <tr><th>Année</th><td><?= htmlspecialchars($livre['annee_parution'] ?? '-') ?></td></tr>
                    <tr><th>ISBN</th><td><?= htmlspecialchars($livre['isbn'] ?? '-') ?></td></tr>
                    <tr><th>Dimensions</th><td><?= htmlspecialchars($livre['dimensions'] ?? '-') ?></td></tr>
                    <tr><th>Nb de pages</th><td><?= htmlspecialchars($livre['nb_pages'] ?? '-') ?></td></tr>
                    <tr><th>Poids</th><td><?= htmlspecialchars($livre['poids'] ?? '-') ?> g</td></tr>
                    <tr><th>Reliure</th><td><?= htmlspecialchars($livre['reliure'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-4 shadow-sm border-0 bg-white" style="border-radius: 15px;">
                <?php if ($offre_selectionnee): ?>
                    <h2 class="fw-bold text-success"><?= number_format($offre_selectionnee['prix'], 2) ?> €</h2>
                    <p class="text-muted small">Stock : <?= $offre_selectionnee['quantite'] ?></p>
                    
                    <?php if ($offre_selectionnee['quantite'] > 0): ?>
                        <form action="action_ajouter_panier.php" method="POST">
                            <input type="hidden" name="id_livre" value="<?= $id ?>">
                            <input type="hidden" name="id_exemplaire" value="<?= $offre_selectionnee['id_exemplaire'] ?>">
                            <input type="hidden" name="titre" value="<?= htmlspecialchars($livre['titre']) ?>">
                            <input type="hidden" name="etat" value="<?= htmlspecialchars($offre_selectionnee['etat']) ?>">
                            <input type="hidden" name="prix" value="<?= $offre_selectionnee['prix'] ?>">
                            <input type="hidden" name="image" value="<?= htmlspecialchars($livre['couverture'] ?? 'default.jpg') ?>">
                            <button type="submit" name="action" value="ajouter" class="btn btn-custom-green w-100 mb-2">Ajouter au panier</button>
                            <button type="submit" name="action" value="acheter" class="btn btn-outline-dark w-100 rounded-pill">Acheter</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 rounded-pill" disabled>Épuisé</button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <hr class="my-3">
                    <div class="d-grid gap-2">
                        <a href="modifier_livre.php?id=<?= $id ?>" class="btn btn-admin-green">Modifier la fiche</a>
                        <a href="gerer_exemplaires.php?id=<?= $id ?>" class="btn btn-outline-success rounded-pill">Gérer le stock</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>