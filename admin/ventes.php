<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../ventes_lib.php';
exigerDroit('moderation');

$onglets = [
    'surveiller' => ['À surveiller', "d.en_litige = 1 OR (d.statut = 'acceptee' AND d.date_acceptation < NOW() - INTERVAL " . VENTE_RETARD_JOURS . " DAY)"],
    'en_cours'   => ['En cours', "d.statut IN ('en_attente', 'en_file', 'acceptee')"],
    'conclues'   => ['Conclues', "d.statut = 'validee'"],
    'closes'     => ['Refusées ou annulées', "d.statut IN ('refusee', 'annulee', 'expiree', 'annulee_equipe')"],
];
$onglet = isset($onglets[$_GET['onglet'] ?? '']) ? $_GET['onglet'] : 'surveiller';
$q = trim($_GET['q'] ?? '');
$idFiltre = (int) ($_GET['id'] ?? 0);
$retour = 'ventes.php?onglet=' . $onglet . ($q !== '' ? '&q=' . urlencode($q) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $idDemande = (int) ($_POST['id_demande'] ?? 0);
    $idModerateur = (int) $_SESSION['user_id'];
    [$type, $message] = match ($_POST['action'] ?? '') {
        'valider' => validerVenteParEquipe($pdo, $idDemande, $idModerateur),
        'annuler' => annulerVenteParEquipe($pdo, $idDemande, $idModerateur, (string) ($_POST['motif'] ?? '')),
        'classer' => classerLitige($pdo, $idDemande, $idModerateur),
        default => ['danger', "Action inconnue."],
    };
    rediriger($retour, $type, $message);
}

$tablePresente = true;
$ventes = [];
$nombres = array_fill_keys(array_keys($onglets), 0);
try {
    expirerDemandes($pdo);
    foreach ($onglets as $cle => [, $condition]) {
        $nombres[$cle] = (int) $pdo->query("SELECT COUNT(*) FROM demande_achat d WHERE $condition")->fetchColumn();
    }
    $sql = "
        SELECT d.*, l.titre, l.couverture, a.pseudo AS acheteur_pseudo, v.pseudo AS vendeur_pseudo, s.pseudo AS signale_par,
               (SELECT MAX(c.id_conversation) FROM conversation c WHERE c.type_objet = 'vente' AND c.id_objet = d.id_demande) AS id_conversation,
               (SELECT p.fichier FROM exemplaire_photo p WHERE p.id_exemplaire = d.id_annonce ORDER BY p.ordre, p.id_photo LIMIT 1) AS photo
        FROM demande_achat d
        INNER JOIN exemplaire e ON e.id_exemplaire = d.id_annonce
        INNER JOIN livre l ON l.id_livre = e.id_livre
        INNER JOIN `user` a ON a.id = d.id_acheteur
        INNER JOIN `user` v ON v.id = d.id_vendeur
        LEFT JOIN `user` s ON s.id = d.id_signale_par
        WHERE (" . ($idFiltre ? 'd.id_demande = ?' : $onglets[$onglet][1]) . ")";
    $parametres = $idFiltre ? [$idFiltre] : [];
    if ($q !== '') {
        $sql .= " AND (l.titre LIKE ? OR a.pseudo LIKE ? OR v.pseudo LIKE ?)";
        array_push($parametres, ...array_fill(0, 3, '%' . $q . '%'));
    }
    $stmt = $pdo->prepare($sql . " ORDER BY d.en_litige DESC, COALESCE(d.date_litige, d.date_fin, d.date_acceptation, d.date_demande) DESC LIMIT 100");
    $stmt->execute($parametres);
    $ventes = $stmt->fetchAll();
} catch (PDOException $e) {
    $tablePresente = false;
}

