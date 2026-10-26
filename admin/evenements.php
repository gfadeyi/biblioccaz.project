<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../evenements_lib.php';
exigerDroit('evenements');

$onglets = ['proposes' => 'Proposés', 'a_venir' => 'À venir', 'passes' => 'Passés', 'refuses' => 'Refusés', 'annules' => 'Annulés'];
$onglet = isset($onglets[$_GET['onglet'] ?? '']) ? $_GET['onglet'] : 'a_venir';
$condition = match ($onglet) {
    'proposes' => "e.statut = 'en_attente'",
    'passes'   => "e.statut = 'publie' AND e.date_fin < NOW()",
    'refuses'  => "e.statut IN ('refuse', 'retire')",
    'annules'  => "e.statut = 'annule'",
    default    => "e.statut = 'publie' AND e.date_fin >= NOW()",
};
$ordre = match ($onglet) {
    'proposes' => 'e.date_creation ASC',
    'a_venir'  => 'e.date_debut ASC',
    'refuses'  => 'COALESCE(e.date_validation, e.date_annulation) DESC',
    default    => 'e.date_debut DESC',
};

$evenements = [];
$nbProposes = 0;
$tablePresente = true;
try {
    $evenements = $pdo->query("
        SELECT e.*,
               SUM(i.statut = 'inscrit') AS nb_inscrits,
               SUM(i.statut = 'attente') AS nb_attente,
               SUM(i.statut = 'inscrit' AND i.present = 1) AS nb_presents,
               IF(e.statut IN ('en_attente', 'refuse', 'retire') OR e.date_validation IS NOT NULL,
                  COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.prenom, u.nom)), ''), u.pseudo), NULL) AS propose_par
        FROM evenement e
        LEFT JOIN evenement_inscription i ON i.id_evenement = e.id_evenement
        LEFT JOIN user u ON u.id = e.id_createur
        WHERE $condition
        GROUP BY e.id_evenement
        ORDER BY $ordre")->fetchAll();
    $nbProposes = (int) $pdo->query("SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'")->fetchColumn();
} catch (PDOException $e) {
    $tablePresente = false;
}

$titrePage = 'Événements';
$pageActive = 'evenements';
$actionsPage = [['evenement.php', 'plus-circle', 'Nouvel événement', 'btn-vert']];
include __DIR__ . '/_haut.php';
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning">Les tables des événements n'existent pas encore : importez <code>sql/maj_evenements.sql</code> et <code>sql/maj_evenements_auteurs.sql</code>.</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 onglets-evenements">
    <?php foreach ($onglets as $cle => $libelle): ?>
        <li class="nav-item">
            <a class="nav-link <?= $onglet === $cle ? 'active bg-success' : 'text-success' ?>" href="?onglet=<?= $cle ?>" <?= $onglet === $cle ? 'aria-current="page"' : '' ?>>
                <?= $libelle ?>
                <?php if ($cle === 'proposes' && $nbProposes > 0): ?><span class="badge rounded-pill <?= $onglet === $cle ? 'bg-light text-success' : 'bg-success' ?> ms-1"><?= $nbProposes ?></span><?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($onglet === 'proposes'): ?>
    <p class="small text-muted"><i class="bi bi-info-circle"></i> Événements proposés par les auteurs : ouvrez-les pour les vérifier, les ajuster, puis les valider ou les refuser avec un motif. L'auteur reçoit un mail à chaque décision.</p>
<?php endif; ?>

<div class="carte">
    <div class="carte-entete"><?= count($evenements) ?> événement<?= count($evenements) > 1 ? 's' : '' ?></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th>Date</th><th>Événement</th>
                    <?php if (in_array($onglet, ['proposes', 'refuses'], true)): ?>
                        <th class="text-center">Places</th><th><?= $onglet === 'proposes' ? 'Reçu le' : 'Décision' ?></th>
                    <?php else: ?>
                        <th class="text-center">Inscrits</th><th class="text-center">Attente</th>
                    <?php endif; ?>
                    <?php if ($onglet === 'passes'): ?><th class="text-center">Présents</th><?php endif; ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$evenements): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?= $onglet === 'proposes' ? 'Aucune proposition en attente.' : 'Aucun événement dans cette liste.' ?></td></tr>
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
                            <?php if ($e['propose_par']): ?>
                                <div class="small"><i class="bi bi-pen text-success"></i> Proposé par <?= e($e['propose_par']) ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if (in_array($onglet, ['proposes', 'refuses'], true)): ?>
                            <td class="text-center"><?= (int) $e['places_max'] ?></td>
                            <td class="small">
                                <?php if ($onglet === 'proposes'): ?>
                                    <?= date('d/m/Y', strtotime($e['date_creation'])) ?>
                                <?php elseif ($e['statut'] === 'retire'): ?>
                                    <span class="badge rounded-pill bg-secondary">Retiré par l'auteur</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-danger">Refusé</span> <?= $e['date_validation'] ? date('d/m/Y', strtotime($e['date_validation'])) : '' ?>
                                    <?php if ($e['motif_refus']): ?><div class="text-muted text-truncate" style="max-width: 280px;" title="<?= e($e['motif_refus']) ?>"><?= e($e['motif_refus']) ?></div><?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $inscrits >= $e['places_max'] ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $inscrits ?> / <?= $e['places_max'] ?></span>
                            </td>
                            <td class="text-center"><?= (int) $e['nb_attente'] ?: '<span class="text-muted">—</span>' ?></td>
                        <?php endif; ?>
                        <?php if ($onglet === 'passes'): ?>
                            <td class="text-center small"><?= (int) $e['nb_presents'] ?> <span class="text-muted">(<?= $taux ?> %)</span></td>
                        <?php endif; ?>
                        <td class="text-end text-nowrap">
                            <?php if ($e['statut'] === 'en_attente'): ?>
                                <a href="evenement.php?id=<?= $e['id_evenement'] ?>" class="btn btn-sm btn-vert"><i class="bi bi-search"></i> Examiner</a>
                            <?php elseif (in_array($e['statut'], ['publie', 'annule'], true)): ?>
                                <a href="evenement_participants.php?id=<?= $e['id_evenement'] ?>" class="btn btn-sm btn-outline-success" title="Participants et présences"><i class="bi bi-people"></i> Participants</a>
                                <?php if ($e['statut'] === 'publie'): ?>
                                    <a href="evenement.php?id=<?= $e['id_evenement'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier" aria-label="Modifier"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <a href="../evenement.php?id=<?= $e['id_evenement'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir sur le site" aria-label="Voir sur le site"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
