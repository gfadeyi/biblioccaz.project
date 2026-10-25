<?php
require_once 'config.php';
require_once 'categories_lib.php';

const LIVRES_PAR_PAGE = 12;

$preset = $preset ?? [];
$defauts = $defauts ?? [];
$titrePage = $titrePage ?? 'Catalogue';
$sousTitre = $sousTitre ?? '';

$f = array_merge([
    'q' => '', 'categorie' => '', 'tag' => '', 'etat' => '',
    'prix_min' => '', 'prix_max' => '', 'tri' => 'recent', 'stock_bas' => '', 'note_min' => '',
], $defauts, array_intersect_key($_GET, array_flip(['q', 'categorie', 'tag', 'etat', 'prix_min', 'prix_max', 'tri', 'note_min'])), $preset);

$f = array_map(fn($v) => is_string($v) ? trim($v) : $v, $f);
$page = max(1, (int) ($_GET['page'] ?? 1));

$etatsPossibles = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'État usé'];
$trisPossibles = [
    'recent'    => ['Nouveautés', 'l.id_livre DESC'],
    'prix_asc'  => ['Prix croissant', 'prix ASC, l.titre ASC'],
    'prix_desc' => ['Prix décroissant', 'prix DESC, l.titre ASC'],
    'titre'     => ['Titre (A → Z)', 'l.titre ASC'],
    'note'      => ['Mieux notés', 'note_moyenne IS NULL, note_moyenne DESC, nb_avis DESC'],
    'ventes'    => ['Meilleures ventes', '(SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_livre = l.id_livre) DESC, l.id_livre DESC'],
];
if (!isset($trisPossibles[$f['tri']])) { $f['tri'] = 'recent'; }

$where = ["l.is_valide = 1"];
$having = [];
$params = [];

if ($f['q'] !== '') {
    $where[] = "(l.titre LIKE :q1 OR l.auteur LIKE :q2 OR l.isbn LIKE :q3 OR l.editeur LIKE :q4)";
    foreach (['q1', 'q2', 'q3', 'q4'] as $k) { $params[$k] = '%' . $f['q'] . '%'; }
}
if ($f['categorie'] !== '') {
    $where[] = "c.slug = :categorie";
    $params['categorie'] = $f['categorie'];
}
if ($f['tag'] !== '') {
    $where[] = "EXISTS (SELECT 1 FROM livre_tag lt INNER JOIN tag t ON t.id_tag = lt.id_tag
                        WHERE lt.id_livre = l.id_livre AND t.slug = :tag)";
    $params['tag'] = $f['tag'];
}
if (isset($etatsPossibles[$f['etat']])) {
    $where[] = "e.etat = :etat";
    $params['etat'] = $f['etat'];
}
if ($f['prix_min'] !== '' && is_numeric($f['prix_min'])) {
    $having[] = "MIN(e.prix) >= :prix_min";
    $params['prix_min'] = (float) $f['prix_min'];
}
if ($f['prix_max'] !== '' && is_numeric($f['prix_max'])) {
    $having[] = "MIN(e.prix) <= :prix_max";
    $params['prix_max'] = (float) $f['prix_max'];
}
if (in_array($f['note_min'], ['3', '4', '4.5'], true)) {
    $where[] = "av.moyenne >= :note_min";
    $params['note_min'] = (float) $f['note_min'];
}
if (!empty($f['stock_bas'])) {
    $having[] = "SUM(e.quantite) <= 2";
}

$sqlBase = "
    FROM livre l
    INNER JOIN exemplaire e ON e.id_livre = l.id_livre AND e.quantite > 0 AND e.is_disponible = 1
    LEFT JOIN categorie c ON c.id_categorie = l.id_categorie
    LEFT JOIN (SELECT id_livre, AVG(note) AS moyenne, COUNT(*) AS nb
               FROM avis WHERE statut = 'visible' GROUP BY id_livre) av ON av.id_livre = l.id_livre
    WHERE " . implode(' AND ', $where) . "
    GROUP BY l.id_livre, l.titre, l.auteur, l.couverture, c.nom, c.slug, av.moyenne, av.nb
    " . ($having ? "HAVING " . implode(' AND ', $having) : "");

$stmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT l.id_livre $sqlBase) AS resultats");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$nbPages = max(1, (int) ceil($total / LIVRES_PAR_PAGE));
$page = min($page, $nbPages);
$offset = ($page - 1) * LIVRES_PAR_PAGE;

