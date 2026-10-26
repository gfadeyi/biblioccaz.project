<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../categories_lib.php';
require_once __DIR__ . '/../livres_lib.php';
exigerDroit('catalogue');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('inventaire.php');
    if (($_POST['action'] ?? '') === 'supprimer' && aLeDroit('admin')) {
        $id = (int) ($_POST['id_livre'] ?? 0);
        $stmt = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ?");
        $stmt->execute([$id]);
        $titre = $stmt->fetchColumn();
        if ($titre !== false) {
            $pdo->prepare("DELETE FROM livre WHERE id_livre = ?")->execute([$id]);
            insertLog('CATALOGUE', "Suppression du livre #$id : $titre");
            rediriger('inventaire.php', 'success', "« $titre » a été supprimé du catalogue.");
        }
    }
    rediriger('inventaire.php');
}

const ETATS_STOCK = ['neuf' => ['N', 'Neuf'], 'tres bon' => ['TB', 'Très bon état'], 'bon' => ['B', 'Bon état'], 'use' => ['U', 'État usé']];
const SEUIL_STOCK_BAS = 2;
const TRIS_INVENTAIRE = [
    'recent' => 'l.id_livre',
    'titre'  => 'l.titre',
    'stock'  => 'nb_stock',
    'prix'   => 'MIN(e.prix) IS NULL, MIN(e.prix)',
];

