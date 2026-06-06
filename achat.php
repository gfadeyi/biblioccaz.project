<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'client')) {
    header("Location: login.php?error=" . urlencode("Vous devez être connecté."));
    exit();
}

if (empty($_SESSION['panier'])) {
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
            <form id="form-achat" action="confirmation_achat.php" method="POST">
                <div class="card p-4 border-0 shadow-sm bg-white mb-4" style="border-radius: 20px;">
                    <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-geo-alt text-success me-2"></i>Adresse</h4>
                    <div class="row g-3">
                        <input type="text" name="prenom" class="form-control" required value="Jean">
                        <input type="text" name="nom" class="form-control" required value="Dupont">
                    </div>
                </div>
                <button type="button" id="btn-payer" class="btn btn-success w-100 py-3 rounded-pill">Confirmer et payer</button>
            </form>
        </div>
        <div class="col-lg-5">
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <h4 class="fw-bold mb-4">Résumé</h4>
                <?php foreach ($_SESSION['panier'] as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= htmlspecialchars($item['titre']) ?> (x<?= $item['quantite'] ?>)</span>
                        <span><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</span>
                    </div>
                <?php endforeach; ?>
                <div class="border-top pt-3 mt-3">
                    <h5 class="fw-bold text-success">Total : <?= number_format($totalFinal, 2) ?> €</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-payer').addEventListener('click', function() {
    document.getElementById('form-achat').submit();
});
</script>

<?php include 'footer.php'; ?>