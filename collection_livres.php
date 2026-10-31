<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/series_lib.php';
require_once __DIR__ . '/avis_lib.php';
$livresCollection = [];
$titreCollection = '';
if (($modeCollection ?? '') === 'serie') {
    $idSerie = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $requete = $pdo->prepare('SELECT nom FROM serie WHERE id_serie = ?');
    $requete->execute([$idSerie]);
    $nomSerie = $requete->fetchColumn();
    if ($nomSerie !== false) { $titreCollection = $nomSerie; $livresCollection = tomesDeSerie($pdo, $idSerie); }
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
    $requete = $pdo->prepare('SELECT titre, auteur FROM livre WHERE id_livre = ? AND is_valide = 1');
    $requete->execute([$idEdition]);
    $referenceEdition = $requete->fetch();
    if ($referenceEdition) {
        $titreCollection = 'Autres éditions de « ' . $referenceEdition['titre'] . ' »';
        $requete = $pdo->prepare('SELECT * FROM livre WHERE titre = ? AND auteur = ? AND id_livre <> ? AND is_valide = 1 ORDER BY annee_parution DESC, id_livre');
        $requete->execute([$referenceEdition['titre'], (string) $referenceEdition['auteur'], $idEdition]);
        $livresCollection = $requete->fetchAll();
    }
}
if ($titreCollection === '') { http_response_code(404); $titreCollection = 'Sélection introuvable'; }
foreach ($livresCollection as &$livreCollection) {
    if (!isset($livreCollection['nb_avis'])) { $noteCollection = noteDuLivre($pdo, (int) $livreCollection['id_livre']); $livreCollection['nb_avis'] = $noteCollection['nb']; $livreCollection['note_moyenne'] = $noteCollection['moyenne']; }
    $offresCollection = array_values(array_filter(offresMagasinDuLivre($pdo, (int) $livreCollection['id_livre']), fn($offre) => $offre['libre'] > 0));
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
<main class="container my-4">
    <a href="catalogue.php" class="small text-muted">Tout le catalogue</a>
    <h1 class="mt-3 mb-2"><?= htmlspecialchars($titreCollection) ?></h1>
    <p class="text-muted"><?= count($livresCollection) ?> livre(s)<?= ($modeCollection ?? '') === 'serie' ? ', dans l’ordre des tomes.' : '.' ?></p>
    <?php if (!$livresCollection): ?><div class="carte-compte">Aucun livre dans cette sélection pour le moment.</div><?php endif; ?>
    <div class="row g-3">
        <?php foreach ($livresCollection as $l): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <?php if (isset($l['numero_tome'])): ?><p class="small text-muted mb-2">Tome <?= htmlspecialchars(numeroTomeLisible($l['numero_tome'])) ?></p><?php endif; ?>
                <?php include __DIR__ . '/partie_carte_livre.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
