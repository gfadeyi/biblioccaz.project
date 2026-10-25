<?php
// Inventaire : tous les titres avec leur stock, recherche et suppression.
require_once __DIR__ . '/_init.php';
exigerDroit('catalogue');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('inventaire.php');
    if (($_POST['action'] ?? '') === 'supprimer' && aLeDroit('admin')) {
        $id = (int) ($_POST['id_livre'] ?? 0);
        $stmt = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ?");
        $stmt->execute([$id]);
        $titre = $stmt->fetchColumn();
        if ($titre !== false) {
            // Exemplaires, avis et tags partent avec (ON DELETE CASCADE) ; les commandes gardent leur copie du titre
            $pdo->prepare("DELETE FROM livre WHERE id_livre = ?")->execute([$id]);
            insertLog('CATALOGUE', "Suppression du livre #$id : $titre");
            rediriger('inventaire.php', 'success', "« $titre » a été supprimé du catalogue.");
        }
    }
    rediriger('inventaire.php');
}

$q = trim($_GET['q'] ?? '');
$filtreStock = $_GET['stock'] ?? '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(l.titre LIKE ? OR l.auteur LIKE ? OR l.isbn LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$having = match ($filtreStock) {
    'rupture' => 'HAVING nb_stock = 0',
    'bas'     => 'HAVING nb_stock BETWEEN 1 AND 2',
    default   => '',
};

$stmt = $pdo->prepare("
    SELECT l.id_livre, l.titre, l.auteur, l.couverture, l.is_valide, c.nom AS categorie,
           COALESCE(SUM(e.quantite), 0) AS nb_stock, MIN(e.prix) AS prix_min
    FROM livre l
    LEFT JOIN exemplaire e ON e.id_livre = l.id_livre AND e.quantite > 0
    LEFT JOIN categorie c ON c.id_categorie = l.id_categorie
    " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    GROUP BY l.id_livre, l.titre, l.auteur, l.couverture, l.is_valide, c.nom
    $having
    ORDER BY l.id_livre DESC");
$stmt->execute($params);
$livres = $stmt->fetchAll();

$titrePage = 'Inventaire & stock';
$pageActive = 'inventaire';
include __DIR__ . '/_haut.php';
?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET">
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" style="max-width: 320px;" placeholder="Titre, auteur ou ISBN">
        <select name="stock" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
            <option value="">Tous les stocks</option>
            <option value="rupture" <?= $filtreStock === 'rupture' ? 'selected' : '' ?>>En rupture</option>
            <option value="bas" <?= $filtreStock === 'bas' ? 'selected' : '' ?>>Stock bas (1-2)</option>
        </select>
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        <?php if ($q !== '' || $filtreStock !== ''): ?><a href="inventaire.php" class="btn btn-link">Effacer</a><?php endif; ?>
    </form>
    <a href="livre.php" class="btn btn-vert"><i class="bi bi-plus-circle me-1"></i> Nouveau titre</a>
</div>

<div class="carte">
    <div class="carte-entete"><?= count($livres) ?> titre<?= count($livres) > 1 ? 's' : '' ?></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase">
                <tr><th>Livre</th><th>Catégorie</th><th class="text-center">Stock</th><th class="text-end">Dès</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php if (!$livres): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun livre ne correspond.</td></tr>
                <?php endif; ?>
                <?php foreach ($livres as $l): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= imageLivre($l['couverture']) ?>" alt="" class="couverture-mini">
                                <div>
                                    <div class="fw-bold"><?= e($l['titre']) ?></div>
                                    <div class="small text-muted"><?= e($l['auteur']) ?> · #<?= $l['id_livre'] ?>
                                        <?php if (!$l['is_valide']): ?><span class="badge bg-warning text-dark ms-1">À valider</span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= $l['categorie'] ? e($l['categorie']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?= $l['nb_stock'] > 2 ? 'bg-success' : ($l['nb_stock'] > 0 ? 'bg-warning text-dark' : 'bg-danger') ?>"><?= (int) $l['nb_stock'] ?> ex.</span>
                        </td>
                        <td class="text-end small"><?= $l['prix_min'] !== null ? number_format($l['prix_min'], 2, ',', ' ') . ' €' : '—' ?></td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="exemplaires.php?id=<?= $l['id_livre'] ?>" class="btn btn-sm btn-outline-success" title="Gérer le stock"><i class="bi bi-box-seam"></i> Stock</a>
                                <a href="livre.php?id=<?= $l['id_livre'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier la fiche"><i class="bi bi-pencil"></i></a>
                                <a href="../detail_livre.php?id=<?= $l['id_livre'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir sur le site"><i class="bi bi-eye"></i></a>
                                <?php if (aLeDroit('admin')): ?>
                                    <form method="POST" data-confirmer="Supprimer définitivement « <?= e($l['titre']) ?> » et tout son stock ?">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
