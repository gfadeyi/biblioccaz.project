<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'categories_lib.php';

redirigerAnciennesAdresses();

$q =trim($_GET['q'] ?? '');
$lieu = trim($_GET['lieu'] ?? '');
$categorie = $_GET['categorie'] ?? '';

$where = ["d.statut = 'disponible'", "d.date_limite >= CURDATE()"];
$params = [];
if ($q !== '') {
    $where[] = "(d.titre LIKE :q1 OR d.auteur LIKE :q2)";
    $params['q1'] = $params['q2'] = "%$q%";
}
if ($lieu !== '') {
    $where[] = "d.lieu_retrait LIKE :lieu";
    $params['lieu'] = "%$lieu%";
}
if ($categorie !== '') {
    $idsFiltre = idsCategorieEtEnfants($pdo, $categorie);
    $marqueurs = [];
    foreach ($idsFiltre as $index => $idFiltre) {
        $marqueurs[] = ':categorie' . $index;
        $params['categorie' . $index] = $idFiltre;
    }
    $where[] = "d.id_categorie IN (" . implode(', ', $marqueurs) . ")";
}

$stmt = $pdo->prepare("
    SELECT d.*, livre_don.couverture AS couverture_don, c.nom AS categorie_nom, u.pseudo AS donneur_pseudo
    FROM don d LEFT JOIN livre livre_don ON livre_don.id_livre = d.id_livre
    INNER JOIN user u ON u.id = d.id_donneur
    LEFT JOIN categorie c ON c.id_categorie = d.id_categorie
    WHERE " . implode(' AND ', $where) . "
    ORDER BY d.date_creation DESC
    LIMIT 60");
$stmt->execute($params);
$dons = $stmt->fetchAll();
$arbreCategoriesDons = arbreCategories($pdo);

include 'header.php';
?>

<div class="container my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="fw-bold mb-1" style="color:#274e13;"><i class="bi bi-gift"></i> Livres à donner</h1>
            <p class="text-muted mb-0"><?= count($dons) ?> don<?= count($dons) > 1 ? 's' : '' ?> disponible<?= count($dons) > 1 ? 's' : '' ?> · gratuit, à récupérer en main propre</p>
        </div>
        <a href="don_creer.php" class="btn btn-success rounded-pill fw-bold"><i class="bi bi-plus-lg"></i> Donner un livre</a>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-5"><label for="dons-recherche" class="visually-hidden">Titre ou auteur</label><input type="search" id="dons-recherche" name="q" class="form-control" placeholder="Titre ou auteur" value="<?= htmlspecialchars($q) ?>"></div>
        <div class="col-md-3"><label for="dons-lieu" class="visually-hidden">Ville ou lieu de retrait</label><input type="text" id="dons-lieu" name="lieu" class="form-control" placeholder="Ville / lieu" value="<?= htmlspecialchars($lieu) ?>"></div>
        <div class="col-md-2">
            <label for="dons-categorie" class="visually-hidden">Catégorie</label>
            <select id="dons-categorie" name="categorie" class="form-select">
                <option value="">Toutes les catégories</option>
                <?= optionsCategories($arbreCategoriesDons, $categorie, 'slug', true) ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-success">Rechercher</button></div>
    </form>

    <?php if (!$dons): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <i class="bi bi-gift text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary">Aucun don disponible pour ces critères.</h5>
            <a href="don_creer.php" class="btn btn-success rounded-pill px-4 mt-3 mx-auto">Soyez le premier à donner un livre</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($dons as $d): ?>
                <div class="col-sm-6 col-lg-3">
                    <a href="don_detail.php?id=<?= $d['id_don'] ?>" class="card h-100 border-0 shadow-sm text-decoration-none text-dark" style="border-radius: 15px; overflow: hidden;">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <img src="<?= htmlspecialchars(photoDon($d['photo'], $d['couverture_don'])) ?>" alt="<?= htmlspecialchars(legendePhotoDon($d['photo'], $d['couverture_don']) . ' : ' . $d['titre']) ?>" loading="lazy" style="max-height: 180px; max-width: 90%; object-fit: contain;">
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
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
