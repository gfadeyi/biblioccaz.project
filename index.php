<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'evenements_lib.php';
require_once 'livres_lib.php';
require_once 'icones_lib.php';
require_once 'panier_calcul.php';
require_once 'compte_lib.php';
require_once 'categories_lib.php';

$selectionCartes = sqlCartesLivres();

$coupsDeCoeur = $pdo->query($selectionCartes . "
    HAVING nb_favoris > 0 OR note_moyenne >= 4
    ORDER BY nb_favoris * 2 + COALESCE(note_moyenne, 0) * nb_avis DESC, l.id_livre DESC
    LIMIT 24")->fetchAll();
$idsCoupsDeCoeur = array_column($coupsDeCoeur, 'id_livre');
$selectionLecteurs = $coupsDeCoeur;
$titreSelectionLecteurs = 'Coups de cœur des lecteurs';
$sousTitreSelectionLecteurs = 'Les plus ajoutés en favoris et les mieux notés.';
if (count($coupsDeCoeur) < 4) {
    $selectionLecteurs = $pdo->query($selectionCartes . "
        ORDER BY (SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_livre = l.id_livre) DESC,
                 nb_favoris DESC, COALESCE(note_moyenne, 0) DESC, l.id_livre DESC
        LIMIT 24")->fetchAll();
    $titreSelectionLecteurs = 'Les plus demandés';
    $sousTitreSelectionLecteurs = 'Les livres que nos lecteurs s\'arrachent en ce moment.';
}
foreach ($selectionLecteurs as &$livreSelection) {
    $livreSelection['coup_de_coeur'] = in_array($livreSelection['id_livre'], $idsCoupsDeCoeur, true);
}
unset($livreSelection);
$selectionLecteurs = privilegierVraiesCouvertures($selectionLecteurs, 12);

$nouveautes = privilegierVraiesCouvertures($pdo->query($selectionCartes . " ORDER BY l.id_livre DESC LIMIT 24")->fetchAll(), 12);
$petitsPrix = privilegierVraiesCouvertures($pdo->query($selectionCartes . " HAVING prix < 10 ORDER BY prix ASC, l.id_livre DESC LIMIT 24")->fetchAll(), 12);

$couverturesBandeau = array_slice(array_values(array_filter($nouveautes, fn($livre) => aUneVraieCouverture($livre['couverture']))), 0, 3);

$univers = [['classe' => 'univers-bons-plans', 'icone' => 'etiquette', 'nom' => 'Bons plans', 'detail' => 'Moins de 10 €, dernière chance, nouveautés', 'lien' => 'petits_prix.php']];
foreach (arbreCategories($pdo) as $universCategorie) {
    $univers[] = [
        'classe' => 'univers-' . ($universCategorie['couleur'] ?: 'litterature'),
        'icone' => $universCategorie['icone'] ?: 'livre-ouvert',
        'nom' => $universCategorie['nom'],
        'detail' => $universCategorie['description'] ?? '',
        'lien' => lienCategorie($universCategorie['slug']),
    ];
}

$totauxPanierAccueil = calculerTotauxPanier($_SESSION['panier'] ?? []);

$chiffresCommunaute = ['livres' => 0, 'dons' => 0, 'membres' => 0];
try {
    $chiffresCommunaute['livres'] = (int) $pdo->query("
        SELECT COALESCE(SUM(e.quantite), 0)
        FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre
        WHERE l.is_valide = 1 AND " . conditionOffreMagasin())->fetchColumn();
    $chiffresCommunaute['membres'] = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut NOT IN ('refuse_temporaire', 'refuse_definitif')")->fetchColumn();
    $chiffresCommunaute['dons'] = (int) $pdo->query("SELECT COUNT(*) FROM don WHERE statut = 'recupere'")->fetchColumn();
} catch (PDOException $e) {
}

$derniersDons = [];
$prochainsEvenements = [];
$derniersAvis = [];
try {
    $derniersDons = $pdo->query("
        SELECT id_don, titre, auteur, etat, photo, lieu_retrait, date_limite
        FROM don
        WHERE statut = 'disponible' AND date_limite >= CURDATE()
        ORDER BY date_creation DESC
        LIMIT 3")->fetchAll();
    $prochainsEvenements = $pdo->query("
        SELECT e.*,
               (SELECT COUNT(*) FROM evenement_inscription i WHERE i.id_evenement = e.id_evenement AND i.statut = 'inscrit') AS nb_inscrits
        FROM evenement e
        WHERE e.statut = 'publie' AND e.date_fin >= NOW()
        ORDER BY e.date_debut ASC
        LIMIT 3")->fetchAll();
    $derniersAvis = $pdo->query("
        SELECT a.note, a.commentaire, u.pseudo, u.photo, l.id_livre, l.titre AS titre_livre, l.couverture
        FROM avis a
        INNER JOIN user u ON u.id = a.id_user
        INNER JOIN livre l ON l.id_livre = a.id_livre
        WHERE a.statut = 'visible'
        ORDER BY a.date_creation DESC
        LIMIT 6")->fetchAll();
} catch (PDOException $e) {
}

$moisCourts = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$scriptsPage = ['accueil.js'];

include 'header.php';
?>

<section class="bandeau-accueil">
    <div class="container-xxl">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="surtitre"><?= iconeDessin('seconde-vie') ?> Livres d'occasion &amp; communauté de lecteurs</span>
                <h1>Chaque livre mérite une <em>seconde lecture</em>.</h1>
                <p class="chapo fs-5 mb-4">Romans, mangas, BD, manuels scolaires : des livres vérifiés à petits prix, et une communauté qui donne, échange et se retrouve.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="catalogue.php" class="bouton bouton-action"><?= iconeDessin('cabas') ?> Acheter des livres</a>
                    <a href="vendre.php" class="bouton bouton-contour"><?= iconeDessin('carton') ?> Donner ou revendre mes livres</a>
                </div>
                <div class="atouts-courts">
                    <span><?= iconeDessin('camion') ?> Livraison offerte dès 20 €</span>
                    <span><?= iconeDessin('etiquette') ?> -10 % dès 50 €</span>
                    <span><?= iconeDessin('medaille') ?> 1 point fidélité par euro</span>
                </div>
            </div>
            <?php if (count($couverturesBandeau) === 3): ?>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="pile-livres" aria-hidden="true">
                        <?php foreach ($couverturesBandeau as $livreBandeau): ?>
                            <img src="<?= htmlspecialchars(urlImage($livreBandeau['couverture'])) ?>" alt="">
                        <?php endforeach; ?>
                        <div class="pastille-verifie">Vérifié<br>à la main<br>✓</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section-accueil container-xxl">
    <div class="entete-section">
        <div>
            <h2>Explorez nos univers</h2>
            <p>Comme dans une librairie : chaque rayon a sa couleur.</p>
        </div>
    </div>
    <div class="grille-univers">
        <?php foreach ($univers as $u): ?>
            <a href="<?= htmlspecialchars($u['lien']) ?>" class="tuile-univers <?= $u['classe'] ?>">
                <span class="icone"><?= iconeDessin($u['icone']) ?></span>
                <strong><?= htmlspecialchars($u['nom']) ?></strong>
                <small><?= htmlspecialchars($u['detail']) ?></small>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($selectionLecteurs): ?>
    <section class="section-accueil container-xxl">
        <div class="entete-section">
            <div>
                <h2><?= $titreSelectionLecteurs ?></h2>
                <p><?= $sousTitreSelectionLecteurs ?></p>
            </div>
            <a href="<?= count($coupsDeCoeur) < 4 ? 'meilleures_ventes.php' : 'catalogue.php?tri=note' ?>" class="lien-voir-tout">Voir tout <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="carrousel">
            <?php foreach ($selectionLecteurs as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($nouveautes): ?>
    <section class="section-accueil container-xxl">
        <div class="entete-section">
            <div>
                <h2>Nouveau chez nous</h2>
                <p>Les derniers livres arrivés sur les étagères.</p>
            </div>
            <a href="nouveauté.php" class="lien-voir-tout">Voir tout <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="carrousel">
            <?php foreach ($nouveautes as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="container-xxl mt-4">
    <div class="bandeau-remise<?= $totauxPanierAccueil['nb_articles'] ? '' : ' panier-vide-accueil' ?>">
        <div class="bandeau-remise__entete">
            <p class="titre"><?= iconeDessin('cabas') ?> Le panier malin</p>
            <?php if ($totauxPanierAccueil['nb_articles']): ?>
                <a href="panier.php" class="lien-voir-tout">Mon panier · <?= $totauxPanierAccueil['nb_articles'] ?> livre<?= $totauxPanierAccueil['nb_articles'] > 1 ? 's' : '' ?> · <?= formaterPrix($totauxPanierAccueil['sous_total']) ?> <i class="bi bi-chevron-right"></i></a>
            <?php else: ?>
                <a href="petits_prix.php" class="lien-voir-tout">Voir les bons plans <i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php $sousTotal = $totauxPanierAccueil['sous_total']; include 'partie_jauge_panier.php'; ?>
    </div>
</section>

<?php if ($petitsPrix): ?>
    <section class="section-accueil container-xxl">
        <div class="entete-section">
            <div>
                <h2>Petits prix, grandes lectures</h2>
                <p>Moins de 10 € : de quoi remplir la bibliothèque.</p>
            </div>
            <a href="petits_prix.php" class="lien-voir-tout">Voir tout <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="carrousel">
            <?php foreach ($petitsPrix as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="section-accueil container-xxl">
    <div class="seconde-vie">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2>De votre étagère à un nouveau lecteur</h2>
                <p class="mb-0 text-body-secondary">Vos livres dorment dans un carton ? Donnez-les à la communauté : vous gagnez <strong><?= DON_POINTS ?> points fidélité</strong> à chaque don récupéré.</p>
                <ol class="etapes">
                    <li class="etape"><?= iconeDessin('plume') ?><strong>Je propose</strong><span>Titre, état, photo : deux minutes suffisent.</span></li>
                    <li class="etape"><?= iconeDessin('lecteur') ?><strong>Un lecteur réserve</strong><span>Il le récupère grâce au QR code du don.</span></li>
                    <li class="etape"><?= iconeDessin('medaille') ?><strong>Je gagne des points</strong><span>+<?= DON_POINTS ?> points à utiliser sur mes achats.</span></li>
                </ol>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="don_creer.php" class="bouton bouton-action"><?= iconeDessin('carton') ?> Donner mes livres</a>
                    <ul class="chiffres-communaute">
                        <?php if ($chiffresCommunaute['livres'] > 0): ?>
                            <li><strong><?= $chiffresCommunaute['livres'] ?></strong> livre<?= $chiffresCommunaute['livres'] > 1 ? 's' : '' ?> sur nos étagères</li>
                        <?php endif; ?>
                        <?php if ($chiffresCommunaute['dons'] > 0): ?>
                            <li><strong><?= $chiffresCommunaute['dons'] ?></strong> livre<?= $chiffresCommunaute['dons'] > 1 ? 's' : '' ?> déjà donné<?= $chiffresCommunaute['dons'] > 1 ? 's' : '' ?></li>
                        <?php endif; ?>
                        <?php if ($chiffresCommunaute['membres'] > 0): ?>
                            <li><strong><?= $chiffresCommunaute['membres'] ?></strong> membre<?= $chiffresCommunaute['membres'] > 1 ? 's' : '' ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block">
                <?php include 'partie_illustration_don.php'; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-accueil container-xxl">
    <div class="entete-section">
        <div>
            <h2>La vie de la communauté</h2>
            <p>Dons, rencontres et avis : BIBLIOccaz, c'est aussi des lecteurs.</p>
        </div>
    </div>
    <div class="row g-3 grille-communaute">
        <div class="col-md-6 col-lg-4">
            <div class="bloc-communaute">
                <h3><?= iconeDessin('carton') ?> Derniers dons</h3>
                <?php foreach ($derniersDons as $don): ?>
                    <a href="don_detail.php?id=<?= (int) $don['id_don'] ?>" class="ligne-communaute">
                        <span class="couverture-mini"><img src="<?= htmlspecialchars(photoDon($don['photo'])) ?>" alt="" loading="lazy"></span>
                        <span class="ligne-communaute__texte">
                            <strong><?= htmlspecialchars($don['titre']) ?></strong>
                            <?php if (!empty($don['auteur'])): ?><small><?= htmlspecialchars($don['auteur']) ?></small><?php endif; ?>
                            <span class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                <span class="badge-bibli badge-etat"><?= htmlspecialchars(DON_ETATS[$don['etat']] ?? $don['etat']) ?></span>
                                <small><?= htmlspecialchars($don['lieu_retrait']) ?> · avant le <?= date('d/m', strtotime($don['date_limite'])) ?></small>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$derniersDons): ?>
                    <div class="vide-dons">
                        <span class="vide-dons__illustration" aria-hidden="true"><?= iconeDessin('carton') ?></span>
                        <strong>Le carton est vide… pour l'instant&nbsp;!</strong>
                        <p>Vos livres méritent un second lecteur. Proposez-en un : +<?= DON_POINTS ?> points dès qu'il est récupéré.</p>
                        <a href="don_creer.php" class="bouton bouton-action"><?= iconeDessin('carton') ?> Proposer un don</a>
                    </div>
                <?php endif; ?>
                <a href="dons.php" class="lien-voir-tout mt-3">Tous les dons <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="bloc-communaute">
                <h3><?= iconeDessin('ticket') ?> Prochains événements</h3>
                <?php foreach ($prochainsEvenements as $evt): ?>
                    <?php
                    $debutEvt = strtotime($evt['date_debut']);
                    $placesEvt = placesRestantes($evt);
                    $remplissageEvt = $evt['places_max'] > 0 ? min(1, $evt['nb_inscrits'] / $evt['places_max']) : 1;
                    ?>
                    <a href="evenement.php?id=<?= (int) $evt['id_evenement'] ?>" class="ligne-communaute ligne-evenement">
                        <span class="page-calendrier" aria-hidden="true">
                            <small><?= $moisCourts[(int) date('n', $debutEvt) - 1] ?></small>
                            <strong><?= date('d', $debutEvt) ?></strong>
                        </span>
                        <span class="ligne-communaute__texte">
                            <strong><?= htmlspecialchars($evt['titre']) ?></strong>
                            <small><?= ucfirst(EVT_JOURS[(int) date('w', $debutEvt)]) ?> · <?= date('G\hi', $debutEvt) ?> · <?= htmlspecialchars($evt['lieu']) ?></small>
                            <span class="jauge-places<?= $placesEvt === 0 ? ' complet' : '' ?>" style="--remplissage: <?= round($remplissageEvt, 3) ?>;"><span></span></span>
                            <small class="places-restantes"><?= $placesEvt > 0 ? $placesEvt . ' place' . ($placesEvt > 1 ? 's' : '') . ' libre' . ($placesEvt > 1 ? 's' : '') . ' sur ' . (int) $evt['places_max'] : "Complet · liste d'attente" ?></small>
                            <span class="bouton-inscription"><?= $placesEvt > 0 ? "Je m'inscris" : "Rejoindre la liste d'attente" ?> <i class="bi bi-arrow-right"></i></span>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$prochainsEvenements): ?>
                    <p class="vide-communaute">Pas d'événement prévu pour l'instant.</p>
                <?php endif; ?>
                <a href="evenements.php" class="lien-voir-tout mt-3">Le calendrier <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="col-md-12 col-lg-4">
            <div class="bloc-communaute">
                <h3><?= iconeDessin('plume') ?> Derniers avis</h3>
                <?php if ($derniersAvis): ?>
                    <div class="carrousel-avis" data-carrousel-avis>
                        <?php foreach ($derniersAvis as $avis): ?>
                            <a href="detail_livre.php?id=<?= (int) $avis['id_livre'] ?>#avis" class="avis-lecteur">
                                <span class="couverture-mini"><img src="<?= htmlspecialchars(urlImage($avis['couverture'])) ?>" alt="" loading="lazy"></span>
                                <span class="avis-lecteur__texte">
                                    <span class="avis-lecteur__auteur">
                                        <?= avatarMembre($avis['pseudo'], $avis['photo'], 'petit') ?>
                                        <strong><?= htmlspecialchars(pseudoAffiche($avis['pseudo'])) ?></strong>
                                        <?= etoiles((float) $avis['note']) ?>
                                    </span>
                                    <q><?= htmlspecialchars(mb_strimwidth($avis['commentaire'], 0, 110, '…')) ?></q>
                                    <small>sur <strong><?= htmlspecialchars($avis['titre_livre']) ?></strong></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($derniersAvis) > 1): ?>
                        <div class="points-avis" data-points-avis>
                            <?php foreach ($derniersAvis as $indexAvis => $avis): ?>
                                <button type="button" class="<?= $indexAvis === 0 ? 'actif' : '' ?>" aria-label="Voir l'avis <?= $indexAvis + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="vide-communaute">Aucun avis pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="reassurance">
    <div class="container-xxl">
        <div class="row">
            <div class="col-6 col-lg-3"><div class="atout"><?= iconeDessin('tampon') ?><div><strong>Livres vérifiés</strong><span>État contrôlé à la main</span></div></div></div>
            <div class="col-6 col-lg-3"><div class="atout"><?= iconeDessin('camion') ?><div><strong>Livraison offerte</strong><span>Dès 20 € d'achat</span></div></div></div>
            <div class="col-6 col-lg-3"><div class="atout"><?= iconeDessin('medaille') ?><div><strong>Points fidélité</strong><span>1 point par euro</span></div></div></div>
            <div class="col-6 col-lg-3"><div class="atout"><?= iconeDessin('seconde-vie') ?><div><strong>Seconde vie</strong><span>Moins de livres au pilon</span></div></div></div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
