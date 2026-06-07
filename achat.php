<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier'])) {
    header("Location: panier.php");
    exit();
}

$sousTotal = 0;
foreach ($_SESSION['panier'] as $item) {
    $sousTotal += $item['prix'] * $item['quantite'];
}

$fraisLivraison = ($sousTotal > 0 && $sousTotal < 20) ? 1.99 : 0;
$totalFinal = $sousTotal + $fraisLivraison;

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <h4 class="fw-bold mb-4">Adresse de livraison</h4>
                <form id="form-achat" action="confirmation_achat.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6"><input type="text" name="prenom" class="form-control" placeholder="Prénom" required></div>
                        <div class="col-md-6"><input type="text" name="nom" class="form-control" placeholder="Nom" required></div>
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
                <div class="d-flex justify-content-between">
                    <span>Total</span>
                    <span class="fw-bold fs-4 text-success"><?= number_format($totalFinal, 2) ?> €</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>