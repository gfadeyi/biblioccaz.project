<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'categories_lib.php';

redirigerAnciennesAdresses();

const DONS_PAR_PAGE = 24;
const DONS_TRIS = ['recents' => 'Plus récents', 'bientot' => 'À récupérer bientôt', 'titre' => 'Titre (A → Z)'];

$texte = fn(string $cle) => is_string($_GET[$cle] ?? null) ? mb_substr(trim($_GET[$cle]), 0, 100) : '';
$q = $texte('q');
$lieu = $texte('lieu');
$categorie = $texte('categorie');
$etat = isset(DON_ETATS[$texte('etat')]) ? $texte('etat') : '';
$tri = isset(DONS_TRIS[$texte('tri')]) ? $texte('tri') : 'recents';
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ["d.statut = 'disponible'", "d.date_limite >= CURDATE()"];
$params = [];
$mots = motsRechercheDon($q);
if ($mots) {
    $where[] = conditionRechercheDon($mots, ['d.titre', 'd.auteur', 'd.description', 'livre_don.titre', 'livre_don.auteur'], 'livre_don.isbn', $params);
}
if ($lieu !== '') {
    $where[] = "d.lieu_retrait LIKE ?";
    $params[] = '%' . addcslashes($lieu, '%_\\') . '%';
}
$nomCategorie = '';
if ($categorie !== '') {
    $idsFiltre = idsCategorieEtEnfants($pdo, $categorie);
    if ($idsFiltre) {
        $where[] = "d.id_categorie IN (" . implode(', ', array_fill(0, count($idsFiltre), '?')) . ")";
        array_push($params, ...array_map('intval', $idsFiltre));
        $stmt = $pdo->prepare("SELECT nom FROM categorie WHERE slug = ?");
        $stmt->execute([$categorie]);
        $nomCategorie = (string) $stmt->fetchColumn();
    } else {
        $categorie = '';
    }
}
if ($etat !== '') {
    $where[] = "d.etat = ?";
    $params[] = $etat;
}

$depuis = "FROM don d LEFT JOIN livre livre_don ON livre_don.id_livre = d.id_livre
    INNER JOIN user u ON u.id = d.id_donneur
    LEFT JOIN categorie c ON c.id_categorie = d.id_categorie
    WHERE " . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT COUNT(*) $depuis");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / DONS_PAR_PAGE));
