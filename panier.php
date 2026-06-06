<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$sousTotal = 0;
$totalArticles = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $sousTotal += $item['prix'] * $item['quantite'];
        $totalArticles += $item['quantite'];
    }
}

$remise = 0;
if ($sousTotal >= 50) {
    $remise = $sousTotal * 0.10;
} elseif ($sousTotal >= 30) {
    $remise = $sousTotal * 0.05;
}

$fraisLivraison = 0;
if ($sousTotal > 0 && $sousTotal < 20) {
    $fraisLivraison = 1.99;
}

$totalFinal = $sousTotal - $remise + $fraisLivraison;

include 'header.php';
?>

<style>
    .btn-custom-green { 
        background-color: #274e13; 
        color: white; 
        font-weight: bold;
        border: none;
        transition: all 0.3s ease;
        border-radius: 8px;
    }
    .btn-custom-green:hover { 
        background-color: #1a330d; 
        color: white; 
    }
</style>

<div class="container mt-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <h2 class="fw-bold mb-4">Mon panier</h2>

            <div class="card border-success p-3 mb-4 bg-white" style="border-radius: 12px; border-width: 2px !important;">
                <div class="d-flex align-items-center">
                    <i class="bi bi-lightbulb text-success fs-3 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-success">OFFRE EN COURS</h6>
                        <small class="text-secondary">-5% dès 30€ d'achats | -10% dès 50€ d'achats | Livraison OFFERTE dès 20€ !</small>
                    </div>
                </div>
            </div>

            <?php if (empty($_SESSION['panier'])): ?>
                <div class="card p-5 text-center shadow-sm border-0 bg-white" style="border-radius: 15px;">
                    <i class="bi bi-cart-x text-muted fs-1 mb-3"></i>
                    <h5 class="text-secondary">Votre panier est vide</h5>
                    <a href="index.php" class="btn btn-custom-green rounded-pill px-4 mt-3 text-white text-decoration-none">Découvrir nos livres</a>
                </div>
            <?php else: ?>
                <div style="max-height: 480px; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                    <?php foreach ($_SESSION['panier'] as $key => $item): ?>
                        <div class="card p-3 border-0 shadow-sm bg-white mb-3 position-relative" style="border-radius: 15px;">
                            <a href="action_modifier_panier.php?action=supprimer&key=<?= $key ?>" class="position-absolute top-0 end-0 mt-3 me-3 text-muted text-decoration-none fs-5">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            <div class="row align-items-center g-3">
                                <div class="col-auto">
                                    <img src="img/<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded border" style="width: 70px; height: 95px; object-fit: cover;">
                                </div>
                                <div class="col">
                                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['titre']) ?></h6>
                                    <div class="badge bg-light text-secondary border px-2 py-1 mb-2" style="font-size: 0.75rem;"><?= htmlspecialchars($item['etat']) ?></div>
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <div class="input-group input-group-sm border rounded-pill overflow-hidden bg-light" style="width: 100px;">
                                            <a href="action_modifier_panier.php?action=moins&key=<?= $key ?>" class="btn btn-light border-0 px-2 d-flex align-items-center justify-content-center"><i class="bi bi-minus small"></i></a>
                                            <input type="text" class="form-control text-center border-0 bg-transparent fw-bold small p-0" value="<?= $item['quantite'] ?>" readonly style="box-shadow: none;">
                                            <a href="action_modifier_panier.php?action=plus&key=<?= $key ?>" class="btn btn-light border-0 px-2 d-flex align-items-center justify-content-center"><i class="bi bi-plus small"></i></a>
                                        </div>
                                        <div class="fw-bold text-dark fs-5"><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-5">
                <h5 class="fw-bold text-dark mb-4">Ces livres pourraient aussi vous plaire</h5>
                <div class="row g-3">
                    <?php
                    $current_id = !empty($_SESSION['panier']) ? reset($_SESSION['panier'])['id_livre'] : 0;
                    $stmtSug = $pdo->prepare("SELECT id_livre, titre, couverture, auteur FROM livre WHERE id_livre != ? ORDER BY RAND() LIMIT 3");
                    $stmtSug->execute([$current_id]);
                    $suggestions = $stmtSug->fetchAll();
                    foreach ($suggestions as $sug):
                        $sug_img = !empty($sug['couverture']) ? $sug['couverture'] : 'default.jpg';
                    ?>
                        <div class="col-md-4">
                            <a href="detail_livre.php?id=<?= $sug['id_livre'] ?>" class="text-decoration-none text-dark">
                                <div class="p-3 border-0 shadow-sm rounded-4 text-center bg-white h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <img src="img/<?= htmlspecialchars($sug_img) ?>" class="img-fluid rounded mb-3 shadow-sm" style="max-height: 120px; object-fit: cover;">
                                        <div class="small fw-bold text-dark text-truncate mb-1"><?= htmlspecialchars($sug['titre']) ?></div>
                                        <div class="text-muted small text-truncate mb-2"><?= htmlspecialchars($sug['auteur']) ?></div>
                                    </div>
                                    <div class="fw-bold text-success mt-2">1,79 €</div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <h2 class="fw-bold mb-4">Récapitulatif</h2>
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary">Panier (<?= $totalArticles ?>)</span>
                    <span class="fw-medium text-dark"><?= number_format($sousTotal, 2) ?> €</span>
                </div>

                <?php if ($remise > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 text-danger">
                        <span>Remise automatique</span>
                        <span class="fw-bold">-<?= number_format($remise, 2) ?> €</span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary">Frais de livraison</span>
                    <span class="fw-medium <?= $fraisLivraison == 0 ? 'text-success fw-bold' : 'text-dark' ?>">
                        <?= $fraisLivraison == 0 ? 'Gratuit' : number_format($fraisLivraison, 2) . ' €' ?>
                    </span>
                </div>

                <?php if ($fraisLivraison == 0 && $sousTotal > 0): ?>
                    <div class="d-flex align-items-center text-success bg-light border border-success p-2 rounded-3 mb-4" style="font-size: 0.85rem;">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span class="fw-medium">Votre commande est éligible à la livraison gratuite !</span>
                    </div>
                <?php elseif ($sousTotal > 0): ?>
                    <div class="d-flex align-items-center text-success bg-light border border-success p-2 rounded-3 mb-4" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <span class="fw-medium">Ajoutez <strong><?= number_format(20 - $sousTotal, 2) ?> €</strong> pour avoir la livraison gratuite.</span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top mb-4">
                    <span class="fw-bold text-dark fs-5">Total</span>
                    <span class="fw-bold fs-4 text-success"><?= number_format($totalFinal, 2) ?> €</span>
                </div>

                <div class="d-grid">
                    <?php if (!empty($_SESSION['panier'])): ?>
                        <a href="achat.php" class="btn btn-custom-green text-white fw-bold py-3 rounded-pill fs-5 shadow-sm text-center text-decoration-none">
                            Valider mon panier
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary text-white fw-bold py-3 rounded-pill fs-5 shadow-sm" disabled>
                            Panier vide
                        </button>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-4 pt-2">
                    <small class="text-muted"><i class="bi bi-lock-fill me-1"></i> Paiement 100% sécurisé</small>
                    <div class="d-flex gap-2 justify-content-center mt-3 fs-3 text-secondary">
                        <i class="bi bi-credit-card"></i>
                        <i class="bi bi-paypal"></i>
                        <i class="bi bi-apple"></i>
                        <i class="bi bi-google"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>