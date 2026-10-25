<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../evenements_lib.php';
exigerDroit('evenements');

$onglet = in_array($_GET['onglet'] ?? '', ['passes', 'annules'], true) ? $_GET['onglet'] : 'a_venir';
$condition = match ($onglet) {
    'passes'  => "e.statut = 'publie' AND e.date_fin < NOW()",
    'annules' => "e.statut = 'annule'",
    default   => "e.statut = 'publie' AND e.date_fin >= NOW()",
};

$evenements = [];
$tablePresente = true;
try {
    $evenements = $pdo->query("
        SELECT e.*,
               SUM(i.statut = 'inscrit') AS nb_inscrits,
               SUM(i.statut = 'attente') AS nb_attente,
               SUM(i.statut = 'inscrit' AND i.present = 1) AS nb_presents
        FROM evenement e
        LEFT JOIN evenement_inscription i ON i.id_evenement = e.id_evenement
        WHERE $condition
        GROUP BY e.id_evenement
        ORDER BY e.date_debut " . ($onglet === 'a_venir' ? 'ASC' : 'DESC'))->fetchAll();
} catch (PDOException $e) {
    $tablePresente = false;
}

$titrePage = 'Événements';
$pageActive = 'evenements';
include __DIR__ . '/_haut.php';
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning">Les tables des événements n'existent pas encore : importez <code>sql/maj_evenements.sql</code> dans phpMyAdmin.</div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills">
        <?php foreach (['a_venir' => 'À venir', 'passes' => 'Passés', 'annules' => 'Annulés'] as $cle => $libelle): ?>
            <li class="nav-item"><a class="nav-link <?= $onglet === $cle ? 'active bg-success' : 'text-success' ?>" href="?onglet=<?= $cle ?>"><?= $libelle ?></a></li>
        <?php endforeach; ?>
    </ul>
    <a href="evenement.php" class="btn btn-vert"><i class="bi bi-plus-circle me-1"></i> Nouvel événement</a>
</div>

<div class="carte">
    <div class="carte-entete"><?= count($evenements) ?> événement<?= count($evenements) > 1 ? 's' : '' ?></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th>Date</th><th>Événement</th>
                    <th class="text-center">Inscrits</th><th class="text-center">Attente</th>
                    <?php if ($onglet === 'passes'): ?><th class="text-center">Présents</th><?php endif; ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$evenements): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun événement dans cette liste.</td></tr>
                <?php endif; ?>
                <?php foreach ($evenements as $e): ?>
                    <?php $inscrits = (int) $e['nb_inscrits']; $taux = $inscrits ? round(100 * $e['nb_presents'] / $inscrits) : 0; ?>
                    <tr>
                        <td class="small text-nowrap">
                            <strong><?= date('d/m/Y', strtotime($e['date_debut'])) ?></strong><br>
                            <span class="text-muted"><?= date('G\hi', strtotime($e['date_debut'])) ?> – <?= date('G\hi', strtotime($e['date_fin'])) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= e($e['titre']) ?></div>
                            <div class="small text-muted"><?= EVT_TYPES[$e['type']][0] ?? e($e['type']) ?> · <?= e($e['lieu']) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?= $inscrits >= $e['places_max'] ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $inscrits ?> / <?= $e['places_max'] ?></span>
                        </td>
                        <td class="text-center"><?= (int) $e['nb_attente'] ?: '<span class="text-muted">—</span>' ?></td>
                        <?php if ($onglet === 'passes'): ?>
                            <td class="text-center small"><?= (int) $e['nb_presents'] ?> <span class="text-muted">(<?= $taux ?> %)</span></td>
                        <?php endif; ?>
                        <td class="text-end text-nowrap">
                            <a href="evenement_participants.php?id=<?= $e['id_evenement'] ?>" class="btn btn-sm btn-outline-success" title="Participants et présences"><i class="bi bi-people"></i> Participants</a>
                            <?php if ($e['statut'] === 'publie'): ?>
                                <a href="evenement.php?id=<?= $e['id_evenement'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <a href="../evenement.php?id=<?= $e['id_evenement'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir sur le site"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
