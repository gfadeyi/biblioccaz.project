<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'avis_lib.php';
require_once 'panier_calcul.php';
require_once 'livres_lib.php';

$panier = $_SESSION['panier'] ?? [];
$idUserPanier = (int) ($_SESSION['user_id'] ?? 0);
$niveauPanier = null;
$meilleurBon = null;
if ($idUserPanier) {
    require_once 'points_lib.php';
    $niveauPanier = niveauMembre($pdo, $idUserPanier);
}
$totaux = calculerTotauxPanier($panier, 0, $niveauPanier['livraison_des'] ?? 20, pointsRegle($pdo, 'achat'));
if ($idUserPanier && $panier) {
    foreach (optionsReduction($pdo, $idUserPanier, $totaux, soldePoints($pdo, $idUserPanier)) as $option) {
        if ($option['possible'] && (!$meilleurBon || $option['montant'] > $meilleurBon['montant'])) {
            $meilleurBon = $option;
        }
    }
}
$pointsPanier = $totaux['points'] + ($niveauPanier ? bonusNiveau($totaux['points'], $niveauPanier) : 0);
$sousTotal = $totaux['sous_total'];

require_once 'recommandations_lib.php';
$suggestions = suggestionsPanier($pdo, $panier, $idUserPanier, (float) progressionPanier($sousTotal)['reste']);
$jetonPanier = jetonCsrf();

$titreDocument = 'Mon panier';
$scriptsPage = ['panier_suggestions.js'];
include 'header.php';
?>