$q = trim($_GET['q'] ?? '');
$filtreStock = $_GET['stock'] ?? '';
$filtreCategorie = $_GET['categorie'] ?? '';
$tri = array_key_exists($_GET['tri'] ?? '', TRIS_INVENTAIRE) ? $_GET['tri'] : 'recent';
$sens = ($_GET['sens'] ?? ($tri === 'recent' ? 'desc' : 'asc')) === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(l.titre LIKE ? OR l.auteur LIKE ? OR l.isbn LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($filtreCategorie !== '') {
    $idsCategorie = idsCategorieEtEnfants($pdo, $filtreCategorie);
    $where[] = "l.id_categorie IN (" . implode(', ', array_fill(0, count($idsCategorie), '?')) . ")";
    array_push($params, ...$idsCategorie);
}
$having = match ($filtreStock) {
    'rupture' => 'HAVING nb_stock = 0',
    'bas'     => 'HAVING nb_stock BETWEEN 1 AND ' . SEUIL_STOCK_BAS,
    'dispo'   => 'HAVING nb_stock > 0',
    default   => '',
};

$colonnesEtats = implode(",\n           ", array_map(
    fn($etat, $index) => "COALESCE(SUM(CASE WHEN e.etat = '" . $etat . "' THEN e.quantite END), 0) AS stock_$index",
    array_keys(ETATS_STOCK), array_keys(array_keys(ETATS_STOCK))
));

$stmt = $pdo->prepare("
    SELECT l.id_livre, l.titre, l.auteur, l.isbn, l.couverture, l.is_valide,
           c.nom AS categorie, u.nom AS univers,
           COALESCE(SUM(e.quantite), 0) AS nb_stock, MIN(e.prix) AS prix_min,
           $colonnesEtats,
           (SELECT COUNT(*) FROM alerte_stock a WHERE a.id_livre = l.id_livre AND a.date_envoi IS NULL) AS nb_alertes
    FROM livre l
    LEFT JOIN exemplaire e ON e.id_livre = l.id_livre AND e.quantite > 0 AND " . conditionExemplaireMagasin() . "
    LEFT JOIN categorie c ON c.id_categorie = l.id_categorie
    LEFT JOIN categorie u ON u.id_categorie = c.id_parent
    " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    GROUP BY l.id_livre, l.titre, l.auteur, l.isbn, l.couverture, l.is_valide, c.nom, u.nom
    $having
    ORDER BY " . TRIS_INVENTAIRE[$tri] . " $sens, l.id_livre DESC");
$stmt->execute($params);
$livres = $stmt->fetchAll();

$totaux = ['rupture' => 0, 'bas' => 0];
foreach ($livres as $livre) {
    if ((int) $livre['nb_stock'] === 0) {
        $totaux['rupture']++;
    } elseif ($livre['nb_stock'] <= SEUIL_STOCK_BAS) {
        $totaux['bas']++;
    }
}
$filtresActifs = $q !== '' || $filtreStock !== '' || $filtreCategorie !== '';

function lienTri(string $colonne, string $libelle, string $triActuel, string $sensActuel): string
{
    $sensSuivant = $triActuel === $colonne && $sensActuel === 'ASC' ? 'desc' : 'asc';
    $parametres = array_filter(array_merge($_GET, ['tri' => $colonne, 'sens' => $sensSuivant]), fn($v) => $v !== '');
    $icone = $triActuel === $colonne ? ($sensActuel === 'ASC' ? 'sort-up' : 'sort-down') : 'arrow-down-up';
    return '<a href="inventaire.php?' . e(http_build_query($parametres)) . '" class="th-tri ' . ($triActuel === $colonne ? 'actif' : '') . '">'
        . e($libelle) . ' <i class="bi bi-' . $icone . '" aria-hidden="true"></i></a>';
}

$titrePage = 'Inventaire & stock';
$sousTitrePage = 'Les titres du catalogue, leur stock par état et leurs prix.';
$pageActive = 'inventaire';
$actionsPage = [['livre.php', 'plus-lg', 'Nouveau titre', 'btn-vert']];
include __DIR__ . '/_haut.php';
?>

<div class="carte">
    <form class="barre-filtres-admin" method="GET" role="search" aria-label="Filtrer l'inventaire">
        <div class="champ-recherche">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Titre, auteur ou ISBN" aria-label="Rechercher">
        </div>
        <select name="categorie" class="form-select" aria-label="Univers ou catégorie" onchange="this.form.submit()">
            <option value="">Tous les univers</option>
            <?= optionsCategories(arbreCategories($pdo), $filtreCategorie, 'slug', true) ?>
        </select>
        <select name="stock" class="form-select" aria-label="État du stock" onchange="this.form.submit()">
            <option value="">Tous les stocks</option>
            <option value="dispo" <?= $filtreStock === 'dispo' ? 'selected' : '' ?>>En stock</option>
            <option value="bas" <?= $filtreStock === 'bas' ? 'selected' : '' ?>>Stock bas (1 à <?= SEUIL_STOCK_BAS ?>)</option>
            <option value="rupture" <?= $filtreStock === 'rupture' ? 'selected' : '' ?>>En rupture</option>
        </select>
        <input type="hidden" name="tri" value="<?= e($tri) ?>">
        <input type="hidden" name="sens" value="<?= strtolower($sens) ?>">
        <button class="btn btn-outline-secondary">Rechercher</button>
        <?php if ($filtresActifs): ?><a href="inventaire.php" class="btn btn-link">Effacer les filtres</a><?php endif; ?>
        <span class="ms-auto d-flex flex-wrap gap-2">
            <span class="etiquette etiquette-neutre"><?= count($livres) ?> titre<?= count($livres) > 1 ? 's' : '' ?></span>
            <?php if ($totaux['bas'] > 0): ?><span class="etiquette"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><?= $totaux['bas'] ?> stock bas</span><?php endif; ?>
            <?php if ($totaux['rupture'] > 0): ?><span class="etiquette etiquette-alerte"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><?= $totaux['rupture'] ?> en rupture</span><?php endif; ?>
        </span>
    </form>

    <?php if (!$livres): ?>
        <div class="etat-vide">
            <div class="icone-vide"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
            <strong><?= $filtresActifs ? 'Aucun livre ne correspond à ces filtres' : 'Le catalogue est vide' ?></strong>
            <?= $filtresActifs ? '<a href="inventaire.php">Effacer les filtres</a>' : '<a href="livre.php">Ajouter un premier titre</a>' ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-cartes">
                <thead>
                    <tr>
                        <th scope="col"><?= lienTri('titre', 'Livre', $tri, $sens) ?></th>
                        <th scope="col">Univers / catégorie</th>
                        <th scope="col">Stock par état</th>
                        <th scope="col" class="text-center"><?= lienTri('stock', 'Total', $tri, $sens) ?></th>
                        <th scope="col" class="text-end"><?= lienTri('prix', 'Prix à partir de', $tri, $sens) ?></th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($livres as $l): ?>
                        <?php
                        $stockTotal = (int) $l['nb_stock'];
                        $classeLigne = $stockTotal === 0 ? 'ligne-rupture' : ($stockTotal <= SEUIL_STOCK_BAS ? 'ligne-stock-bas' : '');
                        ?>
                        <tr class="<?= $classeLigne ?>">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= imageLivre($l['couverture']) ?>" alt="" class="couverture-mini" loading="lazy">
                                    <div>
                                        <div class="titre-livre"><?= e($l['titre']) ?></div>
                                        <div class="auteur-livre"><?= e($l['auteur']) ?></div>
                                        <div class="small text-muted">#<?= $l['id_livre'] ?><?= $l['isbn'] ? ' · ISBN ' . e($l['isbn']) : '' ?>
                                            <?php if (!$l['is_valide']): ?><span class="etiquette etiquette-neutre ms-1">À valider</span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-libelle="Univers / catégorie">
                                <?php if ($l['categorie']): ?>
                                    <div class="small text-muted"><?= e($l['univers'] ?? '') ?></div>
                                    <div class="fw-semibold"><?= e($l['categorie']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Non classé</span>
                                <?php endif; ?>
                            </td>
                            <td data-libelle="Stock par état">
                                <span class="stock-etats">
                                    <?php foreach (array_values(ETATS_STOCK) as $index => [$abreviation, $libelleEtat]): ?>
                                        <?php $quantiteEtat = (int) $l['stock_' . $index]; ?>
                                        <span class="pastille-etat <?= $quantiteEtat === 0 ? 'vide' : '' ?>" data-bs-toggle="tooltip" title="<?= e($libelleEtat) ?> : <?= $quantiteEtat ?> exemplaire<?= $quantiteEtat > 1 ? 's' : '' ?>"><?= $abreviation ?> <?= $quantiteEtat ?></span>
                                    <?php endforeach; ?>
                                </span>
                            </td>
                            <td class="text-center" data-libelle="Total">
                                <span class="total-stock"><?= $stockTotal ?></span>
                                <?php if ($stockTotal === 0): ?>
                                    <div><span class="etiquette etiquette-alerte">Rupture</span></div>
                                    <?php if ($l['nb_alertes'] > 0): ?><div class="small text-muted mt-1"><i class="bi bi-bell" aria-hidden="true"></i> <?= (int) $l['nb_alertes'] ?> client<?= $l['nb_alertes'] > 1 ? 's' : '' ?> en attente</div><?php endif; ?>
                                <?php elseif ($stockTotal <= SEUIL_STOCK_BAS): ?>
                                    <div><span class="etiquette">Stock bas</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end prix-cellule" data-libelle="Prix à partir de"><?= $l['prix_min'] !== null ? formaterPrix($l['prix_min']) : '<span class="text-muted fw-normal">—</span>' ?></td>
                            <td class="text-end cellule-actions">
                                <div class="actions-ligne">
                                    <a href="exemplaires.php?id=<?= $l['id_livre'] ?>" class="btn btn-vert bouton-action-texte" title="Ajouter ou retirer des exemplaires"><i class="bi bi-box-seam" aria-hidden="true"></i> Stock</a>
                                    <a href="livre.php?id=<?= $l['id_livre'] ?>" class="btn btn-outline-secondary bouton-action" title="Modifier la fiche"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <a href="../detail_livre.php?id=<?= $l['id_livre'] ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary bouton-action" title="Voir sur le site"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                    <?php if (aLeDroit('admin')): ?>
                                        <form method="POST" data-confirmer="Supprimer définitivement « <?= e($l['titre']) ?> » et tout son stock ? Cette action est irréversible." data-bouton-confirmer="Supprimer">
                                            <?= champCsrf() ?>
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id_livre" value="<?= $l['id_livre'] ?>">
                                            <button class="btn btn-outline-danger bouton-action" title="Supprimer le livre"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