$page = min($page, $pages);
$ordre = match ($tri) { 'bientot' => 'd.date_limite ASC, d.id_don DESC', 'titre' => 'd.titre ASC, d.id_don DESC', default => 'd.date_creation DESC, d.id_don DESC' };
$stmt = $pdo->prepare("SELECT d.*, livre_don.couverture AS couverture_don, c.nom AS categorie_nom, u.pseudo AS donneur_pseudo
    $depuis ORDER BY $ordre LIMIT " . DONS_PAR_PAGE . " OFFSET " . (($page - 1) * DONS_PAR_PAGE));
$stmt->execute($params);
$dons = $stmt->fetchAll();
$arbreCategoriesDons = arbreCategories($pdo);

$criteres = array_filter(['q' => $q, 'lieu' => $lieu, 'categorie' => $categorie, 'etat' => $etat, 'tri' => $tri !== 'recents' ? $tri : '']);
$lienDons = fn(array $changements) => 'dons' . (($requete = http_build_query(array_filter(array_merge($criteres, $changements), fn($valeur) => $valeur !== '' && $valeur !== null))) !== '' ? '?' . $requete : '');
$filtresActifs = array_filter([
    'q' => $q !== '' ? '« ' . $q . ' »' : '',
    'lieu' => $lieu !== '' ? 'Lieu : ' . $lieu : '',
    'categorie' => $categorie !== '' ? ($nomCategorie ?: $categorie) : '',
    'etat' => $etat !== '' ? DON_ETATS[$etat] : '',
]);
$titreDocument = 'Voir les livres à donner';

include 'header.php';
?>

<div class="container my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="fw-bold mb-1" style="color:#274e13;"><i class="bi bi-gift"></i> Livres à donner</h1>
            <p class="text-muted mb-0">Gratuits, à récupérer en main propre auprès d’autres lecteurs.</p>
        </div>
        <a href="don_creer" class="btn btn-success rounded-pill fw-bold"><i class="bi bi-plus-lg"></i> Donner un livre</a>
    </div>

    <form method="GET" class="filtres-dons" role="search" aria-label="Rechercher un livre à donner">
        <div class="filtres-dons__texte"><label for="dons-recherche" class="form-label small fw-bold mb-1">Titre, auteur ou ISBN</label><input type="search" id="dons-recherche" name="q" class="form-control" placeholder="Ex : Dune, Herbert, 978…" maxlength="100" value="<?= htmlspecialchars($q) ?>"></div>
        <div class="filtres-dons__lieu"><label for="dons-lieu" class="form-label small fw-bold mb-1">Ville ou lieu de retrait</label><input type="text" id="dons-lieu" name="lieu" class="form-control" placeholder="Ex : Lyon" maxlength="100" value="<?= htmlspecialchars($lieu) ?>"></div>
        <div class="filtres-dons__choix">
            <label for="dons-categorie" class="form-label small fw-bold mb-1">Catégorie</label>
            <select id="dons-categorie" name="categorie" class="form-select">
                <option value="">Toutes les catégories</option>
                <?= optionsCategories($arbreCategoriesDons, $categorie, 'slug', true) ?>
            </select>
        </div>
        <div class="filtres-dons__etat">
            <label for="dons-etat" class="form-label small fw-bold mb-1">État</label>
            <select id="dons-etat" name="etat" class="form-select">
                <option value="">Tous les états</option>
                <?php foreach (DON_ETATS as $cle => $libelle): ?><option value="<?= htmlspecialchars($cle) ?>"<?= $etat === $cle ? ' selected' : '' ?>><?= htmlspecialchars($libelle) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="filtres-dons__tri">
            <label for="dons-tri" class="form-label small fw-bold mb-1">Trier par</label>
            <select id="dons-tri" name="tri" class="form-select">
                <?php foreach (DONS_TRIS as $cle => $libelle): ?><option value="<?= $cle ?>"<?= $tri === $cle ? ' selected' : '' ?>><?= $libelle ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="filtres-dons__actions">
            <button class="btn btn-success rounded-pill fw-bold"><i class="bi bi-search" aria-hidden="true"></i> Rechercher</button>
        </div>
    </form>

    <div class="dons-resume" aria-live="polite">
        <strong><?= $total ?> don<?= $total > 1 ? 's' : '' ?> disponible<?= $total > 1 ? 's' : '' ?></strong>
        <?php foreach ($filtresActifs as $cle => $libelle): ?>
            <a class="puce-filtre" href="<?= htmlspecialchars($lienDons([$cle => '', 'page' => ''])) ?>" aria-label="Retirer le filtre <?= htmlspecialchars($libelle) ?>"><?= htmlspecialchars($libelle) ?> <i class="bi bi-x" aria-hidden="true"></i></a>
        <?php endforeach; ?>
        <?php if ($filtresActifs): ?><a href="dons" class="ms-1">Effacer les filtres</a><?php endif; ?>
    </div>

    <?php if (!$dons): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <i class="bi bi-gift text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary">Aucun don disponible pour ces critères.</h5>
            <?php if ($filtresActifs): ?><p class="text-muted mb-0">Essayez un autre mot, une ville voisine ou <a href="dons">voyez tous les livres à donner</a>.</p><?php endif; ?>
            <a href="don_creer" class="btn btn-success rounded-pill px-4 mt-3 mx-auto">Soyez le premier à donner un livre</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($dons as $d): ?>
                <div class="col-sm-6 col-lg-3">
                    <a href="don_detail?id=<?= $d['id_don'] ?>" class="card h-100 border-0 shadow-sm text-decoration-none text-dark" style="border-radius: 15px; overflow: hidden;">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <img src="<?= htmlspecialchars(photoDon($d['photo'], $d['couverture_don'])) ?>" alt="<?= htmlspecialchars(legendePhotoDon($d['photo'], $d['couverture_don']) . ' : ' . $d['titre']) ?>" loading="lazy" decoding="async" style="max-height: 180px; max-width: 90%; object-fit: contain;">
                        </div>
                        <div class="card-body">
                            <div class="small text-muted mb-2"><?= htmlspecialchars(legendePhotoDon($d['photo'], $d['couverture_don'])) ?></div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">Gratuit</span>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($d['titre']) ?></h6>
                            <?php if ($d['auteur']): ?><div class="small text-muted"><?= htmlspecialchars($d['auteur']) ?></div><?php endif; ?>
                            <div class="small mt-2"><i class="bi bi-geo-alt text-success"></i> <?= htmlspecialchars($d['lieu_retrait']) ?></div>
                            <div class="small text-muted">Avant le <?= date('d/m', strtotime($d['date_limite'])) ?> · <?= DON_ETATS[$d['etat']] ?? '' ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="mt-4" aria-label="Pages des dons">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars($lienDons(['page' => $page - 1])) ?>" aria-label="Page précédente">‹</a></li><?php endif; ?>
                    <?php for ($numero = max(1, $page - 2); $numero <= min($pages, $page + 2); $numero++): ?>
                        <li class="page-item<?= $numero === $page ? ' active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($lienDons(['page' => $numero > 1 ? $numero : ''])) ?>"<?= $numero === $page ? ' aria-current="page"' : '' ?>><?= $numero ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?><li class="page-item"><a class="page-link" href="<?= htmlspecialchars($lienDons(['page' => $page + 1])) ?>" aria-label="Page suivante">›</a></li><?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
