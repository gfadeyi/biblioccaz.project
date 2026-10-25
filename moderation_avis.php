<?php
// Modération des avis : avis signalés et liste complète (admin, modérateur, gestionnaire)
require_once 'config.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!in_array($_SESSION['role'] ?? '', ['admin', 'moderateur', 'gestionnaire'], true)) {
    header("Location: login.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idAvis = (int) ($_POST['id_avis'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!verifierCsrf()) {
        $message = "Session expirée, merci de réessayer.";
    } elseif ($action === 'masquer' || $action === 'afficher') {
        $pdo->prepare("UPDATE avis SET statut = ? WHERE id_avis = ?")->execute([$action === 'masquer' ? 'masque' : 'visible', $idAvis]);
        $pdo->prepare("UPDATE avis_signalement SET traite = 1 WHERE id_avis = ?")->execute([$idAvis]);
        insertLog('MODERATION', "Avis #$idAvis " . ($action === 'masquer' ? 'masqué' : 'réaffiché'));
        $message = $action === 'masquer' ? "Avis masqué (il reste archivé)." : "Avis de nouveau visible.";
    } elseif ($action === 'ignorer') {
        $pdo->prepare("UPDATE avis_signalement SET traite = 1 WHERE id_avis = ?")->execute([$idAvis]);
        insertLog('MODERATION', "Signalements de l'avis #$idAvis ignorés");
        $message = "Signalements classés sans suite.";
    } elseif ($action === 'supprimer' && ($_SESSION['role'] ?? '') === 'admin') {
        $pdo->prepare("DELETE FROM avis WHERE id_avis = ?")->execute([$idAvis]);
        insertLog('MODERATION', "Avis #$idAvis supprimé définitivement");
        $message = "Avis supprimé définitivement.";
    }
}

$onglet = ($_GET['onglet'] ?? 'signales') === 'tous' ? 'tous' : 'signales';

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
$nbSignales = (int) $pdo->query("SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0")->fetchColumn();
$csrf = jetonCsrf();

include 'header.php';
echo etoilesStyle();
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Modération des avis</h2>
        <a href="profil.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-2"></i>Mon espace</a>
    </div>

    <?php if ($message): ?><div class="alert alert-info rounded-3"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $onglet === 'signales' ? 'active bg-success' : 'text-success' ?>" href="?onglet=signales">
                Signalés <?php if ($nbSignales): ?><span class="badge bg-danger ms-1"><?= $nbSignales ?></span><?php endif; ?>
            </a>
        </li>
        <li class="nav-item"><a class="nav-link <?= $onglet === 'tous' ? 'active bg-success' : 'text-success' ?>" href="?onglet=tous">Tous les avis</a></li>
    </ul>

    <?php if (!$avisListe): ?>
        <div class="card p-5 text-center shadow-sm border-0" style="border-radius: 15px;">
            <i class="bi bi-shield-check text-success fs-1 mb-2"></i>
            <h5 class="text-secondary mb-0"><?= $onglet === 'signales' ? 'Aucun avis signalé, tout est en ordre.' : 'Aucun avis pour le moment.' ?></h5>
        </div>
    <?php endif; ?>

    <?php foreach ($avisListe as $a): ?>
        <div class="card border-0 shadow-sm mb-3 <?= $a['statut'] === 'masque' ? 'opacity-75' : '' ?>" style="border-radius: 15px;">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <a href="detail_livre.php?id=<?= $a['id_livre'] ?>#avis" class="fw-bold text-dark"><?= htmlspecialchars($a['titre_livre']) ?></a>
                        <span class="text-muted small"> · par <?= htmlspecialchars($a['pseudo']) ?> · <?= date('d/m/Y', strtotime($a['date_creation'])) ?></span>
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
                        Motifs : <?= htmlspecialchars(implode(', ', array_map(fn($m) => AVIS_MOTIFS[$m] ?? $m, explode(',', $a['motifs'])))) ?>
                    </div>
                <?php endif; ?>
                <div class="mt-2"><?= etoiles((float) $a['note']) ?> <?php if ($a['titre']): ?><strong class="ms-1"><?= htmlspecialchars($a['titre']) ?></strong><?php endif; ?></div>
                <p class="small mt-1 mb-3" style="white-space: pre-line;"><?= htmlspecialchars($a['commentaire']) ?></p>

                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ([
                        ['masquer', 'Masquer', 'btn-outline-warning', $a['statut'] === 'visible'],
                        ['afficher', 'Réafficher', 'btn-outline-success', $a['statut'] === 'masque'],
                        ['ignorer', 'Ignorer les signalements', 'btn-outline-secondary', $a['nb_signalements'] > 0],
                        ['supprimer', 'Supprimer', 'btn-outline-danger', ($_SESSION['role'] ?? '') === 'admin'],
                    ] as [$act, $libelle, $classe, $visible]): ?>
                        <?php if ($visible): ?>
                            <form method="POST" <?= $act === 'supprimer' ? 'onsubmit="return confirm(\'Supprimer définitivement cet avis ?\');"' : '' ?>>
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="<?= $act ?>">
                                <input type="hidden" name="id_avis" value="<?= $a['id_avis'] ?>">
                                <button class="btn btn-sm rounded-pill <?= $classe ?>"><?= $libelle ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
