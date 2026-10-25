<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier'])) {
    header("Location: panier.php");
    exit();
}

require_once 'panier_calcul.php';
require_once 'compte_lib.php';

$totaux = calculerTotauxPanier($_SESSION['panier']);
$totalFinal = $totaux['total'];

$stmtU = $pdo->prepare("SELECT nom, prenom FROM user WHERE id = ?");
$stmtU->execute([$_SESSION['user_id']]);
$infosUser = $stmtU->fetch() ?: [];
$adresses = adressesDe($pdo, (int) $_SESSION['user_id']);
$erreurAdresse = $_GET['erreur'] ?? '';

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <h4 class="fw-bold mb-4">Adresse de livraison</h4>
                <?php if ($erreurAdresse): ?>
                    <div class="alert alert-danger small py-2"><?= htmlspecialchars($erreurAdresse) ?></div>
                <?php endif; ?>
                <form id="form-achat" action="confirmation_achat.php" method="POST">
                    <?php if ($adresses): ?>
                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($adresses as $a): ?>
                                <label class="border rounded-3 p-3 d-flex gap-3 align-items-start choix-adresse">
                                    <input type="radio" name="id_adresse" value="<?= $a['id_adresse'] ?>" class="form-check-input mt-1" <?= $a['par_defaut'] ? 'checked' : '' ?>>
                                    <span class="small">
                                        <strong><?= htmlspecialchars($a['libelle']) ?></strong><br>
                                        <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?> · <?= htmlspecialchars(adresseEnLigne($a)) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <label class="border rounded-3 p-3 d-flex gap-3 align-items-center choix-adresse">
                                <input type="radio" name="id_adresse" value="0" class="form-check-input m-0">
                                <span class="small fw-bold">Livrer à une autre adresse</span>
                            </label>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="id_adresse" value="0">
                    <?php endif; ?>

                    <div id="nouvelle-adresse" class="row g-3 <?= $adresses ? 'd-none' : '' ?>">
                        <div class="col-md-6"><input type="text" name="prenom" class="form-control" placeholder="Prénom" value="<?= htmlspecialchars($infosUser['prenom'] ?? '') ?>" autocomplete="given-name"></div>
                        <div class="col-md-6"><input type="text" name="nom" class="form-control" placeholder="Nom" value="<?= htmlspecialchars($infosUser['nom'] ?? '') ?>" autocomplete="family-name"></div>
                        <div class="col-12"><input type="text" name="adresse" class="form-control" placeholder="N° et nom de rue" autocomplete="address-line1"></div>
                        <div class="col-12"><input type="text" name="complement" class="form-control" placeholder="Complément (bâtiment, étage...)" autocomplete="address-line2"></div>
                        <div class="col-4"><input type="text" name="code_postal" class="form-control" placeholder="Code postal" maxlength="5" inputmode="numeric" autocomplete="postal-code"></div>
                        <div class="col-8"><input type="text" name="ville" class="form-control" placeholder="Ville" autocomplete="address-level2"></div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="enregistrer" value="1" id="enregistrer" checked>
                                <label class="form-check-label small" for="enregistrer">Enregistrer cette adresse dans mon compte</label>
                            </div>
                        </div>
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
<style>
    .choix-adresse { cursor: pointer; }
    .choix-adresse:has(input:checked) { border-color: #274e13 !important; background: #f4f7f4; }
</style>
<script>
(function () {
    const bloc = document.getElementById('nouvelle-adresse');
    const choix = document.querySelectorAll('input[name="id_adresse"][type="radio"]');
    function majAffichage() {
        const nouvelle = !choix.length || document.querySelector('input[name="id_adresse"]:checked')?.value === '0';
        bloc.classList.toggle('d-none', !nouvelle);
        bloc.querySelectorAll('input[name="prenom"], input[name="nom"], input[name="adresse"], input[name="code_postal"], input[name="ville"]')
            .forEach(champ => champ.required = nouvelle);
    }
    choix.forEach(c => c.addEventListener('change', majAffichage));
    majAffichage();
})();
</script>
<?php include 'footer.php'; ?>