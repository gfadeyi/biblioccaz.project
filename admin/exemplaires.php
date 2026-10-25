<?php
// Stock d'un livre : exemplaires par état et prix (ajout, +1 / -1, suppression).
require_once __DIR__ . '/_init.php';
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
        $pdo->prepare("INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite, id_user) VALUES (?, ?, ?, 'vente', 1, ?, ?)")
            ->execute([$idLivre, $etat, $prix, $quantite, $_SESSION['user_id']]);
        insertLog('STOCK', "Ajout de $quantite ex. ($etat, $prix €) pour le livre #$idLivre");
        rediriger($retour, 'success', "$quantite exemplaire" . ($quantite > 1 ? 's ajoutés' : ' ajouté') . " au stock.");
    }

    // Les actions sur un exemplaire vérifient qu'il appartient bien à ce livre
    if ($action === 'plus') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite + 1 WHERE id_exemplaire = ? AND id_livre = ?")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Stock +1 pour l'exemplaire #$idEx");
    } elseif ($action === 'moins') {
        $pdo->prepare("UPDATE exemplaire SET quantite = quantite - 1 WHERE id_exemplaire = ? AND id_livre = ? AND quantite > 0")->execute([$idEx, $idLivre]);
        // Une offre vide disparaît (uniquement celle-ci, pas celles des autres livres)
        $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ? AND id_livre = ? AND quantite <= 0")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Stock -1 pour l'exemplaire #$idEx");
    } elseif ($action === 'supprimer') {
        $pdo->prepare("DELETE FROM exemplaire WHERE id_exemplaire = ? AND id_livre = ?")->execute([$idEx, $idLivre]);
        insertLog('STOCK', "Suppression de l'offre exemplaire #$idEx");
        rediriger($retour, 'success', "Offre supprimée.");
    }
    rediriger($retour);
}

$stmt = $pdo->prepare("SELECT * FROM exemplaire WHERE id_livre = ? AND quantite > 0 ORDER BY prix");
$stmt->execute([$idLivre]);
$exemplaires = $stmt->fetchAll();
$total = array_sum(array_column($exemplaires, 'quantite'));

$titrePage = 'Stock : ' . $livre['titre'];
$pageActive = 'inventaire';
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
        <div class="carte">
            <div class="carte-entete">Offres en vente</div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light small text-uppercase"><tr><th>État</th><th>Prix</th><th class="text-center">Quantité</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php if (!$exemplaires): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucun exemplaire en stock.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($exemplaires as $ex): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= e(ETATS_EXEMPLAIRE[$ex['etat']] ?? ucfirst($ex['etat'])) ?></span></td>
                                <td class="fw-bold text-success"><?= number_format($ex['prix'], 2, ',', ' ') ?> €</td>
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
                                    <form method="POST" data-confirmer="Supprimer cette offre (<?= $ex['quantite'] ?> ex.) ?">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id_exemplaire" value="<?= $ex['id_exemplaire'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Supprimer l'offre"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
