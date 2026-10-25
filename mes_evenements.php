<?php
require_once 'config.php';
require_once 'evenements_lib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT e.*, i.statut AS statut_inscription, i.present, i.date_inscription, i.id_inscription
    FROM evenement_inscription i
    INNER JOIN evenement e ON e.id_evenement = i.id_evenement
    WHERE i.id_user = ? AND i.statut <> 'annule'
    ORDER BY e.date_debut DESC");
$stmt->execute([(int) $_SESSION['user_id']]);
$inscriptions = $stmt->fetchAll();

$maintenant = date('Y-m-d H:i:s');
$aVenir = array_reverse(array_filter($inscriptions, fn($e) => $e['statut'] === 'publie' && $e['date_fin'] >= $maintenant));
$autres = array_filter($inscriptions, fn($e) => !($e['statut'] === 'publie' && $e['date_fin'] >= $maintenant));

$flash = $_SESSION['flash_evt'] ?? null;
unset($_SESSION['flash_evt']);

include 'header.php';

function ligneEvenement(array $e): void
{
    $t = strtotime($e['date_debut']);
    if ($e['statut'] === 'annule') {
        $badge = '<span class="badge bg-danger rounded-pill">Annulé</span>';
    } elseif ($e['date_fin'] < date('Y-m-d H:i:s')) {
        $badge = $e['statut_inscription'] === 'inscrit'
            ? ($e['present'] ? '<span class="badge bg-success rounded-pill">Présent</span>' : '<span class="badge bg-secondary rounded-pill">Terminé</span>')
            : '<span class="badge bg-secondary rounded-pill">Terminé</span>';
    } else {
        $badge = $e['statut_inscription'] === 'inscrit'
            ? '<span class="badge bg-success rounded-pill">Inscrit</span>'
            : '<span class="badge bg-warning text-dark rounded-pill">Liste d\'attente</span>';
    } ?>
    <a href="evenement.php?id=<?= $e['id_evenement'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
        <div class="text-center flex-shrink-0 rounded-3 py-1" style="width: 56px; background: #d9ead3; color: #274e13;">
            <div class="fw-bold fs-5 lh-1"><?= date('j', $t) ?></div>
            <div class="small"><?= mb_substr(EVT_MOIS[(int) date('n', $t) - 1], 0, 4) ?></div>
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold"><?= htmlspecialchars($e['titre']) ?></div>
            <div class="small text-muted"><?= date('G\hi', $t) ?> · <?= htmlspecialchars($e['lieu']) ?></div>
        </div>
        <?= $badge ?>
    </a>
<?php }
?>

<div class="container my-4" style="max-width: 860px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0" style="color:#274e13;"><i class="bi bi-ticket-perforated"></i> Mes événements</h1>
        <a href="evenements.php" class="btn btn-outline-success rounded-pill"><i class="bi bi-calendar-event"></i> Voir le calendrier</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <h2 class="h5 fw-bold mb-3">À venir</h2>
    <?php if (!$aVenir): ?>
        <div class="card border-0 shadow-sm p-4 text-center text-muted mb-4" style="border-radius: 15px;">
            Vous n'êtes inscrit à aucun événement à venir.
            <a href="evenements.php" class="btn btn-success rounded-pill mt-3 mx-auto px-4">Découvrir les événements</a>
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm mb-4" style="border-radius: 15px; overflow: hidden;">
            <?php foreach ($aVenir as $e) ligneEvenement($e); ?>
        </div>
    <?php endif; ?>

    <?php if ($autres): ?>
        <h2 class="h5 fw-bold mb-3">Historique</h2>
        <div class="list-group shadow-sm" style="border-radius: 15px; overflow: hidden;">
            <?php foreach ($autres as $e) ligneEvenement($e); ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