<p class="visually-hidden" id="annonce-panier" role="status" aria-live="polite"></p>
<div class="container-xxl page-panier" id="page-panier">
    <div class="entete-section">
        <div>
            <h1 class="h2 mb-0">Mon panier</h1>
            <?php if ($panier): ?>
                <p><?= $totaux['nb_articles'] ?> livre<?= $totaux['nb_articles'] > 1 ? 's' : '' ?> prêt<?= $totaux['nb_articles'] > 1 ? 's' : '' ?> à rejoindre votre bibliothèque</p>
            <?php endif; ?>
        </div>
        <?php if ($panier): ?>
            <a href="catalogue.php" class="lien-voir-tout"><i class="bi bi-arrow-left"></i> Continuer mes achats</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (!$panier): ?>
        <div class="panier-vide">
            <span class="panier-vide__icone" aria-hidden="true"><i class="bi bi-bag-heart"></i></span>
            <h2>Votre panier attend sa première lecture</h2>
            <p>Romans, mangas, manuels… des milliers de pages vous attendent à petit prix.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="catalogue.php" class="bouton bouton-action"><i class="bi bi-book"></i> Parcourir le catalogue</a>
                <a href="petits_prix.php" class="bouton bouton-contour"><i class="bi bi-tag"></i> Voir les petits prix</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <section class="progression-panier" aria-label="Vos avantages">
                    <?php include 'partie_jauge_panier.php'; ?>
                </section>

                <div class="liste-panier">
                    <?php foreach ($panier as $cle => $item): ?>
                        <article class="ligne-panier">
                            <a href="detail_livre.php?id=<?= (int) $item['id_livre'] ?>" class="ligne-panier__couverture">
                                <img src="<?= htmlspecialchars(urlImage($item['image'])) ?>" alt="Couverture de <?= htmlspecialchars($item['titre']) ?>">
                            </a>
                            <div class="ligne-panier__infos">
                                <h2><a href="detail_livre.php?id=<?= (int) $item['id_livre'] ?>"><?= htmlspecialchars($item['titre']) ?></a></h2>
                                <span class="badge-bibli badge-etat"><?= htmlspecialchars(libelleEtat($item['etat'])) ?></span>
                                <p><?= formaterPrix((float) $item['prix']) ?> l'unité</p>
                            </div>
                            <form method="POST" action="action_modifier_panier.php" class="ligne-panier__actions">
                                <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                                <input type="hidden" name="key" value="<?= htmlspecialchars($cle) ?>">
                                <div class="selecteur-quantite">
                                    <button type="submit" name="action" value="moins" aria-label="Retirer un exemplaire de « <?= htmlspecialchars($item['titre']) ?> »"><i class="bi bi-dash"></i></button>
                                    <span aria-label="Quantité"><?= (int) $item['quantite'] ?></span>
                                    <button type="submit" name="action" value="plus" aria-label="Ajouter un exemplaire de « <?= htmlspecialchars($item['titre']) ?> »"><i class="bi bi-plus"></i></button>
                                </div>
                                <strong class="prix"><?= formaterPrix($item['prix'] * $item['quantite']) ?></strong>
                                <button type="submit" name="action" value="supprimer" class="bouton-retirer" aria-label="Retirer « <?= htmlspecialchars($item['titre']) ?> » du panier" title="Retirer du panier">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <aside class="recap-panier">
                    <h2>Récapitulatif</h2>
                    <dl>
                        <div><dt>Sous-total (<?= $totaux['nb_articles'] ?> article<?= $totaux['nb_articles'] > 1 ? 's' : '' ?>)</dt><dd><?= formaterPrix($sousTotal) ?></dd></div>
                        <?php if ($totaux['remise'] > 0): ?>
                            <div class="ligne-avantage"><dt>Remise <?= $sousTotal >= 50 ? '-10 %' : '-5 %' ?></dt><dd>-<?= formaterPrix($totaux['remise']) ?></dd></div>
                        <?php endif; ?>
                        <div class="<?= $totaux['frais_livraison'] == 0 ? 'ligne-avantage' : '' ?>">
                            <dt>Livraison</dt>
                            <dd><?= $totaux['frais_livraison'] == 0 ? 'Offerte' : formaterPrix($totaux['frais_livraison']) ?></dd>
                        </div>
                        <div class="recap-panier__total"><dt>Total</dt><dd><?= formaterPrix($totaux['total']) ?></dd></div>
                    </dl>
                    <?php if ($pointsPanier > 0): ?>
                        <p class="recap-panier__points"><i class="bi bi-coin" aria-hidden="true"></i>
                            <?= $idUserPanier ? 'Environ ' . $pointsPanier . ' points à gagner' : '<a href="login.php">Connectez-vous</a> pour gagner environ ' . $pointsPanier . ' points' ?>
                            <?php if ($niveauPanier && $niveauPanier['bonus'] > 0): ?><small class="d-block">dont +<?= $niveauPanier['bonus'] ?> % de bonus <?= htmlspecialchars($niveauPanier['nom']) ?></small><?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($meilleurBon): ?>
                        <p class="rappel-points"><i class="bi bi-ticket-perforated" aria-hidden="true"></i>
                            <span>Un bon jusqu'à <strong>-<?= formaterPrix($meilleurBon['montant']) ?></strong> est disponible : vous pourrez l'utiliser à l'étape suivante.</span>
                        </p>
                    <?php endif; ?>
                    <a href="achat.php" class="bouton bouton-action w-100"><i class="bi bi-lock"></i> Valider mon panier</a>
                    <ul class="recap-panier__garanties">
                        <li><i class="bi bi-shield-check"></i> Paiement 100 % sécurisé</li>
                        <li><i class="bi bi-patch-check"></i> Livres vérifiés à la main</li>
                        <li><i class="bi bi-arrow-repeat"></i> Une seconde vie pour chaque livre</li>
                    </ul>
                </aside>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($suggestions): ?>
        <section class="section-accueil" aria-labelledby="titre-suggestions-panier">
            <div class="entete-section">
                <div>
                    <h2 id="titre-suggestions-panier" tabindex="-1"><?= $panier ? 'Pour compléter votre pile' : 'Quelques idées pour commencer' ?></h2>
                    <p><?= $panier ? 'Dans le même esprit que vos choix. Survolez ou touchez un livre pour l’ajouter sans quitter la page.' : 'Choisis pour vous. Survolez ou touchez un livre pour l’ajouter au panier.' ?></p>
                </div>
                <a href="catalogue.php" class="lien-voir-tout">Tout le catalogue <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="carrousel">
                <?php foreach ($suggestions as $l): ?>
                    <div class="suggestion-panier">
                        <?php include 'partie_carte_livre.php'; ?>
                        <button type="button" class="bouton-exemplaires" aria-expanded="false" aria-controls="exemplaires-<?= (int) $l['id_livre'] ?>">
                            <i class="bi bi-bag-plus" aria-hidden="true"></i> Choisir un exemplaire
                        </button>
                        <div class="panneau-exemplaires" id="exemplaires-<?= (int) $l['id_livre'] ?>" role="group" aria-label="Exemplaires disponibles de « <?= htmlspecialchars($l['titre']) ?> »">
                            <p class="panneau-exemplaires__titre"><?= htmlspecialchars($l['titre']) ?></p>
                            <ul>
                                <?php foreach ($l['exemplaires'] as $exemplaire): ?>
                                    <li>
                                        <span class="panneau-exemplaires__etat"><?= htmlspecialchars(libelleEtat($exemplaire['etat'])) ?>
                                            <?php if (stockEstBas((int) $exemplaire['quantite'])): ?><?= badgeStockBas('badge-mini') ?><?php endif; ?>
                                        </span>
                                        <strong><?= formaterPrix((float) $exemplaire['prix']) ?></strong>
                                        <form method="POST" action="action_ajouter_panier.php" class="formulaire-ajout-suggestion">
                                            <input type="hidden" name="csrf" value="<?= $jetonPanier ?>">
                                            <input type="hidden" name="id_exemplaire" value="<?= (int) $exemplaire['id_exemplaire'] ?>">
                                            <input type="hidden" name="action" value="acheter">
                                            <button type="submit" class="bouton-ajout-suggestion" data-titre="<?= htmlspecialchars($l['titre']) ?>">
                                                Ajouter au panier<span class="visually-hidden"> : <?= htmlspecialchars($l['titre']) ?>, <?= htmlspecialchars(libelleEtat($exemplaire['etat'])) ?>, <?= formaterPrix((float) $exemplaire['prix']) ?></span>
                                            </button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
