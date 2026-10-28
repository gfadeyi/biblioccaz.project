<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || empty($_SESSION['panier'])) {
    header("Location: panier.php");
    exit();
}

require_once 'panier_calcul.php';
require_once 'compte_lib.php';
require_once 'points_lib.php';
require_once 'avis_lib.php';

$idUser = (int) $_SESSION['user_id'];
$niveau = niveauMembre($pdo, $idUser);
attribuerBonAnniversaire($pdo, $idUser, $niveau);
$solde = soldePoints($pdo, $idUser);
$totaux = calculerTotauxPanier($_SESSION['panier'], 0, $niveau['livraison_des'], pointsRegle($pdo, 'achat'));
$options = optionsReduction($pdo, $idUser, $totaux, $solde);
$scenarios = ['' => $totaux];
foreach ($options as $cle => $option) {
    if ($option['possible']) {
        $scenarios[$cle] = calculerTotauxPanier($_SESSION['panier'], $option['montant'], $niveau['livraison_des'], pointsRegle($pdo, 'achat'));
    }
}
$totalFinal = $totaux['total'];
$csrf = jetonCsrf();

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
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
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
                        <div class="col-md-6">
                            <label for="livraison-prenom" class="form-label small fw-bold">Prénom</label>
                            <input type="text" id="livraison-prenom" name="prenom" class="form-control" value="<?= htmlspecialchars($infosUser['prenom'] ?? '') ?>" autocomplete="given-name">
                        </div>
                        <div class="col-md-6">
                            <label for="livraison-nom" class="form-label small fw-bold">Nom</label>
                            <input type="text" id="livraison-nom" name="nom" class="form-control" value="<?= htmlspecialchars($infosUser['nom'] ?? '') ?>" autocomplete="family-name">
                        </div>
                        <div class="col-12">
                            <label for="livraison-adresse" class="form-label small fw-bold">N° et nom de rue</label>
                            <input type="text" id="livraison-adresse" name="adresse" class="form-control" autocomplete="address-line1">
                        </div>
                        <div class="col-12">
                            <label for="livraison-complement" class="form-label small fw-bold">Complément <span class="fw-normal text-muted">(facultatif)</span></label>
                            <input type="text" id="livraison-complement" name="complement" class="form-control" placeholder="Bâtiment, étage…" autocomplete="address-line2">
                        </div>
                        <div class="col-4">
                            <label for="livraison-code-postal" class="form-label small fw-bold">Code postal</label>
                            <input type="text" id="livraison-code-postal" name="code_postal" class="form-control" maxlength="5" inputmode="numeric" autocomplete="postal-code">
                        </div>
                        <div class="col-8">
                            <label for="livraison-ville" class="form-label small fw-bold">Ville</label>
                            <input type="text" id="livraison-ville" name="ville" class="form-control" autocomplete="address-level2">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="enregistrer" value="1" id="enregistrer" checked>
                                <label class="form-check-label small" for="enregistrer">Enregistrer cette adresse dans mon compte</label>
                            </div>
                        </div>
                    </div>
                    <fieldset class="bloc-points mt-4">
                        <legend class="bloc-points__titre">
                            <?= iconeDessin('medaille') ?>
                            <span>Utiliser mes points</span>
                            <span class="bloc-points__solde"><?= $solde ?> point<?= $solde > 1 ? 's' : '' ?> disponible<?= $solde > 1 ? 's' : '' ?></span>
                        </legend>
                        <p class="bloc-points__aide">Un seul bon par commande, dès <?= BON_MINIMUM_LIVRES ?> € de livres, dans la limite de 50 % des livres (hors livraison).</p>
                        <div class="choix-bons">
                            <label class="choix-bon">
                                <input type="radio" name="bon" value="" checked data-scenario="<?= htmlspecialchars(json_encode($scenarios[''])) ?>">
                                <span class="choix-bon__montant">Aucun</span>
                                <span class="choix-bon__detail">Garder mes points</span>
                            </label>
                            <?php foreach ($options as $cle => $option): ?>
                                <label class="choix-bon <?= $option['possible'] ? '' : 'indisponible' ?>">
                                    <input type="radio" name="bon" value="<?= htmlspecialchars($cle) ?>" <?= $option['possible'] ? 'data-scenario="' . htmlspecialchars(json_encode($scenarios[$cle])) . '"' : 'disabled' ?>>
                                    <span class="choix-bon__montant">-<?= formaterPrix($option['montant']) ?></span>
                                    <span class="choix-bon__detail"><?= htmlspecialchars($option['libelle']) ?></span>
                                    <?php if (!$option['possible']): ?><span class="choix-bon__raison"><?= htmlspecialchars($option['raison']) ?></span><?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <button type="submit" class="btn btn-bibli w-100 mt-4 py-3 rounded-pill fw-bold" id="bouton-payer">Payer <?= formaterPrix($totalFinal) ?></button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 border-0 shadow-sm bg-white" style="border-radius: 20px;">
                <h4 class="fw-bold mb-4">Résumé de la commande</h4>
                <?php foreach ($_SESSION['panier'] as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= htmlspecialchars(urlImage($item['image'])) ?>" alt="" loading="lazy" style="width: 40px; height: 50px; object-fit: cover;">
                        <div class="ms-3">
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['titre']) ?></h6>
                            <small class="text-muted">Qté : <?= $item['quantite'] ?> - <?= htmlspecialchars($item['etat']) ?></small>
                        </div>
                        <div class="ms-auto ps-2 fw-bold text-nowrap"><?= formaterPrix($item['prix'] * $item['quantite']) ?></div>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="ligne-recap">
                    <span>Sous-total</span>
                    <span><?= formaterPrix($totaux['sous_total']) ?></span>
                </div>
                <?php if ($totaux['remise'] > 0): ?>
                    <div class="ligne-recap remise">
                        <span>Remise panier</span>
                        <span>-<?= formaterPrix($totaux['remise']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="ligne-recap remise" id="recap-bon" hidden>
                    <span>Bon de points</span>
                    <span data-recap="reduction"></span>
                </div>
                <div class="ligne-recap">
                    <span>Livraison<?= $niveau['livraison_des'] < 20 ? ' <small class="avantage-niveau">avantage ' . htmlspecialchars($niveau['nom']) . '</small>' : '' ?></span>
                    <span><?= $totaux['frais_livraison'] > 0 ? formaterPrix($totaux['frais_livraison']) : 'Offerte' ?></span>
                </div>
                <div class="ligne-recap total">
                    <span>Total</span>
                    <span data-recap="total"><?= formaterPrix($totalFinal) ?></span>
                </div>
                <div class="gain-points" data-bonus="<?= (int) $niveau['bonus'] ?>">
                    <?= iconeDessin('medaille') ?>
                    <span>Vous gagnerez <strong data-recap="points"><?= $totaux['points'] + bonusNiveau($totaux['points'], $niveau) ?></strong> points
                        <?php if ($niveau['bonus'] > 0): ?><small>(dont +<?= $niveau['bonus'] ?> % de bonus <?= htmlspecialchars($niveau['nom']) ?>)</small><?php endif; ?>
                    </span>
                </div>
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

    const euros = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
    const bonusNiveau = parseInt(document.querySelector('.gain-points').dataset.bonus, 10) || 0;
    function majRecap() {
        const choisi = document.querySelector('input[name="bon"]:checked');
        if (!choisi || !choisi.dataset.scenario) return;
        const scenario = JSON.parse(choisi.dataset.scenario);
        document.getElementById('recap-bon').hidden = scenario.reduction <= 0;
        document.querySelector('[data-recap="reduction"]').textContent = '-' + euros.format(scenario.reduction);
        document.querySelector('[data-recap="total"]').textContent = euros.format(scenario.total);
        document.querySelector('[data-recap="points"]').textContent = scenario.points + Math.floor(scenario.points * bonusNiveau / 100);
        document.getElementById('bouton-payer').textContent = 'Payer ' + euros.format(scenario.total);
    }
    document.querySelectorAll('input[name="bon"]').forEach(c => c.addEventListener('change', majRecap));
    majRecap();
})();
</script>
<?php include 'footer.php'; ?>