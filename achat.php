<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier'])) {
    header("Location: panier.php");
    exit();
}

require_once 'panier_calcul.php';

// Même calcul que le panier (avant, la remise -5% / -10% était oubliée ici)
$totaux = calculerTotauxPanier($_SESSION['panier']);
$totalFinal = $totaux['total'];

// Pré-remplir l'adresse avec les infos du compte
$stmtU = $pdo->prepare("SELECT nom, prenom FROM user WHERE id = ?");
$stmtU->execute([$_SESSION['user_id']]);
$infosUser = $stmtU->fetch() ?: [];

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <h4 class="fw-bold mb-4">Adresse de livraison</h4>
                <form id="form-achat" action="confirmation_achat.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6"><input type="text" name="prenom" class="form-control" placeholder="Prénom" value="<?= htmlspecialchars($infosUser['prenom'] ?? '') ?>" required></div>
                        <div class="col-md-6"><input type="text" name="nom" class="form-control" placeholder="Nom" value="<?= htmlspecialchars($infosUser['nom'] ?? '') ?>" required></div>
                        <div class="col-12"><input type="text" name="adresse" class="form-control" placeholder="Adresse" required></div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mt-4 py-3 rounded-pill">Payer <?= number_format($totalFinal, 2) ?> €</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <h4 class="fw-bold mb-4">Résumé de la commande</h4>
                <?php foreach ($_SESSION['panier'] as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="img/<?= htmlspecialchars($item['image']) ?>" style="width: 40px; height: 50px; object-fit: cover;">
                        <div class="ms-3">
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['titre']) ?></h6>
                            <small class="text-muted">Qté : <?= $item['quantite'] ?> - <?= htmlspecialchars($item['etat']) ?></small>
                        </div>
                        <div class="ms-auto fw-bold"><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</div>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between small text-secondary mb-1">
                    <span>Sous-total</span>
                    <span><?= number_format($totaux['sous_total'], 2) ?> €</span>
                </div>
                <?php if ($totaux['remise'] > 0): ?>
                    <div class="d-flex justify-content-between small text-danger mb-1">
                        <span>Remise automatique</span>
                        <span>-<?= number_format($totaux['remise'], 2) ?> €</span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between small text-secondary mb-2">
                    <span>Livraison</span>
                    <span><?= $totaux['frais_livraison'] > 0 ? number_format($totaux['frais_livraison'], 2) . ' €' : 'Gratuite' ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total</span>
                    <span class="fw-bold fs-4 text-success"><?= number_format($totalFinal, 2) ?> €</span>
                </div>
                <?php if ($totaux['points'] > 0): ?>
                    <div class="text-end small text-success mt-1">
                        <i class="bi bi-coin me-1"></i> +<?= $totaux['points'] ?> points fidélité
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>