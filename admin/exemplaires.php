<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../compte_lib.php';
exigerDroit('catalogue');

const ETATS_EXEMPLAIRE = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'Usé'];

$idLivre = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT id_livre, titre, auteur, couverture FROM livre WHERE id_livre = ?");
$stmt->execute([$idLivre]);
$livre = $stmt->fetch();
if (!$livre) {
    rediriger('inventaire.php', 'warning', "Ce livre n'existe pas.");
}
$retour = "exemplaires.php?id=$idLivre";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $action = $_POST['action'] ?? '';
    $idEx = (int) ($_POST['id_exemplaire'] ?? 0);

    if ($action === 'ajouter') {
        $etat = $_POST['etat'] ?? '';
        $prix = round((float) str_replace(',', '.', $_POST['prix'] ?? '0'), 2);
        $quantite = max(1, (int) ($_POST['quantite'] ?? 1));
        if (!isset(ETATS_EXEMPLAIRE[$etat]) || $prix <= 0) {
            rediriger($retour, 'danger', "Choisissez un état et un prix supérieur à 0.");
        }
        $stmt = $pdo->prepare("SELECT e.id_exemplaire FROM exemplaire e WHERE e.id_livre = ? AND e.etat = ? AND e.prix = ? AND " . conditionExemplaireMagasin() . " ORDER BY e.id_exemplaire LIMIT 1");
        $stmt->execute([$idLivre, $etat, $prix]);
        $idExistant = (int) $stmt->fetchColumn();
        if ($idExistant) {
            $pdo->prepare("UPDATE exemplaire SET quantite = quantite + ?, is_disponible = 1 WHERE id_exemplaire = ?")->execute([$quantite, $idExistant]);
        } else {
            $pdo->prepare("INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite, id_user) VALUES (?, ?, ?, 'vente', 1, ?, ?)")
                ->execute([$idLivre, $etat, $prix, $quantite, $_SESSION['user_id']]);
        }
        insertLog('STOCK', "Ajout de $quantite ex. ($etat, $prix €) pour le livre #$idLivre");
        $prevenus = notifierRetourEnStock($pdo, $idLivre);
        rediriger($retour, 'success', "$quantite exemplaire" . ($quantite > 1 ? 's ajoutés' : ' ajouté') . " au stock."
            . ($prevenus ? " $prevenus client" . ($prevenus > 1 ? 's qui attendaient ce livre ont été prévenus' : ' qui attendait ce livre a été prévenu') . " par mail." : ''));
    }

    if ($action === 'plus') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite + 1 WHERE id_exemplaire = ? AND id_livre = ? AND id_proprietaire IS NULL")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Stock +1 pour l'exemplaire #$idEx");
        notifierRetourEnStock($pdo, $idLivre);
    } elseif ($action === 'moins') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite - 1 WHERE id_exemplaire = ? AND id_livre = ? AND quantite > 0 AND id_proprietaire IS NULL")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Stock -1 pour l'exemplaire #$idEx");
    } elseif ($action === 'vider') {
        $pdo->prepare("UPDATE exemplaire SET quantite = 0 WHERE id_exemplaire = ? AND id_livre = ? AND id_proprietaire IS NULL")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Offre exemplaire #$idEx retirée de la vente (stock à 0)");
        rediriger($retour, 'success', "Offre retirée de la vente : elle reste dans l'historique avec un stock de 0.");
    } elseif ($action === 'supprimer') {
        $stmt = $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ? AND id_livre = ? AND quantite = 0 AND id_proprietaire IS NULL AND NOT EXISTS (SELECT 1 FROM commande_ligne cl WHERE cl.id_exemplaire = exemplaire.id_exemplaire)");
        $stmt->execute([$idEx, $idLivre]);
        if ($stmt->rowCount() === 0) {
            rediriger($retour, 'warning', "Cette offre a déjà été vendue : elle est conservée pour l'historique des commandes.");
        }
        insertLog('STOCK', "Suppression de l'offre exemplaire #$idEx (jamais vendue)");
        rediriger($retour, 'success', "Offre supprimée.");
    }
    rediriger($retour);
}

