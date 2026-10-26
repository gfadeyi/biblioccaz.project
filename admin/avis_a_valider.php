<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../points_lib.php';
exigerDroit('moderation');

function validerAvis(PDO $pdo, array $avis): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE avis SET date_validation = NOW(), id_validateur = ? WHERE id_avis = ? AND date_validation IS NULL");
        $stmt->execute([$_SESSION['user_id'], $avis['id_avis']]);
        if ($stmt->rowCount() > 0) {
            validerPointsAvis($pdo, (int) $avis['id_user'], (int) $avis['id_livre']);
            verifierBadges($pdo, (int) $avis['id_user']);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    insertLog('MODERATION', "Avis #{$avis['id_avis']} validé");
}

$requeteAvis = "
    SELECT a.*, u.pseudo, l.titre AS titre_livre, pm.statut AS statut_points
    FROM avis a
    INNER JOIN user u ON u.id = a.id_user
    INNER JOIN livre l ON l.id_livre = a.id_livre
    LEFT JOIN point_mouvement pm ON pm.id_user = a.id_user AND pm.type = 'avis' AND pm.reference = CONCAT('livre:', a.id_livre)
    WHERE a.statut = 'visible' AND a.date_validation IS NULL";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost('avis_a_valider.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'tout_valider') {
        $tous = $pdo->query($requeteAvis)->fetchAll();
        foreach ($tous as $avis) {
            validerAvis($pdo, $avis);
        }
        rediriger('avis_a_valider.php', 'success', count($tous) . " avis validé(s).");
    }

    $stmt = $pdo->prepare($requeteAvis . " AND a.id_avis = ?");
    $stmt->execute([(int) ($_POST['id_avis'] ?? 0)]);
    $avis = $stmt->fetch();
    if (!$avis) {
        rediriger('avis_a_valider.php', 'warning', "Cet avis a déjà été traité.");
    }
    if ($action === 'valider') {
        validerAvis($pdo, $avis);
        rediriger('avis_a_valider.php', 'success', "Avis de @{$avis['pseudo']} validé" . ($avis['statut_points'] === 'en_attente' ? ' : ' . AVIS_POINTS . ' points crédités.' : '.'));
    }
    if ($action === 'refuser') {
        $pdo->prepare("UPDATE avis SET statut = 'masque', date_validation = NOW(), id_validateur = ? WHERE id_avis = ?")->execute([$_SESSION['user_id'], $avis['id_avis']]);
        changerStatutMouvement($pdo, (int) $avis['id_user'], 'avis', 'livre:' . $avis['id_livre'], ['en_attente'], 'refuse');
        insertLog('MODERATION', "Avis #{$avis['id_avis']} refusé et masqué");
        rediriger('avis_a_valider.php', 'success', "Avis refusé : il est masqué et ses points ne seront pas crédités.");
    }
    rediriger('avis_a_valider.php');
}

$avisListe = $pdo->query($requeteAvis . " ORDER BY a.date_creation")->fetchAll();
$avecPoints = count(array_filter($avisListe, fn($a) => $a['statut_points'] === 'en_attente'));

$titrePage = 'Avis à valider';
$sousTitrePage = 'Les avis sont publiés tout de suite ; les points du lecteur attendent votre validation.';
$pageActive = 'avis_a_valider';
$filAriane = [[null, 'Communauté']];
include __DIR__ . '/_haut.php';
echo etoilesStyle();
?>

<?php if ($avisListe): ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex flex-wrap gap-2">
        <span class="etiquette etiquette-compteur"><?= count($avisListe) ?> avis en attente</span>
        <span class="etiquette etiquette-neutre"><?= $avecPoints ?> avec des points à créditer</span>
    </div>
    <?php if (count($avisListe) > 1): ?>
        <form method="POST" data-confirmer="Valider les <?= count($avisListe) ?> avis en attente et créditer leurs points ?" data-bouton-confirmer="Tout valider">
            <?= champCsrf() ?>
            <input type="hidden" name="action" value="tout_valider">
            <button class="btn btn-outline-success btn-sm"><i class="bi bi-check2-all" aria-hidden="true"></i> Tout valider</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$avisListe): ?>
    <div class="carte etat-vide">
        <div class="icone-vide"><i class="bi bi-patch-check" aria-hidden="true"></i></div>
        <strong>Aucun avis en attente</strong>Les nouveaux avis apparaîtront ici pour valider les points de leurs auteurs.
    </div>
<?php endif; ?>

<?php foreach ($avisListe as $a): ?>
    <article class="carte p-3 mb-3 avis-a-valider">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <a href="../detail_livre.php?id=<?= (int) $a['id_livre'] ?>#avis" target="_blank" rel="noopener" class="fw-bold"><?= e($a['titre_livre']) ?></a>
                <span class="text-muted small"> · par @<?= e($a['pseudo']) ?> · <?= date('d/m/Y à H:i', strtotime($a['date_creation'])) ?></span>
            </div>
            <?php if ($a['statut_points'] === 'en_attente'): ?>
                <span class="etiquette"><i class="bi bi-hourglass-split" aria-hidden="true"></i> <?= AVIS_POINTS ?> points en attente</span>
            <?php else: ?>
                <span class="etiquette etiquette-neutre" title="Livre non acheté, avis déjà rémunéré pour ce livre ou quota mensuel atteint">Sans points</span>
            <?php endif; ?>
        </div>
        <div class="mt-2"><?= etoiles((float) $a['note']) ?> <?php if ($a['titre']): ?><strong class="ms-1"><?= e($a['titre']) ?></strong><?php endif; ?></div>
        <p class="small mt-1 mb-3" style="white-space: pre-line;"><?= e($a['commentaire']) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="valider">
                <input type="hidden" name="id_avis" value="<?= (int) $a['id_avis'] ?>">
                <button class="btn btn-vert btn-sm"><i class="bi bi-check-lg" aria-hidden="true"></i> Valider</button>
            </form>
            <form method="POST" data-confirmer="Refuser cet avis ? Il sera masqué et ses points ne seront pas crédités." data-bouton-confirmer="Refuser">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="refuser">
                <input type="hidden" name="id_avis" value="<?= (int) $a['id_avis'] ?>">
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg" aria-hidden="true"></i> Refuser</button>
            </form>
        </div>
    </article>
<?php endforeach; ?>

<?php include __DIR__ . '/_bas.php'; ?>
