<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../annonces_lib.php';
exigerDroit('moderation');

$idAnnonce = (int) ($_GET['id'] ?? 0);
$annonce = annonceParId($pdo, $idAnnonce);
if (!$annonce) {
    rediriger('annonces.php', 'warning', "Cette annonce n'existe pas.");
}
if (!in_array($annonce['statut_annonce'], ANNONCE_STATUTS_MODIFIABLES, true)) {
    rediriger('annonces.php?onglet=toutes', 'warning', "Une annonce réservée ou vendue ne peut plus être modifiée.");
}
$photos = photosAnnonce($pdo, $idAnnonce);
$retourListe = 'annonces.php?onglet=' . match ($annonce['statut_annonce']) {
    'en_validation' => 'a_valider',
    'publiee' => 'en_ligne',
    default => 'refusees',
};

$v = [
    'etat' => (string) $annonce['etat'],
    'prix' => number_format((float) $annonce['prix'], 2, ',', ''),
    'description' => (string) $annonce['description'],
    'mode_remise' => (string) $annonce['mode_remise'],
    'ville_remise' => (string) $annonce['ville_remise'],
    'message' => '',
];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost("annonce.php?id=$idAnnonce");
    foreach (array_keys($v) as $champ) {
        $v[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    $prevenir = !empty($_POST['prevenir']);

    if (!isset(ETATS_LIVRE[$v['etat']])) { $erreurs[] = "Choisissez l'état du livre."; }
    $prix = lirePrix($v['prix']);
    if ($prix === null || $prix < ANNONCE_PRIX_MIN || $prix > ANNONCE_PRIX_MAX) {
        $erreurs[] = "Le prix doit être compris entre " . formaterPrix(ANNONCE_PRIX_MIN) . " et " . formaterPrix(ANNONCE_PRIX_MAX) . ".";
    }
    if (mb_strlen($v['description']) > ANNONCE_DESCRIPTION_MAX) { $erreurs[] = "La description dépasse " . ANNONCE_DESCRIPTION_MAX . " caractères."; }
    if (!isset(MODES_REMISE[$v['mode_remise']])) { $erreurs[] = "Choisissez le mode de remise."; }
    if ($v['mode_remise'] !== 'envoi' && $v['ville_remise'] === '') { $erreurs[] = "Indiquez la ville de remise en main propre."; }
    if (mb_strlen($v['ville_remise']) > 100) { $erreurs[] = "Le nom de la ville est trop long."; }
    $v['message'] = mb_substr($v['message'], 0, ANNONCE_MOTIF_MAX);

    if (!$erreurs) {
        $photosARetirer = array_values(array_intersect(
            array_map('intval', (array) ($_POST['retirer_photos'] ?? [])),
            array_map('intval', array_column($photos, 'id_photo'))
        ));
        $fichiersRetires = array_column(array_filter($photos, fn($p) => in_array((int) $p['id_photo'], $photosARetirer, true)), 'fichier');

        $pdo->beginTransaction();
        $maj = $pdo->prepare("
            UPDATE exemplaire SET etat = ?, prix = ?, description = ?, mode_remise = ?, ville_remise = ?
            WHERE id_exemplaire = ? AND id_proprietaire IS NOT NULL AND statut_annonce IN ('en_validation', 'publiee', 'refusee', 'retiree')");
        $maj->execute([$v['etat'], $prix, $v['description'] ?: null, $v['mode_remise'], $v['ville_remise'] ?: null, $idAnnonce]);
        if ($photosARetirer) {
            $marqueurs = implode(',', array_fill(0, count($photosARetirer), '?'));
            $pdo->prepare("DELETE FROM exemplaire_photo WHERE id_exemplaire = ? AND id_photo IN ($marqueurs)")->execute([$idAnnonce, ...$photosARetirer]);
        }
        $pdo->commit();
        supprimerFichiersPhotos($fichiersRetires);
        insertLog('ANNONCE', "Modification de l'annonce #$idAnnonce par l'équipe" . ($photosARetirer ? ' (' . count($photosARetirer) . ' photo(s) retirée(s))' : ''));

        $texte = "Annonce enregistrée.";
        if ($prevenir) {
            $complement = $v['message'] !== '' ? "\n\nMessage de l'équipe :\n" . $v['message'] : '';
            notifierParMail($annonce['vendeur_email'], "Votre annonce a été ajustée",
                "Bonjour {$annonce['vendeur_pseudo']},\n\nL'équipe BIBLIOccaz a ajusté votre annonce « {$annonce['titre']} » pour qu'elle respecte les règles du site." . $complement,
                SITE_URL . '/mes_annonces.php', "Voir mes annonces");
            $texte .= " Le vendeur a été prévenu par mail.";
        }
        rediriger($retourListe, 'success', $texte);
    }
}

$titrePage = "Modifier l'annonce";
$pageActive = 'annonces';
$sousTitrePage = $annonce['titre'] . ' · annonce de @' . $annonce['vendeur_pseudo'];
include __DIR__ . '/_haut.php';
?>

<a href="<?= e($retourListe) ?>" class="d-inline-flex align-items-center gap-1 mb-3 text-success fw-semibold text-decoration-none"><i class="bi bi-arrow-left" aria-hidden="true"></i> Retour aux annonces</a>

<?php if ($erreurs): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" class="carte p-4">
            <?= champCsrf() ?>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label for="annonce-etat" class="form-label fw-bold">État</label>
                    <select id="annonce-etat" name="etat" class="form-select">
                        <?php foreach (ETATS_LIVRE as $cle => $libelle): ?>
                            <option value="<?= e($cle) ?>" <?= $v['etat'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label for="annonce-prix" class="form-label fw-bold">Prix (€)</label>
                    <input id="annonce-prix" type="text" inputmode="decimal" name="prix" class="form-control" value="<?= e($v['prix']) ?>" required aria-describedby="aide-prix">
                    <div id="aide-prix" class="form-text">Entre <?= formaterPrix(ANNONCE_PRIX_MIN) ?> et <?= formaterPrix(ANNONCE_PRIX_MAX) ?>.</div>
                </div>
                <div class="col-12">
                    <label for="annonce-description" class="form-label fw-bold">Description du vendeur</label>
                    <textarea id="annonce-description" name="description" class="form-control" rows="5" maxlength="<?= ANNONCE_DESCRIPTION_MAX ?>"><?= e($v['description']) ?></textarea>
                </div>
                <div class="col-sm-6">
                    <label for="annonce-mode" class="form-label fw-bold">Remise</label>
                    <select id="annonce-mode" name="mode_remise" class="form-select">
                        <?php foreach (MODES_REMISE as $cle => [$libelle]): ?>
                            <option value="<?= e($cle) ?>" <?= $v['mode_remise'] === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label for="annonce-ville" class="form-label fw-bold">Ville de remise</label>
                    <input id="annonce-ville" type="text" name="ville_remise" class="form-control" maxlength="100" value="<?= e($v['ville_remise']) ?>">
                </div>
                <?php if ($photos): ?>
                    <div class="col-12">
                        <span class="form-label fw-bold d-block">Photos</span>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($photos as $index => $photo): ?>
                                <label class="text-center small">
                                    <img src="<?= e(urlPhotoAnnonce($photo['fichier'], null, '../')) ?>" alt="Photo <?= $index + 1 ?> de l'annonce" class="d-block rounded border mb-1" style="width: 92px; height: 116px; object-fit: cover;">
                                    <input type="checkbox" class="form-check-input" name="retirer_photos[]" value="<?= (int) $photo['id_photo'] ?>"> Retirer
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-12 border-top pt-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="prevenir" value="1" id="annonce-prevenir" checked>
                        <label class="form-check-label" for="annonce-prevenir">Prévenir le vendeur par mail</label>
                    </div>
                    <label for="annonce-message" class="form-label small fw-bold">Message au vendeur <span class="fw-normal text-muted">(facultatif)</span></label>
                    <textarea id="annonce-message" name="message" class="form-control form-control-sm" rows="2" maxlength="<?= ANNONCE_MOTIF_MAX ?>" placeholder="Ex. : photo retirée car elle montrait votre adresse."><?= e($v['message']) ?></textarea>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="btn btn-success fw-bold"><i class="bi bi-save" aria-hidden="true"></i> Enregistrer</button>
                <a href="<?= e($retourListe) ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="carte p-4">
            <h2 class="h6 fw-bold mb-3">L'annonce</h2>
            <div class="d-flex gap-3 mb-3">
                <img src="<?= imageLivre($annonce['couverture']) ?>" alt="" class="rounded shadow-sm" style="width: 64px; height: 90px; object-fit: cover;">
                <div class="small">
                    <div class="fw-bold"><?= e($annonce['titre']) ?></div>
                    <div class="text-muted"><?= e((string) $annonce['auteur']) ?></div>
                    <div class="mt-1"><?= e(ANNONCE_STATUTS[$annonce['statut_annonce']][0] ?? $annonce['statut_annonce']) ?></div>
                </div>
            </div>
            <p class="small mb-1"><strong>Vendeur :</strong> @<?= e($annonce['vendeur_pseudo']) ?><?= $annonce['vendeur_ville'] ? ' · ' . e($annonce['vendeur_ville']) : '' ?></p>
            <p class="small mb-1"><strong>Déposée le :</strong> <?= $annonce['date_creation'] ? date('d/m/Y à H:i', strtotime($annonce['date_creation'])) : '—' ?></p>
            <?php if ($annonce['motif_moderation']): ?>
                <p class="small mb-1"><strong>Motif :</strong> <?= e($annonce['motif_moderation']) ?></p>
            <?php endif; ?>
            <p class="small text-muted mt-3 mb-0">Le statut ne change pas : utilisez la liste des annonces pour valider, refuser ou retirer.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
