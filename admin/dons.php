<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../dons_lib.php';
require_once __DIR__ . '/../messagerie_lib.php';
exigerDroit('equipe');

$filtre = is_string($_GET['statut'] ?? null) ? $_GET['statut'] : 'en_cours';
$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$total = 0;
$nombrePages = 1;
$retour = 'dons.php?' . http_build_query(['statut' => $filtre, 'q' => $q]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    rediriger('don.php?id=' . max(0, (int) ($_POST['id_don'] ?? 0)), 'info', 'Consultez le don puis indiquez le motif de votre intervention.');
}

$conditions = [
    'en_cours'   => "d.statut IN ('disponible', 'reserve') AND d.date_limite >= CURDATE()",
    'disponible' => "d.statut = 'disponible' AND d.date_limite >= CURDATE()",
    'reserve'    => "d.statut = 'reserve'",
    'recupere'   => "d.statut = 'recupere'",
    'expire'     => "d.statut = 'disponible' AND d.date_limite < CURDATE()",
    'annule'     => "d.statut = 'annule'",
    'tous'       => "1 = 1",
];
if (!isset($conditions[$filtre])) {
    $filtre = 'en_cours';
}
$where = [$conditions[$filtre]];
$params = [];
if ($q !== '') {
    $where[] = "(d.titre LIKE ? OR d.code_court = ? OR donneur.pseudo LIKE ? OR benef.pseudo LIKE ?)";
    array_push($params, "%$q%", strtoupper($q), "%$q%", "%$q%");
}

$dons = [];
$tablePresente = true;
try {
    $compter = $pdo->prepare("SELECT COUNT(*) FROM don d INNER JOIN user donneur ON donneur.id = d.id_donneur LEFT JOIN user benef ON benef.id = d.id_beneficiaire WHERE " . implode(' AND ', $where));
    $compter->execute($params);
    $total = (int) $compter->fetchColumn();
    $nombrePages = max(1, (int) ceil($total / 20));
    $page = min($page, $nombrePages);
    $decalage = ($page - 1) * 20;
    $stmt = $pdo->prepare("
        SELECT d.*, livre_don.couverture AS couverture_don, donneur.pseudo AS pseudo_donneur, benef.pseudo AS pseudo_beneficiaire
        FROM don d LEFT JOIN livre livre_don ON livre_don.id_livre = d.id_livre
        INNER JOIN user donneur ON donneur.id = d.id_donneur
        LEFT JOIN user benef ON benef.id = d.id_beneficiaire
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.date_creation DESC, d.id_don DESC LIMIT 20 OFFSET " . $decalage);
    $stmt->execute($params);
    $dons = $stmt->fetchAll();
} catch (PDOException $e) {
    $tablePresente = false;
}

$titrePage = 'Dons';
$pageActive = 'dons';
include __DIR__ . '/_haut.php';

$onglets = ['en_cours' => 'En cours', 'disponible' => 'Disponibles', 'reserve' => 'Réservés', 'recupere' => 'Récupérés',
            'expire' => 'Expirés', 'annule' => 'Annulés', 'tous' => 'Tous'];
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning">La table des dons n'existe pas encore : importez <code>sql/maj_dons.sql</code> dans phpMyAdmin.</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 flex-wrap">
    <?php foreach ($onglets as $cle => $libelle): ?>
        <li class="nav-item">
            <a class="nav-link <?= $filtre === $cle ? 'active bg-success' : 'text-success' ?>" href="?<?= http_build_query(['statut' => $cle, 'q' => $q]) ?>"><?= $libelle ?></a>
        </li>
    <?php endforeach; ?>
</ul>

<form class="d-flex gap-2 mb-3" method="GET">
    <input type="hidden" name="statut" value="<?= e($filtre) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher un don" style="max-width: 320px;" placeholder="Titre, code ou pseudo">
    <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
</form>

<div class="carte">
    <div class="carte-entete"><?= $total ?> don<?= $total > 1 ? 's' : '' ?></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase">
                <tr><th>Livre</th><th>Donneur</th><th>Bénéficiaire</th><th>Retrait</th><th>Statut</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php if (!$dons): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun don dans cette liste.</td></tr>
                <?php endif; ?>
                <?php foreach ($dons as $d): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="../<?= photoDon($d['photo'], $d['couverture_don']) ?>" alt="" class="couverture-mini">
                                <div>
                                    <div class="fw-bold"><?= e($d['titre']) ?></div>
                                    <div class="small text-muted">#<?= $d['id_don'] ?> · code <span class="font-monospace"><?= e($d['code_court']) ?></span></div>
                                </div>
                            </div>
                        </td>
                        <td class="small">@<?= e($d['pseudo_donneur']) ?></td>
                        <td class="small"><?= $d['pseudo_beneficiaire'] ? '@' . e($d['pseudo_beneficiaire']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="small">
                            <?= e($d['lieu_retrait']) ?><br>
                            <span class="text-muted">avant le <?= date('d/m/Y', strtotime($d['date_limite'])) ?></span>
                        </td>
                        <td><?= badgeStatutDon($d) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="don.php?id=<?= (int) $d['id_don'] ?>" class="btn btn-sm btn-outline-success">Voir le suivi</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($nombrePages > 1): ?>
    <nav class="d-flex flex-wrap gap-3 align-items-center justify-content-between mt-3" aria-label="Pages des dons">
        <span>Page <?= $page ?> sur <?= $nombrePages ?></span>
        <div class="d-flex gap-2">
            <?php if ($page > 1): ?><a class="btn btn-outline-success" href="?<?= e(http_build_query(['statut' => $filtre, 'q' => $q, 'page' => $page - 1])) ?>">Précédente</a><?php endif; ?>
            <?php if ($page < $nombrePages): ?><a class="btn btn-outline-success" href="?<?= e(http_build_query(['statut' => $filtre, 'q' => $q, 'page' => $page + 1])) ?>">Suivante</a><?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
<?php include __DIR__ . '/_bas.php'; ?>
