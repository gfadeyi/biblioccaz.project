<?php
require_once 'config.php';
require_once 'categories_lib.php';
require_once 'livres_lib.php';
require_once 'recherche_lib.php';
require_once 'recommandations_lib.php';

redirigerAnciennesAdresses();

const LIVRES_PAR_PAGE = 12;

$preset = $preset ?? [];
$defauts = $defauts ?? [];
$titrePage = $titrePage ?? 'Catalogue';
$sousTitre = $sousTitre ?? '';

$f = array_merge([
    'q' => '', 'categorie' => '', 'tag' => '', 'etat' => '', 'offre' => '',
    'prix_min' => '', 'prix_max' => '', 'tri' => '', 'stock_bas' => '', 'note_min' => '', 'niveau' => '', 'matiere' => '',
], $defauts, array_intersect_key($_GET, array_flip(['q', 'categorie', 'tag', 'etat', 'offre', 'prix_min', 'prix_max', 'tri', 'note_min', 'niveau', 'matiere'])), $preset);

foreach ($f as $cle => $valeur) {
    if ($cle !== 'tag') { $f[$cle] = texteRecherche(is_int($valeur) ? (string) $valeur : $valeur); }
}
$slugsTags = is_array($f['tag']) ? $f['tag'] : explode(',', (string) $f['tag']);
$f['tag'] = array_slice(array_values(array_unique(array_filter(
    array_map(fn($slug) => is_string($slug) ? trim($slug) : '', $slugsTags),
    fn($slug) => preg_match('/^[a-z0-9-]{1,100}$/', $slug) === 1
))), 0, 8);
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;

$etatsPossibles = ['neuf' => 'Neuf', 'tres bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'État usé'];
$trisPossibles = [
    'pertinence' => ['Pertinence', 'pertinence DESC, l.id_livre DESC'],
    'recent'    => ['Nouveautés', 'l.id_livre DESC'],
    'prix_asc'  => ['Prix croissant', 'prix ASC, l.titre ASC'],
    'prix_desc' => ['Prix décroissant', 'prix DESC, l.titre ASC'],
    'titre'     => ['Titre (A → Z)', 'l.titre ASC'],
    'note'      => ['Mieux notés', 'note_moyenne IS NULL, note_moyenne DESC, nb_avis DESC'],
    'ventes'    => ['Meilleures ventes', '(SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_livre = l.id_livre) DESC, l.id_livre DESC'],
];
if (!isset($trisPossibles[$f['tri']])) { $f['tri'] = ''; }
$triActif = $f['tri'] !== '' ? $f['tri'] : ($f['q'] !== '' ? 'pertinence' : 'recent');

$where = ["l.is_valide = 1"];
$having = [];
$params = [];

foreach (['niveau' => 'niveau_scolaire', 'matiere' => 'matiere'] as $cle => $colonne) {
    if ($f[$cle] !== '') {
        $where[] = "l.$colonne = :$cle";
        $params[$cle] = $f[$cle];
    }
}

if ($f['q'] !== '') {
    $rechercheIsbn = isbnRecherche($f['q']);
    if ($rechercheIsbn !== '') {
        $where[] = "REPLACE(REPLACE(UPPER(l.isbn), '-', ''), ' ', '') LIKE :isbn_prefixe ESCAPE '!'";
        $params['isbn_prefixe'] = $rechercheIsbn . '%';
    } else {
        $where[] = "(l.titre LIKE :q1 ESCAPE '!' OR l.auteur LIKE :q2 ESCAPE '!' OR l.isbn LIKE :q3 ESCAPE '!' OR l.editeur LIKE :q4 ESCAPE '!')";
        foreach (['q1', 'q2', 'q3', 'q4'] as $k) { $params[$k] = motifRecherche($f['q']); }
    }
}
if ($f['categorie'] !== '') {
    $marqueursCategorie = [];
    foreach (idsCategorieEtEnfants($pdo, $f['categorie']) as $index => $idCategorie) {
        $marqueursCategorie[] = ':categorie' . $index;
        $params['categorie' . $index] = $idCategorie;
    }
    $where[] = "l.id_categorie IN (" . implode(', ', $marqueursCategorie) . ")";
}
foreach ($f['tag'] as $index => $slugTag) {
    $where[] = "EXISTS (SELECT 1 FROM livre_tag lt INNER JOIN tag t ON t.id_tag = lt.id_tag
                        WHERE lt.id_livre = l.id_livre AND t.slug = :tag$index)";
    $params['tag' . $index] = $slugTag;
}
if (isset($etatsPossibles[$f['etat']])) {
    $where[] = "e.etat = :etat";
    $params['etat'] = $f['etat'];
}
$originesOffre = isset(ORIGINES_OFFRE[$f['offre']]) ? [$f['offre']] : [];
$prixLivre = "COALESCE(MIN(CASE WHEN e.origine IN ('magasin', 'particulier') THEN e.prix END), 0)";
if ($f['prix_min'] !== '' && is_numeric($f['prix_min'])) {
    $having[] = "$prixLivre >= :prix_min";
    $params['prix_min'] = (float) $f['prix_min'];
}
if ($f['prix_max'] !== '' && is_numeric($f['prix_max'])) {
    $having[] = "$prixLivre <= :prix_max";
    $params['prix_max'] = (float) $f['prix_max'];
}
if (in_array($f['note_min'], ['3', '4', '4.5'], true)) {
    $where[] = "av.moyenne >= :note_min";
    $params['note_min'] = (float) $f['note_min'];
}
if (!empty($f['stock_bas'])) {
    $having[] = "SUM(CASE WHEN e.origine = 'magasin' THEN e.quantite END) <= 2";
}

