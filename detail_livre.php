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

$stmtEx = $pdo->prepare("SELECT etat, prix, SUM(quantite) as stock 
                         FROM exemplaire 
                         WHERE id_livre = ? AND is_disponible = 1 
                         GROUP BY etat, prix 
                         ORDER BY prix ASC");
$stmtEx->execute([$id]);
$offres = $stmtEx->fetchAll();

$offre_selectionnee = $offres[$offre_index] ?? $offres[0] ?? null;

function formaterEtat($etat) {
    $etat = strtolower($etat);
    if ($etat == 'tres bon' || $etat == 'très bon') return 'Très bon';
    if ($etat == 'bon') return 'Bon';
    if ($etat == 'neuf') return 'Neuf';
    if ($etat == 'use' || $etat == 'usé') return 'Usé';
    return ucfirst($etat);
}
?>

<style>
    body { background-color: #f8f9fa; }
    .sticky-top { top: 20px; }
    .btn-custom-green { 
        background-color: #274e13; 
        color: white; 
        font-weight: bold;
        border: none;
        transition: all 0.3s ease;
        border-radius: 8px;
    }
    .btn-custom-green:hover { background-color: #1a330d; color: white; }
    .section-header {
        color: #274e13;
        font-weight: bold;
        border-bottom: 2px solid #274e13;
        padding-bottom: 8px;
        margin-bottom: 20px;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .offre-link { text-decoration: none; color: inherit; display: block; flex: 0 0 auto; }
    .offre-card {
        border: 2px solid #eee;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        transition: all 0.2s;
        background: white;
        min-width: 130px;
    }
    .offre-card.active {
        border-color: #274e13;
        background-color: #f4f7f2;
    }
    .offre-card .etat-nom { font-weight: bold; color: #333; margin-bottom: 2px; }
    .offre-card .prix-val { font-weight: 800; color: #274e13; font-size: 1.1rem; }
    .offre-card .stock-msg { font-size: 0.7rem; font-weight: bold; height: 16px; margin-bottom: 4px; }
    .text-biblio { color: #274e13 !important; }
    .char-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; background: white; }
    .char-table td { padding: 10px 12px; border: 1px solid #eee; }
    .char-label { background-color: #f8f9fa; color: #666; font-weight: bold; width: 35%; }
</style>

<div class="container mt-5 mb-5">
    <div class="row g-5">
        <div class="col-md-4">
            <div class="sticky-top">
                <div class="p-4 bg-white border rounded shadow-sm text-center">
                    <img src="img/<?= $livre['couverture'] ?: 'default.png' ?>" class="img-fluid" style="max-height: 450px;">
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <h1 class="fw-bold"><?= htmlspecialchars($livre['titre']) ?></h1>
            <p class="text-muted mb-4 h5">Par <span class="text-biblio"><?= htmlspecialchars($livre['auteur']) ?></span></p>
            
            <div class="mb-4">
                <h6 class="fw-bold mb-3">État des livres</h6>
                <div class="d-flex gap-2 overflow-auto pb-2">
                    <?php foreach ($offres as $index => $offre): 
                        $is_active = ($index == $offre_index);
                        $etat_label = formaterEtat($offre['etat']);
                    ?>
                        <a href="detail_livre.php?id=<?= $id ?>&offre=<?= $index ?>" class="offre-link">
                            <div class="offre-card <?= $is_active ? 'active' : '' ?>">
                                <div class="etat-nom"><?= $etat_label ?></div>
                                <div class="stock-msg">
                                    <?php if ($offre['stock'] == 1): ?>
                                        <span class="text-danger small">C'est le dernier !</span>
                                    <?php elseif ($offre['stock'] < 5): ?>
                                        <span class="text-biblio small">Plus que <?= $offre['stock'] ?> ex.</span>
                                    <?php endif; ?>
                                </div>
                                <div class="prix-val"><?= number_format($offre['prix'], 2) ?> €</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-5">
                <h5 class="section-header">Résumé</h5>
                <div class="text-secondary" style="line-height: 1.7; text-align: justify;">
                    <?= nl2br(htmlspecialchars($livre['description'])) ?>
                </div>
            </div>

            <div class="mt-5">
                <h5 class="section-header">Caractéristiques</h5>
                <div class="row g-0 border shadow-sm rounded overflow-hidden bg-white">
                    <div class="col-6 border-end">
                        <table class="char-table">
                            <tr><td class="char-label">Édition</td><td><?= htmlspecialchars($livre['editeur'] ?? '-') ?></td></tr>
                            <tr><td class="char-label">Dimension</td><td><?= htmlspecialchars($livre['dimensions'] ?? '-') ?></td></tr>
                            <tr><td class="char-label">Auteur</td><td class="text-biblio"><?= htmlspecialchars($livre['auteur']) ?></td></tr>
                            <tr><td class="char-label">Nb de pages</td><td><?= htmlspecialchars($livre['nb_pages'] ?? '-') ?></td></tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <table class="char-table">
                            <tr><td class="char-label">ISBN</td><td><?= htmlspecialchars($livre['isbn'] ?? '-') ?></td></tr>
                            <tr><td class="char-label">Publication</td><td><?= htmlspecialchars($livre['annee_parution'] ?? '-') ?></td></tr>
                            <tr><td class="char-label">Poids (g)</td><td><?= htmlspecialchars($livre['poids'] ?? '-') ?></td></tr>
                            <tr><td class="char-label">Reliure</td><td><?= htmlspecialchars($livre['reliure'] ?? '-') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-4 shadow-sm border-0 bg-white sticky-top" style="border-radius: 15px;">
                <?php if ($offre_selectionnee): ?>
                    <h2 class="fw-bold mb-1 text-biblio"><?= number_format($offre_selectionnee['prix'], 2) ?> €</h2>
                    <p class="text-muted small mb-4">En stock (État : <?= formaterEtat($offre_selectionnee['etat']) ?>)</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-custom-green py-3 shadow-sm rounded-pill">Ajouter au panier</button>
                        <button class="btn btn-outline-dark py-2 rounded-pill">Acheter</button>
                    </div>
                <?php else: ?>
                    <h4 class="text-danger fw-bold text-center">Épuisé</h4>
                <?php endif; ?>
                <hr class="my-4">
                <div class="text-center">
                    <p class="mb-0 fw-bold text-biblio">BIBLIOccaz</p>
                    <a href="index.php" class="btn btn-link btn-sm mt-2 text-decoration-none text-muted">&larr; Retour</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>