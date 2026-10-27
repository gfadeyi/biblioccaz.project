<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../annonces_lib.php';
exigerDroit('moderation');

$onglets = [
    'a_valider' => ['À valider', "e.statut_annonce = 'en_validation'"],
    'en_ligne'  => ['En ligne', "e.statut_annonce IN ('publiee', 'reservee')"],
    'refusees'  => ['Refusées ou retirées', "e.statut_annonce IN ('refusee', 'retiree')"],
    'toutes'    => ['Toutes', "1 = 1"],
];
$onglet = isset($onglets[$_GET['onglet'] ?? '']) ? $_GET['onglet'] : 'a_valider';
$q = trim($_GET['q'] ?? '');
$retour = 'annonces.php?' . http_build_query(['onglet' => $onglet, 'q' => $q]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $idAnnonce = (int) ($_POST['id_annonce'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'supprimer') {
        if (supprimerAnnonce($pdo, $idAnnonce)) {
            insertLog('ANNONCE', "Suppression de l'annonce #$idAnnonce par l'équipe");
            rediriger($retour, 'success', "L'annonce #$idAnnonce est supprimée, avec ses photos.");
        }
        rediriger($retour, 'warning', "Cette annonce ne peut pas être supprimée : une vente est en cours ou terminée.");
    }
    [$type, $message] = modererAnnonce($pdo, $idAnnonce, $action, (int) $_SESSION['user_id'], (string) ($_POST['motif'] ?? ''));
    rediriger($retour, $type, $message);
}

$sql = "
    SELECT e.*, l.titre, l.auteur, l.couverture, l.is_valide AS livre_valide,
           v.pseudo AS vendeur_pseudo, v.statut AS vendeur_statut, m.pseudo AS moderateur_pseudo
    FROM exemplaire e
    INNER JOIN livre l ON l.id_livre = e.id_livre
    INNER JOIN user v ON v.id = e.id_proprietaire
    LEFT JOIN user m ON m.id = e.id_moderateur
    WHERE e.id_proprietaire IS NOT NULL AND e.type_offre = 'vente' AND " . $onglets[$onglet][1];
$parametres = [];
if ($q !== '') {
    $sql .= " AND (l.titre LIKE ? OR l.auteur LIKE ? OR v.pseudo LIKE ?)";
    $parametres = array_fill(0, 3, '%' . $q . '%');
}
$sql .= $onglet === 'a_valider' ? " ORDER BY e.date_creation ASC" : " ORDER BY COALESCE(e.date_moderation, e.date_creation) DESC LIMIT 100";
$tablePresente = true;
$annonces = [];
$photos = [];
$nombres = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametres);
    $annonces = $stmt->fetchAll();
    $photos = premieresPhotos($pdo, array_column($annonces, 'id_exemplaire'));
    foreach ($onglets as $cle => [, $condition]) {
        $nombres[$cle] = (int) $pdo->query("SELECT COUNT(*) FROM exemplaire e WHERE e.id_proprietaire IS NOT NULL AND e.type_offre = 'vente' AND $condition")->fetchColumn();
    }
} catch (PDOException $e) {
    $tablePresente = false;
}

$toutesPhotos = [];
if ($onglet === 'a_valider' && $annonces) {
    $marqueurs = implode(',', array_fill(0, count($annonces), '?'));
    $stmt = $pdo->prepare("SELECT id_exemplaire, fichier FROM exemplaire_photo WHERE id_exemplaire IN ($marqueurs) ORDER BY ordre, id_photo");
    $stmt->execute(array_column($annonces, 'id_exemplaire'));
    foreach ($stmt->fetchAll() as $photo) {
        $toutesPhotos[(int) $photo['id_exemplaire']][] = $photo['fichier'];
    }
}