$stmt = $pdo->prepare("
    SELECT l.id_livre, l.titre, l.auteur, l.couverture,
           c.nom AS categorie_nom, c.slug AS categorie_slug,
           MIN(e.prix) AS prix,
           SUBSTRING_INDEX(GROUP_CONCAT(e.etat ORDER BY e.prix ASC), ',', 1) AS etat,
           SUM(e.quantite) AS stock,
           av.moyenne AS note_moyenne, COALESCE(av.nb, 0) AS nb_avis
    $sqlBase
    ORDER BY {$trisPossibles[$f['tri']][1]}
    LIMIT " . LIVRES_PAR_PAGE . " OFFSET $offset");
$stmt->execute($params);
$livres = $stmt->fetchAll();

$categories = listerCategories($pdo);
$tags = listerTags($pdo);

function lienFiltres(array $f, array $preset, array $changements): string
{
    $p = array_merge($f, $changements);
    foreach ($preset as $cle => $v) { unset($p[$cle]); }
    $p = array_filter($p, fn($v) => $v !== '' && $v !== null);
    return basename($_SERVER['PHP_SELF']) . ($p ? '?' . http_build_query($p) : '');
}

include 'header.php';
require_once 'avis_lib.php';
echo etoilesStyle();
?>

<style>
    .catalogue-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 20px; }
    .catalogue-grid .book-card { transition: transform 0.3s ease; }
    .catalogue-grid .book-card:hover { transform: translateY(-5px); }
    .card-img-container { background: #f8f9fa; height: 220px; display: flex; align-items: center; justify-content: center; padding: 15px; }
    .line-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 1.05rem; }
    .filtres-box { background: white; border-radius: 15px; border: 1px solid #eee; }
    .filtres-box .form-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #274e13; letter-spacing: .03em; }
    .tag-filtre { font-size: 0.8rem; }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="color:#274e13;"><?= htmlspecialchars($titrePage) ?></h1>
            <p class="text-muted mb-0">
                <?= $sousTitre ? htmlspecialchars($sousTitre) . ' · ' : '' ?>
                <?= $total ?> livre<?= $total > 1 ? 's' : '' ?> disponible<?= $total > 1 ? 's' : '' ?>
                <?= $f['q'] !== '' ? ' pour « ' . htmlspecialchars($f['q']) . ' »' : '' ?>
            </p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <?php foreach (['q', 'categorie', 'tag', 'etat', 'prix_min', 'prix_max', 'note_min'] as $k): ?>
                <?php if ($f[$k] !== '' && !isset($preset[$k])): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($f[$k]) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <label class="small text-muted text-nowrap">Trier par</label>
            <select name="tri" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                <?php foreach ($trisPossibles as $cle => [$libelle]): ?>
                    <option value="<?= $cle ?>" <?= $f['tri'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <form method="GET" class="filtres-box p-3 shadow-sm">
                <input type="hidden" name="tri" value="<?= htmlspecialchars($f['tri']) ?>">

                <div class="mb-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Titre, auteur, ISBN..." value="<?= htmlspecialchars($f['q']) ?>">
                </div>

                <?php if (!isset($preset['categorie'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select name="categorie" class="form-select form-select-sm">
                            <option value="">Toutes</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $f['categorie'] === $c['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">État</label>
                    <select name="etat" class="form-select form-select-sm">
                        <option value="">Tous les états</option>
                        <?php foreach ($etatsPossibles as $cle => $libelle): ?>
                            <option value="<?= $cle ?>" <?= $f['etat'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!isset($preset['prix_max'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Prix (€)</label>
                        <div class="d-flex gap-2">
                            <input type="number" name="prix_min" min="0" step="0.5" class="form-control form-control-sm" placeholder="Min" value="<?= htmlspecialchars($f['prix_min']) ?>">
                            <input type="number" name="prix_max" min="0" step="0.5" class="form-control form-control-sm" placeholder="Max" value="<?= htmlspecialchars($f['prix_max']) ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Note des lecteurs</label>
                    <select name="note_min" class="form-select form-select-sm">
                        <option value="">Toutes les notes</option>
                        <?php foreach (['4.5' => '4,5 ★ et plus', '4' => '4 ★ et plus', '3' => '3 ★ et plus'] as $v => $libelle): ?>
                            <option value="<?= $v ?>" <?= $f['note_min'] === $v ? 'selected' : '' ?>><?= $libelle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($tags): ?>
                    <div class="mb-3">
                        <label class="form-label d-block">Thème</label>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($tags as $t): ?>
                                <?php $actif = $f['tag'] === $t['slug']; ?>
                                <a href="<?= htmlspecialchars(lienFiltres($f, $preset, ['tag' => $actif ? '' : $t['slug'], 'page' => ''])) ?>"
                                   class="btn btn-sm rounded-pill tag-filtre <?= $actif ? 'btn-success' : 'btn-outline-success' ?>">
                                    <?= htmlspecialchars($t['nom']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($f['tag'] !== ''): ?>
                            <input type="hidden" name="tag" value="<?= htmlspecialchars($f['tag']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-success w-100 rounded-pill btn-sm fw-bold">Filtrer</button>
                <a href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>" class="btn btn-link w-100 btn-sm text-muted">Réinitialiser</a>
            </form>
        </div>

        <div class="col-lg-9">
            <?php if (!$livres): ?>
                <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
                    <i class="bi bi-book text-muted fs-1 mb-3"></i>
                    <h5 class="text-secondary">Aucun livre ne correspond à ces critères.</h5>
                    <a href="catalogue.php" class="btn btn-success rounded-pill px-4 mt-3 mx-auto">Voir tout le catalogue</a>
                </div>
            <?php else: ?>
                <div class="catalogue-grid">
                    <?php foreach ($livres as $l): include 'partie_carte_livre.php'; endforeach; ?>
                </div>

                <?php if ($nbPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($p = 1; $p <= $nbPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars(lienFiltres($f, $preset, ['page' => $p > 1 ? $p : ''])) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