$paramsTri = [];
$selectPertinence = '';
if ($triActif === 'pertinence') {
    $termesPertinence = [
        "LEAST(10, 2 * (SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_livre = l.id_livre))",
        "2 * COALESCE(av.moyenne, 0)",
    ];
    if ($f['q'] !== '' && isbnRecherche($f['q']) === '') {
        $termesPertinence[] = "CASE WHEN l.titre LIKE :pertinence_debut ESCAPE '!' THEN 40 WHEN l.titre LIKE :pertinence_titre ESCAPE '!' THEN 30
                                    WHEN l.auteur LIKE :pertinence_auteur ESCAPE '!' THEN 20 ELSE 0 END";
        $paramsTri['pertinence_debut'] = substr(motifRecherche($f['q']), 1);
        $paramsTri['pertinence_titre'] = motifRecherche($f['q']);
        $paramsTri['pertinence_auteur'] = motifRecherche($f['q']);
    }
    $idLecteurPertinence = in_array($_SESSION['role'] ?? '', ROLES_SUGGESTIONS, true) ? (int) ($_SESSION['user_id'] ?? 0) : 0;
    if ($idLecteurPertinence) {
        $marqueursPertinence = [];
        foreach (categoriesPertinence($pdo, $idLecteurPertinence) as $index => $idCategorie) {
            $marqueursPertinence[] = ':pertinence_categorie' . $index;
            $paramsTri['pertinence_categorie' . $index] = $idCategorie;
        }
        if ($marqueursPertinence) {
            $termesPertinence[] = "CASE WHEN l.id_categorie IN (" . implode(', ', $marqueursPertinence) . ") THEN 15 ELSE 0 END";
        }
        $marqueursPertinence = [];
        foreach (preferencesLecteur($pdo, $idLecteurPertinence)['tags'] as $index => $idTag) {
            $marqueursPertinence[] = ':pertinence_tag' . $index;
            $paramsTri['pertinence_tag' . $index] = $idTag;
        }
        if ($marqueursPertinence) {
            $termesPertinence[] = "CASE WHEN EXISTS (SELECT 1 FROM livre_tag lp WHERE lp.id_livre = l.id_livre AND lp.id_tag IN (" . implode(', ', $marqueursPertinence) . ")) THEN 8 ELSE 0 END";
        }
    }
    $selectPertinence = ",\n           (" . implode(' + ', $termesPertinence) . ") AS pertinence";
}

