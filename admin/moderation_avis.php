<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../points_lib.php';
exigerDroit('moderation');

$onglet = ($_GET['onglet'] ?? 'signales') === 'tous' ? 'tous' : 'signales';
$retour = 'moderation_avis.php?onglet=' . $onglet;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $idAvis = (int) ($_POST['id_avis'] ?? 0);
    $action = $_POST['action'] ?? '';
    $stmtAvis = $pdo->prepare("SELECT id_user, id_livre FROM avis WHERE id_avis = ?");
    $stmtAvis->execute([$idAvis]);
    $avisCible = $stmtAvis->fetch();

    if ($action === 'masquer' || $action === 'afficher') {
        $pdo->prepare("UPDATE avis SET statut = ? WHERE id_avis = ?")->execute([$action === 'masquer' ? 'masque' : 'visible', $idAvis]);
        $pdo->prepare("UPDATE avis_signalement SET traite = 1 WHERE id_avis = ?")->execute([$idAvis]);
        if ($avisCible && $action === 'masquer') {
            changerStatutMouvement($pdo, (int) $avisCible['id_user'], 'avis', 'livre:' . $avisCible['id_livre'], ['en_attente'], 'refuse');
        }
        insertLog('MODERATION', "Avis #$idAvis " . ($action === 'masquer' ? 'masqué' : 'réaffiché'));
        rediriger($retour, 'success', $action === 'masquer' ? "Avis masqué (il reste archivé)." : "Avis de nouveau visible.");
    }
    if ($action === 'ignorer') {
        $pdo->prepare("UPDATE avis_signalement SET traite = 1 WHERE id_avis = ?")->execute([$idAvis]);
        insertLog('MODERATION', "Signalements de l'avis #$idAvis ignorés");
        rediriger($retour, 'success', "Signalements classés sans suite.");
    }
    if ($action === 'supprimer' && aLeDroit('admin')) {
        $pdo->prepare("DELETE FROM avis WHERE id_avis = ?")->execute([$idAvis]);
        if ($avisCible) {
            changerStatutMouvement($pdo, (int) $avisCible['id_user'], 'avis', 'livre:' . $avisCible['id_livre'], ['en_attente', 'valide'], 'annule');
        }
        insertLog('MODERATION', "Avis #$idAvis supprimé définitivement");
        rediriger($retour, 'success', "Avis supprimé définitivement.");
    }
    rediriger($retour);
}

$requete = "
    SELECT a.*, u.pseudo, l.titre AS titre_livre,
           COUNT(CASE WHEN s.traite = 0 THEN 1 END) AS nb_signalements,
           GROUP_CONCAT(DISTINCT CASE WHEN s.traite = 0 THEN s.motif END) AS motifs
    FROM avis a
    INNER JOIN user u ON u.id = a.id_user
    INNER JOIN livre l ON l.id_livre = a.id_livre
    LEFT JOIN avis_signalement s ON s.id_avis = a.id_avis
    GROUP BY a.id_avis, u.pseudo, l.titre
    " . ($onglet === 'signales' ? "HAVING nb_signalements > 0 ORDER BY nb_signalements DESC, a.date_creation DESC"
                                : "ORDER BY a.date_creation DESC LIMIT 100");
$avisListe = $pdo->query($requete)->fetchAll();
$nbSignales = compteursEquipe($pdo)['avis'];

$titrePage = 'Avis';
$pageActive = 'moderation_avis';
include __DIR__ . '/_haut.php';
echo etoilesStyle();
?>

<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $onglet === 'signales' ? 'active bg-success' : 'text-success' ?>" href="?onglet=signales">
            Signalés <?php if ($nbSignales): ?><span class="badge bg-danger ms-1"><?= $nbSignales ?></span><?php endif; ?>
        </a>
    </li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'tous' ? 'active bg-success' : 'text-success' ?>" href="?onglet=tous">Tous les avis</a></li>
</ul>

<?php if (!$avisListe): ?>
    <div class="carte p-5 text-center">
        <i class="bi bi-shield-check text-success fs-1"></i>
        <p class="text-muted mb-0 mt-2"><?= $onglet === 'signales' ? 'Aucun avis signalé, tout est en ordre.' : 'Aucun avis pour le moment.' ?></p>
    </div>
<?php endif; ?>

<?php foreach ($avisListe as $a): ?>
    <div class="carte p-3 mb-3 <?= $a['statut'] === 'masque' ? 'opacity-75' : '' ?>">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <a href="../detail_livre.php?id=<?= $a['id_livre'] ?>#avis" target="_blank" class="fw-bold text-dark"><?= e($a['titre_livre']) ?></a>
                <span class="text-muted small"> · par <?= e($a['pseudo']) ?> · <?= date('d/m/Y', strtotime($a['date_creation'])) ?></span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($a['statut'] === 'masque'): ?><span class="badge bg-secondary">Masqué</span><?php endif; ?>
                <?php if ($a['nb_signalements'] > 0): ?>
                    <span class="badge bg-danger"><i class="bi bi-flag-fill"></i> <?= $a['nb_signalements'] ?> signalement<?= $a['nb_signalements'] > 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($a['motifs']): ?>
            <div class="small text-danger mt-1">
                Motifs : <?= e(implode(', ', array_map(fn($m) => AVIS_MOTIFS[$m] ?? $m, explode(',', $a['motifs'])))) ?>
            </div>
        <?php endif; ?>
        <div class="mt-2"><?= etoiles((float) $a['note']) ?> <?php if ($a['titre']): ?><strong class="ms-1"><?= e($a['titre']) ?></strong><?php endif; ?></div>
        <p class="small mt-1 mb-3" style="white-space: pre-line;"><?= e($a['commentaire']) ?></p>

        <div class="d-flex flex-wrap gap-2">
            <?php foreach ([
                ['masquer', 'Masquer', 'btn-outline-warning', $a['statut'] === 'visible'],
                ['afficher', 'Réafficher', 'btn-outline-success', $a['statut'] === 'masque'],
                ['ignorer', 'Ignorer les signalements', 'btn-outline-secondary', $a['nb_signalements'] > 0],
                ['supprimer', 'Supprimer', 'btn-outline-danger', aLeDroit('admin')],
            ] as [$act, $libelle, $classe, $visible]): ?>
                <?php if ($visible): ?>
                    <form method="POST" <?= $act === 'supprimer' ? 'data-confirmer="Supprimer définitivement cet avis ?"' : '' ?>>
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="<?= $act ?>">
                        <input type="hidden" name="id_avis" value="<?= $a['id_avis'] ?>">
                        <button class="btn btn-sm rounded-pill <?= $classe ?>"><?= $libelle ?></button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php include __DIR__ . '/_bas.php'; ?>