function badgeAnnonce(string $statut): string
{
    $classes = [
        'en_validation' => 'badge-annonce-attente',
        'publiee'       => 'bg-success-subtle text-success-emphasis',
        'refusee'       => 'bg-danger-subtle text-danger-emphasis',
        'retiree'       => 'bg-light text-dark border',
        'reservee'      => 'bg-white text-success-emphasis border border-success',
        'vendue'        => 'bg-success text-white',
    ];
    [$libelle, , $icone] = ANNONCE_STATUTS[$statut] ?? [$statut, '', 'question-circle'];
    return '<span class="badge rounded-pill ' . ($classes[$statut] ?? 'bg-light text-dark') . '"><i class="bi bi-' . $icone . '" aria-hidden="true"></i> ' . e($libelle) . '</span>';
}

$titrePage = 'Annonces';
$pageActive = 'annonces';
$sousTitrePage = "Ventes entre particuliers : chaque annonce est vérifiée avant d'être visible sur le site.";
include __DIR__ . '/_haut.php';
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning">Les annonces ne sont pas encore installées : importez <code>sql/maj_annonces.sql</code>.</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 flex-wrap">
    <?php foreach ($onglets as $cle => [$libelle]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $onglet === $cle ? 'active bg-success' : 'text-success' ?>" href="?<?= http_build_query(['onglet' => $cle, 'q' => $q]) ?>">
                <?= e($libelle) ?> <span class="badge rounded-pill <?= $onglet === $cle ? 'bg-light text-success' : 'bg-success-subtle text-success-emphasis' ?>"><?= $nombres[$cle] ?? 0 ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<form class="d-flex gap-2 mb-3" method="GET">
    <input type="hidden" name="onglet" value="<?= e($onglet) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher une annonce" style="max-width: 320px;" placeholder="Titre, auteur ou pseudo du vendeur">
    <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
</form>

<?php if (!$annonces): ?>
    <div class="carte p-5 text-center">
        <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
        <p class="text-muted mb-0 mt-2"><?= $onglet === 'a_valider' ? 'Aucune annonce en attente de validation.' : 'Aucune annonce dans cette liste.' ?></p>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($annonces as $a): ?>
        <?php
        $id = (int) $a['id_exemplaire'];
        $statut = (string) $a['statut_annonce'];
        $vendeurSuspendu = in_array($a['vendeur_statut'], STATUTS_COMPTE_SUSPENDU, true);
        $imagesAnnonce = $toutesPhotos[$id] ?? (isset($photos[$id]) ? [$photos[$id]] : []);
        ?>
        <div class="col-xl-6">
            <article class="carte p-3 h-100 d-flex flex-column annonce-admin">
                <div class="d-flex gap-3">
                    <div class="annonce-admin__photos">
                        <?php if ($imagesAnnonce): ?>
                            <?php foreach ($imagesAnnonce as $index => $fichier): ?>
                                <a href="<?= e(urlPhotoAnnonce($fichier, null, '../')) ?>" target="_blank" rel="noopener" class="<?= $index ? 'annonce-admin__vignette' : 'annonce-admin__principale' ?>">
                                    <img src="<?= e(urlPhotoAnnonce($fichier, null, '../')) ?>" alt="Photo <?= $index + 1 ?> de l'annonce">
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="annonce-admin__principale"><img src="<?= imageLivre($a['couverture']) ?>" alt=""></span>
                            <span class="small text-muted d-block mt-1">Sans photo</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <h2 class="h6 fw-bold mb-1"><?= e($a['titre']) ?></h2>
                                <p class="small text-muted mb-1"><?= e((string) $a['auteur']) ?> · par <strong>@<?= e($a['vendeur_pseudo']) ?></strong></p>
                            </div>
                            <span class="fs-5 fw-bold text-success text-nowrap"><?= formaterPrix($a['prix']) ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <?= badgeAnnonce($statut) ?>
                            <span class="badge rounded-pill bg-light text-dark border"><?= e(libelleEtat($a['etat'])) ?></span>
                            <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-<?= MODES_REMISE[$a['mode_remise']][1] ?? 'question' ?>" aria-hidden="true"></i> <?= e(libelleModeRemise($a['mode_remise'], $a['ville_remise'])) ?></span>
                            <?php if (!(int) $a['livre_valide']): ?>
                                <span class="badge rounded-pill badge-annonce-attente"><i class="bi bi-journal-plus" aria-hidden="true"></i> Nouvelle fiche livre</span>
                            <?php endif; ?>
                            <?php if ($vendeurSuspendu): ?>
                                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"><i class="bi bi-person-slash" aria-hidden="true"></i> Vendeur suspendu</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($a['description']): ?>
                            <blockquote class="annonce-admin__description"><?= nl2br(e($a['description'])) ?></blockquote>
                        <?php else: ?>
                            <p class="small text-muted fst-italic mb-2">Pas de description.</p>
                        <?php endif; ?>
                        <?php if ($a['motif_moderation']): ?>
                            <p class="small mb-2"><strong>Motif :</strong> <?= e($a['motif_moderation']) ?></p>
                        <?php endif; ?>
                        <p class="small text-muted mb-0">
                            Déposée le <?= $a['date_creation'] ? date('d/m/Y à H:i', strtotime($a['date_creation'])) : '—' ?>
                            <?php if ($a['date_moderation']): ?>
                                · traitée le <?= date('d/m/Y', strtotime($a['date_moderation'])) ?><?= $a['moderateur_pseudo'] ? ' par @' . e($a['moderateur_pseudo']) : '' ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="annonce-admin__actions mt-auto pt-3">
                    <?php if ($statut === 'en_validation'): ?>
                        <form method="POST">
                            <?= champCsrf() ?>
                            <input type="hidden" name="id_annonce" value="<?= $id ?>">
                            <input type="hidden" name="action" value="valider">
                            <button class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg" aria-hidden="true"></i> <?= (int) $a['livre_valide'] ? "Valider l'annonce" : "Valider l'annonce et la fiche" ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array($statut, ANNONCE_STATUTS_MODIFIABLES, true)): ?>
                        <a href="annonce.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier</a>
                    <?php endif; ?>
                    <?php if ($statut === 'publiee'): ?>
                        <a href="../annonce.php?id=<?= $id ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye" aria-hidden="true"></i> Voir sur le site</a>
                    <?php endif; ?>
                    <?php if (!in_array($statut, ['reservee', 'vendue'], true)): ?>
                        <form method="POST" data-confirmer="Supprimer définitivement l'annonce « <?= e($a['titre']) ?> » et ses photos ? Le vendeur ne sera pas prévenu." data-titre-confirmer="Supprimer l'annonce" data-bouton-confirmer="Supprimer">
                            <?= champCsrf() ?>
                            <input type="hidden" name="id_annonce" value="<?= $id ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Supprimer l'annonce"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array($statut, ['en_validation', 'publiee'], true)): ?>
                        <details class="annonce-admin__motif">
                            <summary class="btn btn-sm btn-outline-danger"><i class="bi bi-<?= $statut === 'publiee' ? 'archive' : 'x-lg' ?>" aria-hidden="true"></i> <?= $statut === 'publiee' ? 'Retirer du site' : 'Refuser' ?></summary>
                            <form method="POST" class="mt-2">
                                <?= champCsrf() ?>
                                <input type="hidden" name="id_annonce" value="<?= $id ?>">
                                <input type="hidden" name="action" value="<?= $statut === 'publiee' ? 'retirer' : 'refuser' ?>">
                                <label for="motif-<?= $id ?>" class="form-label small fw-bold">Motif envoyé au vendeur</label>
                                <textarea id="motif-<?= $id ?>" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="<?= ANNONCE_MOTIF_MAX ?>" required
                                          placeholder="Ex. : photos floues, prix incohérent, livre interdit à la vente…"></textarea>
                                <button class="btn btn-sm btn-danger"><?= $statut === 'publiee' ? 'Retirer et prévenir le vendeur' : 'Refuser et prévenir le vendeur' ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