$titrePage = 'Ventes entre lecteurs';
$pageActive = 'ventes';
$sousTitrePage = "Suivi des demandes d'achat, des ventes en retard et des litiges. Le paiement se fait entre membres : l'équipe arbitre, valide ou annule.";
include __DIR__ . '/_haut.php';
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning">Les ventes entre lecteurs ne sont pas encore installées : importez <code>sql/maj_ventes_particuliers.sql</code>.</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 flex-wrap">
    <?php foreach ($onglets as $cle => [$libelle]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $onglet === $cle && !$idFiltre ? 'active bg-success' : 'text-success' ?>" href="?onglet=<?= $cle ?>">
                <?= e($libelle) ?> <span class="badge rounded-pill <?= $onglet === $cle ? 'bg-light text-success' : 'bg-success-subtle text-success-emphasis' ?>"><?= $nombres[$cle] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<form class="d-flex gap-2 mb-3" method="GET">
    <input type="hidden" name="onglet" value="<?= e($onglet) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher une vente" style="max-width: 320px;" placeholder="Titre ou pseudo">
    <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
</form>

<?php if (!$ventes): ?>
    <div class="carte p-5 text-center">
        <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
        <p class="text-muted mb-0 mt-2"><?= $onglet === 'surveiller' ? 'Aucun litige ni vente en retard.' : 'Aucune vente dans cette liste.' ?></p>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($ventes as $v): ?>
        <?php
        $id = (int) $v['id_demande'];
        $enRetard = venteEnRetard($v);
        $annulable = in_array($v['statut'], ['en_attente', 'en_file', 'acceptee', 'validee'], true);
        ?>
        <div class="col-xl-6">
            <article class="carte p-3 h-100 d-flex flex-column vente-admin<?= (int) $v['en_litige'] ? ' vente-admin--litige' : '' ?>">
                <div class="d-flex gap-3">
                    <img src="<?= e(urlPhotoAnnonce($v['photo'], $v['couverture'], '../')) ?>" alt="" class="vente-admin__visuel" loading="lazy">
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="min-w-0">
                                <a href="../annonce.php?id=<?= (int) $v['id_annonce'] ?>" class="fw-bold text-decoration-none" target="_blank" rel="noopener"><?= e($v['titre']) ?></a>
                                <div class="small text-muted">Demande n° <?= $id ?> · <?= date('d/m/Y', strtotime($v['date_demande'])) ?></div>
                            </div>
                            <span class="fw-bold text-success"><?= formaterPrix($v['prix']) ?></span>
                        </div>
                        <div class="small mt-1">
                            Vendeur <strong>@<?= e($v['vendeur_pseudo']) ?></strong> → acheteur <strong>@<?= e($v['acheteur_pseudo']) ?></strong>
                            · <?= e(MODES_REMISE[$v['mode_remise']][0] ?? '') ?>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <span class="badge rounded-pill <?= in_array($v['statut'], ['validee', 'acceptee'], true) ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-dark border' ?>"><?= e(STATUTS_DEMANDE[$v['statut']][0] ?? $v['statut']) ?></span>
                            <?php if (receptionAConfirmer($v)): ?><span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Réception à confirmer avant le <?= dateLimiteConfirmation($v) ?></span><?php endif; ?>
                            <?php if ($enRetard): ?><span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"><i class="bi bi-alarm" aria-hidden="true"></i> Acceptée le <?= date('d/m/Y', strtotime($v['date_acceptation'])) ?></span><?php endif; ?>
                            <?php if ((int) $v['en_litige']): ?><span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"><i class="bi bi-flag" aria-hidden="true"></i> Litige</span><?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ((int) $v['en_litige']): ?>
                    <blockquote class="message-signale mt-3 mb-0">
                        <span class="small text-muted d-block mb-1">Signalé par @<?= e((string) $v['signale_par']) ?> le <?= date('d/m/Y à H:i', strtotime($v['date_litige'])) ?></span>
                        <?= nl2br(e((string) $v['motif_litige'])) ?>
                    </blockquote>
                <?php elseif ($v['motif'] && !in_array($v['statut'], STATUTS_DEMANDE_ACTIFS, true)): ?>
                    <p class="small text-muted mt-3 mb-0"><strong>Motif :</strong> <?= e($v['motif']) ?></p>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 mt-3 pt-2 mt-auto align-items-start">
                    <?php if ($v['id_conversation']): ?>
                        <a href="conversation.php?id=<?= (int) $v['id_conversation'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots" aria-hidden="true"></i> Conversation</a>
                    <?php endif; ?>
                    <?php if ($v['statut'] === 'validee'): ?>
                        <a href="recu_vente.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Reçu</a>
                    <?php endif; ?>
                    <?php if ($v['statut'] === 'acceptee' || receptionAConfirmer($v)): ?>
                        <form method="POST" data-confirmer="<?= $v['statut'] === 'acceptee' ? 'Conclure et confirmer la vente à la place des membres ? Le livre sera marqué vendu et le vendeur recevra ses points.' : 'Confirmer la vente à la place de l\'acheteur ? Les points du vendeur seront crédités.' ?>">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="valider">
                            <input type="hidden" name="id_demande" value="<?= $id ?>">
                            <button class="btn btn-sm btn-success fw-bold"><i class="bi bi-bag-check" aria-hidden="true"></i> <?= $v['statut'] === 'acceptee' ? 'Valider la vente' : 'Confirmer la vente' ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ((int) $v['en_litige']): ?>
                        <form method="POST">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="classer">
                            <input type="hidden" name="id_demande" value="<?= $id ?>">
                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2" aria-hidden="true"></i> Classer le litige</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($annulable): ?>
                        <details class="annonce-admin__motif">
                            <summary class="btn btn-sm btn-outline-danger"><i class="bi bi-shield-x" aria-hidden="true"></i> Annuler</summary>
                            <form method="POST" class="mt-2">
                                <?= champCsrf() ?>
                                <input type="hidden" name="action" value="annuler">
                                <input type="hidden" name="id_demande" value="<?= $id ?>">
                                <label class="form-label small" for="motif-<?= $id ?>">Motif envoyé aux deux membres<?= $v['statut'] === 'validee' ? ' (les points du vendeur seront annulés)' : '' ?></label>
                                <textarea id="motif-<?= $id ?>" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="500" required></textarea>
                                <button class="btn btn-sm btn-danger">Annuler la transaction</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
