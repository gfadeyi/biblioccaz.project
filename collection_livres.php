<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/series_lib.php';
require_once __DIR__ . '/avis_lib.php';
$livresCollection = [];
$titreCollection = '';
$collectionRegroupee = false;
if (($modeCollection ?? '') === 'serie') {
    $idSerie = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $requete = $pdo->prepare('SELECT nom, nature FROM serie WHERE id_serie = ?');
    $requete->execute([$idSerie]);
    $serieCollection = $requete->fetch(PDO::FETCH_ASSOC);
    if ($serieCollection) { $titreCollection = $serieCollection['nom']; $collectionRegroupee = $serieCollection['nature'] === 'regroupement'; $livresCollection = tomesDeSerie($pdo, $idSerie); }
} elseif (($modeCollection ?? '') === 'auteur') {
    $auteurCollection = is_string($_GET['auteur'] ?? null) ? mb_substr(trim($_GET['auteur']), 0, 255) : '';
    if ($auteurCollection !== '') {
        $titreCollection = 'Les livres de ' . $auteurCollection;
        $requete = $pdo->prepare('SELECT * FROM livre WHERE auteur = ? AND is_valide = 1 ORDER BY titre, id_livre');
        $requete->execute([$auteurCollection]);
        $livresCollection = $requete->fetchAll();
    }
} elseif (($modeCollection ?? '') === 'editions') {
    $idEdition = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $requete = $pdo->prepare('SELECT id_livre, titre, auteur FROM livre WHERE id_livre = ? AND is_valide = 1');
    $requete->execute([$idEdition]);
    $referenceEdition = $requete->fetch();
    if ($referenceEdition) {
        $titreCollection = 'Autres éditions de « ' . $referenceEdition['titre'] . ' »';
        $livresCollection = autresEditionsDuLivre($pdo, $referenceEdition);
    }
}
if ($titreCollection === '') { http_response_code(404); $titreCollection = 'Sélection introuvable'; }
$offresParLivreCollection = [];
foreach (offresMagasinDesLivres($pdo, array_column($livresCollection, 'id_livre')) as $offreCollection) {
    if ($offreCollection['libre'] > 0) { $offresParLivreCollection[(int) $offreCollection['id_livre']][] = $offreCollection; }
}
foreach ($livresCollection as &$livreCollection) {
    if (!isset($livreCollection['nb_avis'])) { $noteCollection = noteDuLivre($pdo, (int) $livreCollection['id_livre']); $livreCollection['nb_avis'] = $noteCollection['nb']; $livreCollection['note_moyenne'] = $noteCollection['moyenne']; }
    $offresCollection = $offresParLivreCollection[(int) $livreCollection['id_livre']] ?? [];
    $livreCollection['stock'] = array_sum(array_column($offresCollection, 'libre'));
    $livreCollection['prix'] = $offresCollection[0]['prix'] ?? 0;
    $livreCollection['etat'] = $offresCollection[0]['etat'] ?? 'bon';
    $livreCollection['nb_offres'] = count($offresCollection);
    $livreCollection['epuise'] = !$offresCollection;
}
unset($livreCollection);
$titreDocument = $titreCollection;
include __DIR__ . '/header.php';
?>
<section class="container my-4">
    <a href="catalogue.php" class="small text-muted">Tout le catalogue</a>
    <h1 class="mt-3 mb-2"><?= htmlspecialchars($titreCollection) ?></h1>
    <p class="text-muted"><?= count($livresCollection) ?> livre(s)<?= ($modeCollection ?? '') === 'serie' && !$collectionRegroupee ? ', dans l’ordre des tomes.' : '.' ?></p>
    <?php if (!$livresCollection): ?><div class="carte-compte">Aucun livre dans cette sélection pour le moment.</div><?php endif; ?>
    <div class="row g-3">
        <?php foreach ($livresCollection as $l): ?>
            <div class="col-6 col-md-4 col-lg-3 d-flex flex-column">
                <?php if (isset($l['numero_tome'])): ?><p class="small text-muted mb-2">Tome <?= htmlspecialchars(numeroVolumeSerie($l)) ?></p><?php endif; ?>
                <div class="flex-grow-1"><?php include __DIR__ . '/partie_carte_livre.php'; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
