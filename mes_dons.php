<?php
require_once 'config.php';
require_once 'dons_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$stmtPointsDons = $pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM point_mouvement WHERE id_user = ? AND type = 'don' AND statut = 'valide'");
$stmtPointsDons->execute([$idUser]);
$pointsDonsGagnes = (int) $stmtPointsDons->fetchColumn();

$stmt = $pdo->prepare("
    SELECT d.*, b.pseudo AS beneficiaire_pseudo
    FROM don d LEFT JOIN user b ON b.id = d.id_beneficiaire
    WHERE d.id_donneur = ? ORDER BY d.date_creation DESC");
$stmt->execute([$idUser]);
$donnes = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT d.*, u.pseudo AS donneur_pseudo
    FROM don d INNER JOIN user u ON u.id = d.id_donneur
    WHERE d.id_beneficiaire = ? ORDER BY COALESCE(d.date_recuperation, d.date_reservation) DESC");
$stmt->execute([$idUser]);
$recus = $stmt->fetchAll();

$nbRecuperes = count(array_filter($donnes, fn($d) => $d['statut'] === 'recupere'));

include 'header.php';

function ligneDon(array $d, string $infoPersonne): void { ?>
    <a href="don_detail.php?id=<?= $d['id_don'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
        <img src="<?= htmlspecialchars(photoDon($d['photo'])) ?>" alt="" class="rounded border" style="width: 45px; height: 60px; object-fit: cover;">
        <div class="flex-grow-1">
            <div class="fw-bold"><?= htmlspecialchars($d['titre']) ?></div>
            <div class="small text-muted"><?= $infoPersonne ?></div>
        </div>
        <?= badgeStatutDon($d) ?>
    </a>
<?php }
?>

<div class="container my-5" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Mes dons</h2>
        <a href="don_creer.php" class="btn btn-success rounded-pill"><i class="bi bi-plus-lg"></i> Nouveau don</a>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-4"><div class="bg-white border rounded-4 p-3"><div class="fs-3 fw-bold text-success"><?= count($donnes) ?></div><div class="small text-muted">dons publiés</div></div></div>
        <div class="col-4"><div class="bg-white border rounded-4 p-3"><div class="fs-3 fw-bold text-success"><?= $nbRecuperes ?></div><div class="small text-muted">livres donnés</div></div></div>
        <div class="col-4"><div class="bg-white border rounded-4 p-3"><div class="fs-3 fw-bold text-success"><?= $pointsDonsGagnes ?></div><div class="small text-muted">points gagnés</div></div></div>
    </div>

    <h5 class="fw-bold">Livres que j'ai donnés</h5>
    <div class="list-group mb-4 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <?php foreach ($donnes as $d): ?>
            <?php ligneDon($d, 'Publié le ' . date('d/m/Y', strtotime($d['date_creation']))
                . ($d['beneficiaire_pseudo'] ? ' · ' . ($d['statut'] === 'recupere' ? 'récupéré par ' : 'réservé par ') . htmlspecialchars($d['beneficiaire_pseudo']) : '')); ?>
        <?php endforeach; ?>
        <?php if (!$donnes): ?><div class="list-group-item text-muted py-3">Vous n'avez encore rien donné. <a href="don_creer.php">Proposer un livre</a></div><?php endif; ?>
    </div>

    <h5 class="fw-bold">Livres que j'ai réservés ou reçus</h5>
    <div class="list-group shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <?php foreach ($recus as $d): ?>
            <?php ligneDon($d, 'Don de ' . htmlspecialchars($d['donneur_pseudo']) . ' · ' . htmlspecialchars($d['lieu_retrait'])); ?>
        <?php endforeach; ?>
        <?php if (!$recus): ?><div class="list-group-item text-muted py-3">Aucune réservation. <a href="dons.php">Voir les livres à donner</a></div><?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
