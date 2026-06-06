<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'client')) {
    header("Location: login.php?error=" . urlencode("Vous devez être connecté ou inscrit en tant que client pour procéder au paiement."));
    exit();
}

if (empty($_SESSION['panier'])) {
    header("Location: panier.php");
    exit();
}

$sousTotal = 0;
$totalArticles = 0;
foreach ($_SESSION['panier'] as $item) {
    $sousTotal += $item['prix'] * $item['quantite'];
    $totalArticles += $item['quantite'];
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
    .stripe-input-container {
        background-color: #fcfcfc;
        border: 1px solid #dee2e6;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .stripe-input-container:focus-within {
        border-color: #274e13;
        box-shadow: 0 0 0 0.25rem rgba(39, 78, 19, 0.25);
    }
</style>

<div class="container mt-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <form id="form-achat" action="confirmation_achat.php" method="POST">
                <div class="card p-4 border-0 shadow-sm bg-white mb-4" style="border-radius: 20px;">
                    <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-geo-alt text-success me-2"></i>Adresse de livraison</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Prénom</label>
                            <input type="text" name="prenom" class="form-control rounded-3" required value="Jean">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Nom</label>
                            <input type="text" name="nom" class="form-control rounded-3" required value="Dupont">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Adresse postale</label>
                            <input type="text" name="adresse" class="form-control rounded-3" placeholder="Numéro et nom de rue" required value="12 Rue de l'ESGI">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Code postal</label>
                            <input type="text" name="cp" class="form-control rounded-3" required value="75012">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Ville</label>
                            <input type="text" name="ville" class="form-control rounded-3" required value="Paris">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control rounded-3" required value="0601020304">
                        </div>
                    </div>
                </div>

                <div class="card p-4 border-0 shadow-sm bg-white mb-4" style="border-radius: 20px;">
                    <h4 class="fw-bold mb-3 text-dark"><i class="bi bi-truck text-success me-2"></i>Mode de livraison</h4>
                    <div class="p-3 border border-success rounded-4 bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="form-check me-2">
                                <input class="form-check-input" type="radio" checked style="cursor: default;">
                            </div>
                            <div>
                                <strong class="text-dark d-block">Livraison à domicile (La Poste)</strong>
                                <small class="text-secondary">Livré chez vous sous 2 à 3 jours ouvrés.</small>
                            </div>
                        </div>
                        <span class="fw-bold text-success"><?= $fraisLivraison == 0 ? 'Offert' : number_format($fraisLivraison, 2) . ' €' ?></span>
                    </div>
                </div>

                <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                    <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-credit-card text-success me-2"></i>Paiement sécurisé</h4>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Informations de carte bancaire (Mode Test activé)</label>
                        <div class="stripe-input-container p-3 rounded-3 d-flex align-items-center gap-2">
                            <i class="bi bi-credit-card-2-front text-muted fs-5"></i>
                            <input type="text" class="form-control border-0 p-0 bg-transparent flex-grow-1" placeholder="4242 4242 4242 4242" style="box-shadow: none;" maxlength="19" required value="4242 4242 4242 4242">
                            <input type="text" class="form-control border-0 p-0 bg-transparent text-center" placeholder="12/28" style="box-shadow: none; width: 60px;" maxlength="5" required value="12/28">
                            <input type="text" class="form-control border-0 p-0 bg-transparent text-center" placeholder="123" style="box-shadow: none; width: 50px;" maxlength="4" required value="123">
                        </div>
                        <div class="form-text small text-muted mt-2"><i class="bi bi-info-circle text-success me-1"></i>Vous pouvez saisir n'importe quel numéro fictif pour valider la simulation de paiement.</div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <h4 class="fw-bold mb-4 text-dark">Résumé de la commande</h4>
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <div style="max-height: 180px; overflow-y: auto; padding-right: 5px;" class="mb-4">
                    <?php foreach ($_SESSION['panier'] as $item): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center min-width-0">
                                <img src="img/<?= htmlspecialchars($item['image']) ?>" class="rounded border" style="width: 35px; height: 48px; object-fit: cover; flex-shrink: 0;">
                                <div class="ms-3 min-width-0">
                                    <h6 class="mb-0 small fw-bold text-dark text-truncate" style="max-width: 180px;"><?= htmlspecialchars($item['titre']) ?></h6>
                                    <small class="text-secondary" style="font-size: 0.75rem;">Qté : <?= $item['quantite'] ?> (<?= htmlspecialchars($item['etat']) ?>)</small>
                                </div>
                            </div>
                            <span class="fw-medium text-dark small"><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary small">Sous-total</span>
                    <span class="fw-medium text-dark small"><?= number_format($sousTotal, 2) ?> €</span>
                </div>

                <?php if ($remise > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 text-danger small">
                        <span>Remise automatique</span>
                        <span class="fw-bold">-<?= number_format($remise, 2) ?> €</span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-secondary small">Frais de livraison</span>
                    <span class="fw-medium <?= $fraisLivraison == 0 ? 'text-success fw-bold small' : 'text-dark small' ?>">
                        <?= $fraisLivraison == 0 ? 'Gratuit' : number_format($fraisLivraison, 2) . ' €' ?>
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top mb-4">
                    <span class="fw-bold text-dark fs-5">Total à régler</span>
                    <span class="fw-bold fs-4 text-success"><?= number_format($totalFinal, 2) ?> €</span>
                </div>

                <div class="d-grid">
                    <button type="button" id="btn-payer" class="btn btn-custom-green text-white fw-bold py-3 rounded-pill fs-5 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        Confirmer et payer <?= number_format($totalFinal, 2) ?> €
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalChargementPaiement" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 p-4 shadow text-center" style="border-radius: 20px;">
            <div class="modal-body">
                <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                <h5 class="fw-bold text-dark mb-2">Traitement sécurisé Stripe en cours...</h5>
                <p class="text-secondary small mb-0">Veuillez ne pas fermer la page ni actualiser votre navigateur.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-payer').addEventListener('click', function() {
    const form = document.getElementById('form-achat');
    if (form.checkValidity()) {
        const modalElement = document.getElementById('modalChargementPaiement');
        const modalChargement = new bootstrap.Modal(modalElement);
        modalChargement.show();
        
        setTimeout(function() {
            form.submit();
        }, 2000);
    } else {
        form.reportValidity();
    }
});
</script>

<?php include 'footer.php'; ?>