<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../dons_lib.php';
require_once __DIR__ . '/../messagerie_lib.php';
exigerDroit('equipe');

$filtre = $_GET['statut'] ?? 'en_cours';
$q = trim($_GET['q'] ?? '');
$retour = 'dons.php?' . http_build_query(['statut' => $filtre, 'q' => $q]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    if (($_POST['action'] ?? '') === 'annuler') {
        $idDon = (int) ($_POST['id_don'] ?? 0);
        $maj = $pdo->prepare("UPDATE don SET statut = 'annule' WHERE id_don = ? AND statut IN ('disponible', 'reserve')");
        $maj->execute([$idDon]);
        if ($maj->rowCount()) {
            insertLog('DON', "Don #$idDon annulé par l'équipe");
            fermerConversationsObjet($pdo, 'don', $idDon, "Le don a été annulé par l'équipe : la conversation est terminée.");
            rediriger($retour, 'success', "Le don #$idDon est annulé.");
        }
        rediriger($retour, 'warning', "Ce don ne peut plus être annulé.");
    }
    rediriger($retour);
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
    $where[] = "(d.titre LIKE ? OR d.code_court = ? OR donneur.pseudo LIKE ?)";
    array_push($params, "%$q%", strtoupper($q), "%$q%");
}

$dons = [];
$tablePresente = true;
try {
    $stmt = $pdo->prepare("
        SELECT d.*, livre_don.couverture AS couverture_don, donneur.pseudo AS pseudo_donneur, benef.pseudo AS pseudo_beneficiaire
        FROM don d LEFT JOIN livre livre_don ON livre_don.id_livre = d.id_livre
        INNER JOIN user donneur ON donneur.id = d.id_donneur
        LEFT JOIN user benef ON benef.id = d.id_beneficiaire
        WHERE " . implode(' AND ', $where) . "
        ORDER BY d.date_creation DESC LIMIT 200");
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
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher un don" style="max-width: 320px;" placeholder="Titre, code ou pseudo du donneur">
    <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
</form>

<div class="carte">
    <div class="carte-entete"><?= count($dons) ?> don<?= count($dons) > 1 ? 's' : '' ?></div>
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
                            <a href="../don_detail.php?id=<?= $d['id_don'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir l'annonce"><i class="bi bi-eye"></i></a>
                            <?php if (in_array($d['statut'], ['disponible', 'reserve'], true)): ?>
                                <form method="POST" class="d-inline" data-confirmer="Annuler ce don ? Le donneur et le bénéficiaire éventuel ne pourront plus l'utiliser.">
                                    <?= champCsrf() ?>
                                    <input type="hidden" name="action" value="annuler">
                                    <input type="hidden" name="id_don" value="<?= $d['id_don'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Annuler le don"><i class="bi bi-x-circle"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
