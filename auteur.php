<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'evenements_lib.php';
require_once 'auteurs_lib.php';

$slug = is_string($_GET['a'] ?? null) ? $_GET['a'] : '';
$fiche = fichePubliqueParSlug($pdo, $slug);

if (!$fiche) {
    http_response_code(404);
    $titreDocument = 'Page auteur introuvable';
    include 'header.php';
    ?>
    <div class="container my-5 page-auteur">
        <div class="carte-compte text-center p-5">
            <h1 class="titre-page-compte">Page auteur introuvable</h1>
            <p class="text-muted">Cette page n'existe pas ou n'est plus publiée.</p>
            <a href="auteurs.php" class="bouton bouton-principal bouton-petit">Voir nos auteurs</a>
        </div>
    </div>
    <?php
    include 'footer.php';
    exit();
}

$idAuteur = (int) $fiche['id_user'];
$livresSurLeSite = livresAuteurEnVente($pdo, $idAuteur);
$livresCatalogue = livresCatalogueOuvrages($pdo, $fiche['ouvrages']);
$evenements = evenementsAuteur($pdo, $idAuteur);
$photo = urlPhotoAuteur($fiche['photo']);
$liensPublics = array_filter(array_map(fn($champ) => $fiche[$champ] ?: null, array_combine(array_keys(AUTEUR_LIENS), array_keys(AUTEUR_LIENS))));
$iconesLiens = ['lien_site' => 'globe2', 'lien_reseau' => 'chat-heart', 'lien_editeur' => 'book'];
$titreDocument = $fiche['nom_plume'] . ' · Auteur';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-auteur">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="auteurs.php">Nos auteurs</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= htmlspecialchars($fiche['nom_plume']) ?></span>
    </nav>

    <header class="auteur-entete">
        <div class="auteur-entete__portrait">
            <?php if ($photo): ?>
                <img src="<?= htmlspecialchars($photo) ?>" alt="Portrait de <?= htmlspecialchars($fiche['nom_plume']) ?>">
            <?php else: ?>
                <span aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($fiche['nom_plume'], 0, 1))) ?></span>
            <?php endif; ?>
        </div>
        <div class="auteur-entete__texte">
            <p class="auteur-entete__surtitre">Auteur sur BIBLIOccaz</p>
            <h1><?= htmlspecialchars($fiche['nom_plume']) ?></h1>
            <?php if ($liensPublics): ?>
                <ul class="auteur-liens">
                    <?php foreach ($liensPublics as $champLien => $adresse): ?>
                        <li><a href="<?= htmlspecialchars($adresse) ?>" target="_blank" rel="noopener noreferrer nofollow"><i class="bi bi-<?= $iconesLiens[$champLien] ?>" aria-hidden="true"></i> <?= htmlspecialchars(AUTEUR_LIENS[$champLien]) ?><span class="visually-hidden"> (nouvel onglet)</span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </header>

    <div class="auteur-grille">
        <section class="carte-compte auteur-biographie" aria-labelledby="titre-biographie">
            <h2 class="carte-compte__titre" id="titre-biographie"><?= iconeDessin('plume') ?> Biographie</h2>
            <div class="auteur-biographie__texte"><?= nl2br(htmlspecialchars($fiche['biographie'])) ?></div>
        </section>

        <section class="carte-compte" aria-labelledby="titre-ouvrages">
            <h2 class="carte-compte__titre" id="titre-ouvrages"><?= iconeDessin('pile') ?> Ouvrages publiés</h2>
            <ul class="auteur-ouvrages">
                <?php foreach ($fiche['ouvrages'] as $ouvrage): ?>
                    <?php $idLivreCatalogue = $ouvrage['isbn'] ? ($livresCatalogue[strtoupper($ouvrage['isbn'])] ?? null) : null; ?>
                    <li>
                        <?php if ($idLivreCatalogue): ?>
                            <a href="detail_livre.php?id=<?= $idLivreCatalogue ?>"><strong><?= htmlspecialchars($ouvrage['titre']) ?></strong></a>
                        <?php else: ?>
                            <strong><?= htmlspecialchars($ouvrage['titre']) ?></strong>
                        <?php endif; ?>
                        <?php $details = array_filter([$ouvrage['editeur'], $ouvrage['annee'] ? (string) $ouvrage['annee'] : null]); ?>
                        <?php if ($details || $ouvrage['isbn']): ?>
                            <small><?= htmlspecialchars(implode(' · ', $details)) ?><?php if ($ouvrage['isbn']): ?><?= $details ? ' · ' : '' ?>ISBN <?= htmlspecialchars($ouvrage['isbn']) ?><?php endif; ?></small>
                        <?php endif; ?>
                        <?php if ($idLivreCatalogue): ?><span class="etiquette-profil">Au catalogue</span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <section class="mt-4" aria-labelledby="titre-livres-site">
        <h2 class="titre-section-auteur" id="titre-livres-site">Ses livres sur BIBLIOccaz</h2>
        <?php if ($livresSurLeSite): ?>
            <div class="grille-suggestions">
                <?php foreach ($livresSurLeSite as $l): include 'partie_carte_livre.php'; endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Aucun livre de cet auteur n'est disponible en ce moment. <a href="catalogue.php?q=<?= rawurlencode($fiche['nom_plume']) ?>">Chercher dans le catalogue</a></p>
        <?php endif; ?>
    </section>

    <section class="mt-4" aria-labelledby="titre-evenements-auteur">
        <h2 class="titre-section-auteur" id="titre-evenements-auteur">Ses rencontres</h2>
        <?php if (!$evenements['a_venir'] && !$evenements['passes']): ?>
            <p class="text-muted">Aucune rencontre prévue pour le moment. <a href="evenements.php">Voir tout le programme</a></p>
        <?php endif; ?>
        <?php foreach (['a_venir' => 'À venir', 'passes' => 'Passées'] as $periode => $libellePeriode): ?>
            <?php if ($evenements[$periode]): ?>
                <h3 class="auteur-periode"><?= $libellePeriode ?></h3>
                <ul class="auteur-evenements<?= $periode === 'passes' ? ' auteur-evenements--passes' : '' ?>">
                    <?php foreach ($evenements[$periode] as $evenement): ?>
                        <?php $debut = strtotime($evenement['date_debut']); ?>
                        <li>
                            <a href="evenement.php?id=<?= (int) $evenement['id_evenement'] ?>">
                                <span class="auteur-evenements__date">
                                    <strong><?= date('j', $debut) ?></strong>
                                    <small><?= EVT_MOIS_COURTS[(int) date('n', $debut) - 1] ?> <?= date('Y', $debut) ?></small>
                                </span>
                                <span class="auteur-evenements__texte">
                                    <strong><?= htmlspecialchars($evenement['titre']) ?></strong>
                                    <small><?= htmlspecialchars(EVT_TYPES[$evenement['type']][0] ?? ucfirst($evenement['type'])) ?> · <?= date('G\hi', $debut) ?> · <?= htmlspecialchars($evenement['lieu']) ?></small>
                                </span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