$sqlBase = "
    FROM livre l
    INNER JOIN " . sqlOffres($originesOffre) . " e ON e.id_livre = l.id_livre
    LEFT JOIN categorie c ON c.id_categorie = l.id_categorie
    LEFT JOIN (SELECT id_livre, AVG(note) AS moyenne, COUNT(*) AS nb
               FROM avis WHERE statut = 'visible' GROUP BY id_livre) av ON av.id_livre = l.id_livre
    WHERE " . implode(' AND ', $where) . "
    GROUP BY l.id_livre, l.titre, l.auteur, l.couverture, c.nom, c.slug, av.moyenne, av.nb
    " . ($having ? "HAVING " . implode(' AND ', $having) : "");

$stmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT l.id_livre $sqlBase) AS resultats");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

if (isset($_GET['compter'])) {
    header('Content-Type: application/json');
    echo json_encode(['total' => $total]);
    exit;
}

$nbPages = max(1, (int) ceil($total / LIVRES_PAR_PAGE));
$page = min($page, $nbPages);
$offset = ($page - 1) * LIVRES_PAR_PAGE;

$stmt = $pdo->prepare("
    SELECT l.id_livre, l.titre, l.auteur, l.couverture,
           c.nom AS categorie_nom, c.slug AS categorie_slug,
           $prixLivre AS prix,
           SUBSTRING_INDEX(GROUP_CONCAT(e.etat ORDER BY e.origine IN ('don', 'emprunt'), e.prix ASC), ',', 1) AS etat,
           SUM(CASE WHEN e.origine = 'magasin' THEN e.quantite END) AS stock,
           GROUP_CONCAT(DISTINCT e.origine ORDER BY e.origine) AS origines,
           av.moyenne AS note_moyenne, COALESCE(av.nb, 0) AS nb_avis$selectPertinence
    $sqlBase
    ORDER BY {$trisPossibles[$triActif][1]}
    LIMIT " . LIVRES_PAR_PAGE . " OFFSET $offset");
$stmt->execute($params + $paramsTri);
$livres = $stmt->fetchAll();

$arbre = arbreCategories($pdo);
$tags = listerTags($pdo);
$choixScolaires = [];
foreach (['niveau' => 'niveau_scolaire', 'matiere' => 'matiere'] as $cle => $colonne) {
    $stmtChoix = $pdo->prepare("SELECT DISTINCT $colonne FROM livre WHERE is_valide = 1 AND $colonne IS NOT NULL AND $colonne <> '' ORDER BY $colonne");
    $stmtChoix->execute();
    $choixScolaires[$cle] = $stmtChoix->fetchAll(PDO::FETCH_COLUMN);
    if ($f[$cle] !== '' && !in_array($f[$cle], $choixScolaires[$cle], true)) { $choixScolaires[$cle][] = $f[$cle]; }
}

$stmt = $pdo->prepare("SELECT lt.id_tag, COUNT(*) FROM livre_tag lt WHERE lt.id_livre IN (SELECT l.id_livre $sqlBase) GROUP BY lt.id_tag");
$stmt->execute($params);
$nombreLivresParTag = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$famillesTags = tagsParFamille(array_filter($tags, fn($t) => in_array($t['slug'], $f['tag'], true) || !empty($nombreLivresParTag[$t['id_tag']])));

$universCourant = null;
$categorieCourante = null;
if ($f['categorie'] !== '') {
    foreach ($arbre as $univers) {
        if ($univers['slug'] === $f['categorie']) {
            $universCourant = $univers;
        }
        foreach ($univers['categories'] as $enfant) {
            if ($enfant['slug'] === $f['categorie']) {
                $universCourant = $univers;
                $categorieCourante = $enfant;
            }
        }
    }
}
if ($universCourant && !isset($preset['categorie']) && $titrePage === 'Catalogue') {
    $titrePage = $categorieCourante['nom'] ?? $universCourant['nom'];
    $sousTitre = $categorieCourante ? '' : (string) ($universCourant['description'] ?? '');
}

$filtresActifs = [];
foreach (['niveau' => 'Niveau', 'matiere' => 'Matière'] as $cle => $libelle) {
    if ($f[$cle] !== '' && !isset($preset[$cle])) { $filtresActifs[$cle] = $libelle . ' : ' . $f[$cle]; }
}
if ($f['q'] !== '' && !isset($preset['q'])) {
    $filtresActifs['q'] = '« ' . $f['q'] . ' »';
}
if ($f['categorie'] !== '' && !isset($preset['categorie'])) {
    $filtresActifs['categorie'] = $categorieCourante['nom'] ?? ($universCourant['nom'] ?? $f['categorie']);
}
$nomsTags = array_column($tags, 'nom', 'slug');
foreach ($f['tag'] as $slugTag) {
    $filtresActifs['tag:' . $slugTag] = $nomsTags[$slugTag] ?? $slugTag;
}
if (isset($etatsPossibles[$f['etat']])) {
    $filtresActifs['etat'] = $etatsPossibles[$f['etat']];
}
if (isset(ORIGINES_OFFRE[$f['offre']]) && !isset($preset['offre'])) {
    $filtresActifs['offre'] = ORIGINES_OFFRE[$f['offre']];
}
if ($f['prix_min'] !== '' && is_numeric($f['prix_min']) && !isset($preset['prix_min'])) {
    $filtresActifs['prix_min'] = 'Dès ' . str_replace('.', ',', (string) (float) $f['prix_min']) . ' €';
}
if ($f['prix_max'] !== '' && is_numeric($f['prix_max']) && !isset($preset['prix_max'])) {
    $filtresActifs['prix_max'] = 'Jusqu\'à ' . str_replace('.', ',', (string) (float) $f['prix_max']) . ' €';
}
if (in_array($f['note_min'], ['3', '4', '4.5'], true)) {
    $filtresActifs['note_min'] = str_replace('.', ',', $f['note_min']) . ' ★ et plus';
}
$categoriesVoisines = [];
$livresSuggeres = [];
$categorieSansLivre = !$livres && $categorieCourante && count($filtresActifs) <= 1;
if (!$livres) {
    require_once 'livres_lib.php';
    $idsUnivers = [];
    if ($universCourant) {
        $idsUnivers[] = (int) $universCourant['id_categorie'];
        $nombreParCategorie = $pdo->query("
            SELECT l.id_categorie, COUNT(DISTINCT l.id_livre)
            FROM livre l
            INNER JOIN " . sqlOffres() . " e ON e.id_livre = l.id_livre
            WHERE l.is_valide = 1
            GROUP BY l.id_categorie")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($universCourant['categories'] as $enfant) {
            $idsUnivers[] = (int) $enfant['id_categorie'];
            if ($enfant['slug'] !== $f['categorie'] && !empty($nombreParCategorie[$enfant['id_categorie']])) {
                $categoriesVoisines[] = $enfant + ['nb' => (int) $nombreParCategorie[$enfant['id_categorie']]];
            }
        }
    }
    $conditionUnivers = $idsUnivers ? " AND l.id_categorie IN (" . implode(', ', $idsUnivers) . ")" : '';
    $livresSuggeres = $pdo->query(sqlCartesLivres($conditionUnivers) . "
        ORDER BY (SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_livre = l.id_livre) DESC, l.id_livre DESC
        LIMIT 4")->fetchAll();
}
$scriptsPage = ['filtres_catalogue.js'];

function texteVoirLivres(int $nombre): string
{
    return $nombre === 0 ? 'Aucun livre' : ($nombre === 1 ? 'Voir le livre' : "Voir les $nombre livres");
}

function lienFiltreCategorie(array $f, array $preset, string $slug): string
{
    if (isset($preset['categorie'])) {
        return lienCategorie($slug);
    }
    return lienFiltres($f, $preset, ['categorie' => $slug, 'page' => '']);
}

function retraitFiltres(array $f, array $cles): array
{
    $changements = ['page' => ''];
    foreach ($cles as $cle) {
        if (str_starts_with($cle, 'tag:')) {
            $f['tag'] = array_values(array_diff($f['tag'], [substr($cle, 4)]));
            $changements['tag'] = $f['tag'];
        } else {
            $changements[$cle] = '';
        }
    }
    return $changements;
}

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
    .filtres-box { background: white; border-radius: 15px; border: 1px solid #eee; }
    .filtres-box .form-label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #274e13; letter-spacing: .03em; }</style>

<div class="container mt-4 mb-5">
    <?php if ($universCourant): ?>
        <nav aria-label="Fil d'Ariane" class="fil-ariane">
            <a href="catalogue.php">Catalogue</a>
            <span aria-hidden="true">›</span>
            <?php if ($categorieCourante): ?>
                <a href="<?= htmlspecialchars(lienCategorie($universCourant['slug'])) ?>"><?= htmlspecialchars($universCourant['nom']) ?></a>
                <span aria-hidden="true">›</span>
                <span aria-current="page"><?= htmlspecialchars($categorieCourante['nom']) ?></span>
            <?php else: ?>
                <span aria-current="page"><?= htmlspecialchars($universCourant['nom']) ?></span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="color:#274e13;"><?= htmlspecialchars($titrePage) ?></h1>
            <p class="text-muted mb-0">
                <?= $sousTitre ? htmlspecialchars($sousTitre) . ' · ' : '' ?>
                <?= $total ?> livre<?= $total > 1 ? 's' : '' ?> disponible<?= $total > 1 ? 's' : '' ?>
                <?= $f['q'] !== '' ? ' pour « ' . htmlspecialchars($f['q']) . ' »' : '' ?>
            </p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <?php foreach (['q', 'categorie', 'tag', 'etat', 'offre', 'prix_min', 'prix_max', 'note_min'] as $k): ?>
                <?php if (isset($preset[$k])) continue; ?>
                <?php foreach ((array) $f[$k] as $valeur): ?>
                    <?php if ($valeur !== ''): ?>
                        <input type="hidden" name="<?= $k ?><?= is_array($f[$k]) ? '[]' : '' ?>" value="<?= htmlspecialchars($valeur) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <label for="filtre-tri" class="small text-muted text-nowrap">Trier par</label>
            <select id="filtre-tri" name="tri" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                <?php foreach ($trisPossibles as $cle => [$libelle]): ?>
                    <option value="<?= $cle ?>" <?= $triActif === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($universCourant && $universCourant['categories']): ?>
        <div class="puces-univers" aria-label="Catégories de l'univers <?= htmlspecialchars($universCourant['nom']) ?>">
            <a href="<?= htmlspecialchars(lienFiltreCategorie($f, $preset, $universCourant['slug'])) ?>" class="puce-univers <?= $categorieCourante ? '' : 'actif' ?>">Tout voir</a>
            <?php foreach ($universCourant['categories'] as $enfant): ?>
                <a href="<?= htmlspecialchars(lienFiltreCategorie($f, $preset, $enfant['slug'])) ?>" class="puce-univers <?= $categorieCourante && $categorieCourante['slug'] === $enfant['slug'] ? 'actif' : '' ?>"><?= htmlspecialchars($enfant['nom']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="offcanvas-lg offcanvas-start volet-filtres" tabindex="-1" id="voletFiltres" aria-labelledby="voletFiltresTitre">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title fw-bold" id="voletFiltresTitre">Filtrer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#voletFiltres" aria-label="Fermer"></button>
            </div>
            <div class="offcanvas-body p-lg-0">
            <form method="GET" class="filtres-box p-3 shadow-sm" id="formulaireFiltres">
                <?php if ($f['tri'] !== ''): ?>
                    <input type="hidden" name="tri" value="<?= htmlspecialchars($f['tri']) ?>">
                <?php endif; ?>

                <?php if ($f['q'] !== ''): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($f['q']) ?>">
                <?php endif; ?>

                <?php if (!isset($preset['offre'])): ?>
                    <div class="mb-3">
                        <label for="filtre-offre" class="form-label">Type d'offre</label>
                        <select id="filtre-offre" name="offre" class="form-select form-select-sm">
                            <option value="">Toutes les offres</option>
                            <?php foreach (ORIGINES_OFFRE as $cle => $libelle): ?>
                                <option value="<?= $cle ?>" <?= $f['offre'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-bibli w-100 rounded-pill btn-sm fw-bold d-none d-lg-block mb-3">Filtrer</button>

                <?php if (!isset($preset['categorie'])): ?>
                    <div class="mb-3">
                        <label for="filtre-categorie" class="form-label">Catégorie</label>
                        <select id="filtre-categorie" name="categorie" class="form-select form-select-sm">
                            <option value="">Toutes</option>
                            <?= optionsCategories($arbre, $f['categorie'], 'slug', true) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="filtre-etat" class="form-label">État</label>
                    <select id="filtre-etat" name="etat" class="form-select form-select-sm">
                        <option value="">Tous les états</option>
                        <?php foreach ($etatsPossibles as $cle => $libelle): ?>
                            <option value="<?= $cle ?>" <?= $f['etat'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php foreach (['niveau' => 'Niveau scolaire', 'matiere' => 'Matière'] as $cle => $libelle): ?>
                    <?php if ($choixScolaires[$cle] && !isset($preset[$cle])): ?>
                        <div class="mb-3">
                            <label for="filtre-<?= $cle ?>" class="form-label"><?= $libelle ?></label>
                            <select id="filtre-<?= $cle ?>" name="<?= $cle ?>" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <?php foreach ($choixScolaires[$cle] as $choix): ?>
                                    <option value="<?= htmlspecialchars($choix) ?>" <?= $f[$cle] === $choix ? 'selected' : '' ?>><?= htmlspecialchars($choix) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>



                <?php if (!isset($preset['prix_max'])): ?>
                    <div class="mb-3">
                        <span class="form-label d-block">Prix (€)</span>
                        <div class="d-flex gap-2">
                            <input type="number" name="prix_min" aria-label="Prix minimum en euros" min="0" step="0.5" class="form-control form-control-sm" placeholder="Min" value="<?= htmlspecialchars($f['prix_min']) ?>">
                            <input type="number" name="prix_max" aria-label="Prix maximum en euros" min="0" step="0.5" class="form-control form-control-sm" placeholder="Max" value="<?= htmlspecialchars($f['prix_max']) ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="filtre-note" class="form-label">Note des lecteurs</label>
                    <select id="filtre-note" name="note_min" class="form-select form-select-sm">
                        <option value="">Toutes les notes</option>
                        <?php foreach (['4.5' => '4,5 ★ et plus', '4' => '4 ★ et plus', '3' => '3 ★ et plus'] as $v => $libelle): ?>
                            <option value="<?= $v ?>" <?= $f['note_min'] === $v ? 'selected' : '' ?>><?= $libelle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php foreach ($famillesTags as $cleFamille => $tagsFamille): ?>
                    <?php $nombreTagsChoisis = count(array_intersect(array_column($tagsFamille, 'slug'), $f['tag'])); ?>
                    <details class="mb-3 famille-tags filtre-depliant">
                        <summary><?= htmlspecialchars(FAMILLES_TAGS[$cleFamille]) ?><span class="filtre-depliant__compteur" data-selection-tags><?= $nombreTagsChoisis ? $nombreTagsChoisis . ' sélectionné' . ($nombreTagsChoisis > 1 ? 's' : '') : 'Tous' ?></span></summary>
                        <fieldset class="mt-2">
                        <legend class="visually-hidden"><?= htmlspecialchars(FAMILLES_TAGS[$cleFamille]) ?></legend>
                        <div class="puces-tags">
                            <?php foreach ($tagsFamille as $t): ?>
                                <?php $idCase = 'tag-' . $t['slug']; ?>
                                <input type="checkbox" class="btn-check" name="tag[]" value="<?= htmlspecialchars($t['slug']) ?>" id="<?= htmlspecialchars($idCase) ?>" <?= in_array($t['slug'], $f['tag'], true) ? 'checked' : '' ?> data-filtre-immediat>
                                <label class="puce-tag" for="<?= htmlspecialchars($idCase) ?>">
                                    <?= htmlspecialchars($t['nom']) ?>
                                    <span class="compte-tag"><?= (int) ($nombreLivresParTag[$t['id_tag']] ?? 0) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        </fieldset>
                    </details>
                <?php endforeach; ?>

                <a href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>" class="btn btn-link w-100 btn-sm text-muted">Réinitialiser</a>
            </form>
            </div>
            <div class="pied-volet-filtres d-lg-none">
                <button type="submit" form="formulaireFiltres" class="btn btn-bibli w-100 rounded-pill fw-bold" data-voir-livres><?= texteVoirLivres($total) ?></button>
            </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="barre-filtres-mobile d-lg-none">
                <button type="button" class="bouton-filtrer" data-bs-toggle="offcanvas" data-bs-target="#voletFiltres" aria-controls="voletFiltres">
                    <i class="bi bi-sliders" aria-hidden="true"></i> Filtrer<?= $filtresActifs ? ' (' . count($filtresActifs) . ')' : '' ?>
                </button>
                <span class="text-muted small"><?= $total ?> livre<?= $total > 1 ? 's' : '' ?></span>
            </div>
            <?php if ($filtresActifs): ?>
                <div class="puces-filtres-actifs" aria-label="Filtres actifs">
                    <?php foreach ($filtresActifs as $cleFiltre => $libelleFiltre): ?>
                        <a href="<?= htmlspecialchars(lienFiltres($f, $preset, retraitFiltres($f, [$cleFiltre]))) ?>" class="puce-filtre-actif" aria-label="Retirer le filtre <?= htmlspecialchars($libelleFiltre) ?>">
                            <?= htmlspecialchars($libelleFiltre) ?> <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($filtresActifs) > 1): ?>
                        <a href="<?= htmlspecialchars(lienFiltres($f, $preset, retraitFiltres($f, array_keys($filtresActifs)))) ?>" class="lien-effacer-filtres">Tout effacer</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!$livres): ?>
                <div class="carte-compte etat-vide-catalogue">
                    <?= iconeDessin($categorieSansLivre ? 'carton' : 'loupe') ?>
                    <?php if ($categorieSansLivre): ?>
                        <h2>Ce rayon attend ses premiers livres</h2>
                        <p>Aucun livre n'est en vente dans « <?= htmlspecialchars($categorieCourante['nom']) ?> » pour le moment. Revenez bientôt, ou explorez les autres rayons de l'univers <?= htmlspecialchars($universCourant['nom']) ?>.</p>
                        <div class="etat-vide-catalogue__actions">
                            <a href="<?= htmlspecialchars(lienCategorie($universCourant['slug'])) ?>" class="bouton bouton-principal">Explorer <?= htmlspecialchars($universCourant['nom']) ?></a>
                            <a href="don_creer.php" class="bouton bouton-contour">Vous en avez à donner ?</a>
                        </div>
                    <?php else: ?>
                        <h2>Aucun livre ne correspond à ces critères</h2>
                        <p>Essayez de retirer un filtre ou d'élargir votre recherche.</p>
                        <div class="etat-vide-catalogue__actions">
                            <?php if ($filtresActifs): ?>
                                <a href="<?= htmlspecialchars(lienFiltres($f, $preset, retraitFiltres($f, array_keys($filtresActifs)))) ?>" class="bouton bouton-principal">Effacer les filtres</a>
                            <?php endif; ?>
                            <a href="catalogue.php" class="bouton bouton-contour">Voir tout le catalogue</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($categoriesVoisines): ?>
                        <div class="etat-vide-catalogue__rayons">
                            <span>Dans le même univers</span>
                            <?php foreach ($categoriesVoisines as $voisine): ?>
                                <a href="<?= htmlspecialchars(lienCategorie($voisine['slug'])) ?>" class="puce-univers"><?= htmlspecialchars($voisine['nom']) ?> <small><?= $voisine['nb'] ?></small></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($livresSuggeres): ?>
                    <h2 class="titre-suggestions-vide">Les lecteurs aiment aussi</h2>
                    <div class="catalogue-grid">
                        <?php foreach ($livresSuggeres as $l): include 'partie_carte_livre.php'; endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="catalogue-grid">
                    <?php foreach ($livres as $l): include 'partie_carte_livre.php'; endforeach; ?>
                </div>

                <?php if ($nbPages > 1): ?>
                    <?php $lienPage = fn(int $numero) => lienFiltres($f, $preset, ['page' => $numero > 1 ? $numero : '']); include 'partie_pagination.php'; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