$stmt = $pdo->prepare("
    SELECT e.*, COALESCE(SUM(CASE WHEN c.id_commande IS NOT NULL THEN cl.quantite END), 0) AS nb_vendus, COUNT(cl.id_ligne) AS nb_lignes_commande
    FROM exemplaire e
    LEFT JOIN commande_ligne cl ON cl.id_exemplaire = e.id_exemplaire
    LEFT JOIN commande c ON c.id_commande = cl.id_commande AND c.statut <> 'annulee'
    WHERE e.id_livre = ? AND " . conditionExemplaireMagasin() . "
    GROUP BY e.id_exemplaire
    ORDER BY e.prix");
$stmt->execute([$idLivre]);
$toutesOffres = $stmt->fetchAll();
$exemplaires = array_values(array_filter($toutesOffres, fn($offre) => (int) $offre['quantite'] > 0));
$offresEpuisees = array_values(array_filter($toutesOffres, fn($offre) => (int) $offre['quantite'] <= 0));
$total = array_sum(array_column($exemplaires, 'quantite'));

$titrePage = 'Stock : ' . $livre['titre'];
$pageActive = 'inventaire';
$filAriane = [[null, 'Catalogue'], ['inventaire.php', 'Inventaire & stock']];
include __DIR__ . '/_haut.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="inventaire.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Inventaire</a>
    <a href="livre.php?id=<?= $idLivre ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Modifier la fiche</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <form method="POST" class="carte p-4">
            <?= champCsrf() ?>
            <input type="hidden" name="action" value="ajouter">
            <div class="d-flex gap-3 mb-3">
                <img src="<?= imageLivre($livre['couverture']) ?>" alt="" class="rounded shadow-sm" style="width: 70px; height: 100px; object-fit: cover;">
                <div>
                    <div class="fw-bold"><?= e($livre['titre']) ?></div>
                    <div class="small text-muted"><?= e($livre['auteur']) ?></div>
                    <div class="small mt-1"><strong><?= $total ?></strong> ex. en stock</div>
                </div>
            </div>
            <h2 class="h6 fw-bold text-success">Ajouter des exemplaires</h2>
            <div class="mb-3">
                <label class="form-label small fw-bold" for="etat">État</label>
                <select id="etat" name="etat" class="form-select">
                    <?php foreach (ETATS_EXEMPLAIRE as $cle => $libelle): ?>
                        <option value="<?= $cle ?>" <?= $cle === 'bon' ? 'selected' : '' ?>><?= $libelle ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label small fw-bold" for="prix">Prix (€)</label>
                    <input type="number" id="prix" name="prix" step="0.01" min="0.01" class="form-control" placeholder="12.50" required>
                </div>
                <div class="col-5">
                    <label class="form-label small fw-bold" for="quantite">Quantité</label>
                    <input type="number" id="quantite" name="quantite" value="1" min="1" class="form-control">
                </div>
            </div>
            <button class="btn btn-vert w-100 fw-bold"><i class="bi bi-plus-lg"></i> Ajouter au stock</button>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="carte mb-4">
            <div class="carte-entete">Offres en vente</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small text-uppercase"><tr><th>État</th><th>Prix</th><th class="text-center">Vendus</th><th class="text-center">Quantité</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php if (!$exemplaires): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucun exemplaire en stock.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($exemplaires as $ex): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? ucfirst($ex['etat'])) ?></span></td>
                                <td class="fw-bold text-success"><?= formaterPrix($ex['prix']) ?></td>
                                <td class="text-center text-muted"><?= (int) $ex['nb_vendus'] ?></td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <?php foreach (['moins' => 'dash', 'plus' => 'plus'] as $act => $icone): ?>
                                            <?php if ($act === 'plus'): ?><span class="fw-bold" style="min-width: 2ch;"><?= $ex['quantite'] ?></span><?php endif; ?>
                                            <form method="POST">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="<?= $act ?>">
                                                <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                                <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 30px; height: 30px; padding: 0;" aria-label="<?= $act === 'plus' ? 'Ajouter un' : 'Retirer un' ?> exemplaire"><i class="bi bi-<?= $icone ?>"></i></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <form method="POST" data-confirmer="Retirer cette offre de la vente ? Son stock passe à 0 (<?= (int) $ex['quantite'] ?> ex.), elle reste dans l'historique." data-bouton-confirmer="Retirer de la vente">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="action" value="vider">
                                        <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Retirer de la vente"><i class="bi bi-box-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Retirer de la vente</span></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($offresEpuisees): ?>
            <div class="carte">
                <div class="carte-entete">Offres épuisées <span class="text-muted small fw-normal">Conservées pour l'historique des commandes</span></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-uppercase"><tr><th>État</th><th>Prix</th><th class="text-center">Vendus</th><th class="text-center">Remettre en stock</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($offresEpuisees as $ex): ?>
                                <tr class="text-muted">
                                    <td><span class="badge bg-light text-secondary border"><?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? ucfirst($ex['etat'])) ?></span></td>
                                    <td><?= formaterPrix($ex['prix']) ?></td>
                                    <td class="text-center"><?= (int) $ex['nb_vendus'] ?></td>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline">
                                            <?= champCsrf() ?>
                                            <input type="hidden" name="action" value="plus">
                                            <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                            <button class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-plus" aria-hidden="true"></i> 1 exemplaire</button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <?php if ((int) $ex['nb_lignes_commande'] === 0): ?>
                                            <form method="POST" data-confirmer="Supprimer définitivement cette offre jamais vendue ?" data-bouton-confirmer="Supprimer">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" title="Supprimer l'offre"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Supprimer l'offre</span></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="small" title="Cette offre figure dans des commandes"><i class="bi bi-lock" aria-hidden="true"></i> Conservée</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
