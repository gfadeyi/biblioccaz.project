<main class="fiche-livre" id="fiche-livre">
    <nav class="fiche-livre__chemin" aria-label="Fil d’Ariane">
        <a href="index.php">Accueil</a><span aria-hidden="true">›</span><a href="catalogue.php">Livres</a>
        <?php if ($catLivre): ?><span aria-hidden="true">›</span><a href="catalogue.php?categorie=<?= rawurlencode($catLivre['slug']) ?>"><?= htmlspecialchars($catLivre['nom']) ?></a><?php endif; ?>
    </nav>
    <div class="fiche-livre__grille">
            <div class="fiche-livre__visuel">
                <div class="fiche-livre__badges">
                    <?php if (stockEstBas($stockTotal)): ?><?= badgeStockBas() ?><?php endif; ?>
                    <?php if (!empty($livre['date_ajout']) && strtotime($livre['date_ajout']) >= strtotime('-30 days')): ?><span class="badge-bibli badge-nouveau">Nouveauté</span><?php endif; ?>
                    <?php if ($serieLivre): ?><a href="serie.php?id=<?= (int) $serieLivre['id_serie'] ?>" class="badge-bibli badge-etat">Série · tome <?= htmlspecialchars(numeroTomeLisible($serieLivre['numero_tome'])) ?></a><?php endif; ?>
                </div>
                <img class="fiche-livre__couverture" src="<?= htmlspecialchars(urlImage($livre['couverture'])) ?>" alt="Couverture de <?= htmlspecialchars($livre['titre']) ?>" fetchpriority="high">
                <?php if ($publicsLivre): ?><p class="fiche-livre__audience"><?= htmlspecialchars(implode(' · ', array_column($publicsLivre, 'nom'))) ?></p><?php endif; ?>
            </div>

            <section class="fiche-livre__achat" data-achat-livre data-prix-neuf="<?= $prixNeufLivre ?? 0 ?>">
                <?php if ($flashCompte): ?><div class="alert alert-<?= htmlspecialchars($flashCompte[0]) ?>"><?= htmlspecialchars($flashCompte[1]) ?></div><?php endif; ?>
                <p class="fiche-livre__auteur"><a href="livres_auteur.php?auteur=<?= rawurlencode($livre['auteur']) ?>"><?= htmlspecialchars($livre['auteur']) ?></a><?php if ($serieLivre): ?> · <a href="serie.php?id=<?= (int) $serieLivre['id_serie'] ?>"><?= htmlspecialchars($serieLivre['nom']) ?> #<?= htmlspecialchars(numeroTomeLisible($serieLivre['numero_tome'])) ?></a><?php endif; ?></p>
                <h1><?= htmlspecialchars($livre['titre']) ?></h1>
                <a href="#avis" class="fiche-livre__avis"><?php if ($noteEntete['nb']): ?><?= etoiles($noteEntete['moyenne']) ?> <span><?= number_format($noteEntete['moyenne'], 1, ',', '') ?> · <?= (int) $noteEntete['nb'] ?> avis</span><?php else: ?><i class="bi bi-star" aria-hidden="true"></i> Donner le premier avis<?php endif; ?></a>
                <div class="fiche-livre__prix">
                    <strong data-prix-fiche><?= $selectionMagasin ? formaterPrix($selectionMagasin['prix']) : 'Épuisé' ?></strong>
                    <?php if ($prixNeufLivre): ?><s data-prix-neuf-fiche <?= !$selectionMagasin || $prixNeufLivre <= $selectionMagasin['prix'] ? 'hidden' : '' ?> title="Prix neuf de référence"><?= formaterPrix($prixNeufLivre) ?></s><?php endif; ?>
                    <span class="fiche-livre__economie" data-economie-fiche <?= !$selectionMagasin || !$prixNeufLivre || $prixNeufLivre <= $selectionMagasin['prix'] ? 'hidden' : '' ?>><?= $selectionMagasin && $prixNeufLivre > $selectionMagasin['prix'] ? '−' . round((1 - $selectionMagasin['prix'] / $prixNeufLivre) * 100) . ' %' : '' ?></span>
                </div>
                <h2 class="h6">Sélectionnez la condition</h2>
                <form method="POST" action="panier_action.php" data-panier-formulaire>
                    <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
                    <input type="hidden" name="id_exemplaire" value="<?= (int) ($selectionMagasin['id_exemplaire'] ?? 0) ?>">
                    <div class="conditions-livre" role="group" aria-label="Conditions disponibles">
                        <?php foreach ($etatsMagasin as $etat => $offreEtat): ?>
                            <label class="condition-livre">
                                <input class="visually-hidden" type="radio" name="condition" data-condition-fiche data-prix="<?= $offreEtat['prix'] ?? 0 ?>" data-stock-bas="<?= $offreEtat && stockEstBas((int) $offreEtat['libre']) ? '1' : '0' ?>" value="<?= (int) ($offreEtat['id_exemplaire'] ?? 0) ?>" <?= $selectionMagasin && $offreEtat && $selectionMagasin['id_exemplaire'] === $offreEtat['id_exemplaire'] ? 'checked' : '' ?> <?= !$offreEtat || $offreEtat['libre'] <= 0 ? 'disabled' : '' ?>>
                                <strong><?= $etat === 'use' ? 'Correct' : ($etat === 'tres bon' ? 'Très bon' : ucfirst($etat)) ?></strong>
                                <span><?= $offreEtat ? formaterPrix($offreEtat['prix']) : '—' ?></span>
                                <?php if (!$offreEtat || $offreEtat['libre'] <= 0): ?><span>Déjà vendu</span><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="small text-muted">Correct : marques d’usage visibles. Bon et très bon : usure plus légère. Neuf : sans usure.</p>
                    <p data-stock-bas <?= !$selectionMagasin || !stockEstBas((int) $selectionMagasin['libre']) ? 'hidden' : '' ?>><?= badgeStockBas() ?></p>
                    <div class="fiche-livre__boutons"><button class="bouton bouton-action" type="submit" name="action" value="ajouter" <?= !$selectionMagasin ? 'disabled' : '' ?>><i class="bi bi-bag-plus" aria-hidden="true"></i> Ajouter au panier</button></div>
                    <noscript><?php if ($selectionMagasin): ?><p>Choisissez un état puis ajoutez-le au panier. Le prix total sera confirmé dans le panier.</p><?php endif; ?></noscript>
                </form>
                <div class="fiche-livre__boutons">
                    <?php if ($idUserConnecte): ?><?= $enFavori ? formulaireCompte('favori_retirer', (int) $id, '<i class="bi bi-heart-fill"></i> Retirer des favoris', 'btn btn-outline-success', 'Retirer de mes favoris') : formulaireCompte('favori_ajouter', (int) $id, '<i class="bi bi-heart"></i> Ajouter aux favoris', 'btn btn-outline-success', 'Ajouter à mes favoris') ?><?php else: ?><a href="login.php" class="btn btn-outline-success"><i class="bi bi-heart" aria-hidden="true"></i> Ajouter aux favoris</a><?php endif; ?>
                </div>
                <?php if ($nombreEditions > 0): ?><a class="fiche-livre__editions" href="editions_livre.php?id=<?= (int) $id ?>">Découvrir <?= $nombreEditions ?> autre<?= $nombreEditions > 1 ? 's' : '' ?> édition<?= $nombreEditions > 1 ? 's' : '' ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
                <div class="fiche-informations">
                    <details><summary>Livraison</summary><p>1,99 € de livraison sous 20 € de livres. Offerte à partir de 20 €, avant utilisation d’un bon.</p></details>
                    <details><summary>Paiement sécurisé</summary><p>Les commandes sont simulées : aucun paiement réel ni donnée bancaire n’est demandé sur cette version.</p></details>
                    <details><summary>Nos engagements</summary><p>Des états et des prix annoncés avant l’ajout, des échanges suivis et une nouvelle vie pour les livres.</p></details>
                </div>
            </section>
            <section class="fiche-livre__onglets" aria-label="En savoir plus sur le livre">
                <ul class="nav nav-pills gap-1" role="tablist">
                    <?php foreach (['resume' => 'Le résumé', 'details' => 'Détails du livre', 'auteur' => 'Auteur'] as $cle => $libelle): ?>
                        <li class="nav-item" role="presentation"><button class="nav-link <?= $cle === 'resume' ? 'active' : '' ?>" id="onglet-<?= $cle ?>" data-bs-toggle="tab" data-bs-target="#contenu-<?= $cle ?>" type="button" role="tab" aria-controls="contenu-<?= $cle ?>" aria-selected="<?= $cle === 'resume' ? 'true' : 'false' ?>"><?= $libelle ?></button></li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="contenu-resume" role="tabpanel" aria-labelledby="onglet-resume" tabindex="0">
                        <?php if (mb_strlen((string) $livre['description']) > 550): ?>
                            <div class="resume-depliable">
                                <p class="fiche-livre__resume resume-extrait"><?= nl2br(htmlspecialchars(mb_strimwidth($livre['description'], 0, 550, '…'))) ?></p>
                                <details><summary><span class="resume-plus">Afficher plus</span><span class="resume-moins">Afficher moins</span></summary><p class="fiche-livre__resume"><?= nl2br(htmlspecialchars($livre['description'])) ?></p></details>
                            </div>
                        <?php else: ?><p class="fiche-livre__resume"><?= nl2br(htmlspecialchars($livre['description'] ?: 'Le résumé de cette édition sera complété prochainement.')) ?></p><?php endif; ?>
                        <div class="fiche-livre__tags">
                            <?php foreach (array_slice($tagsLivre, 0, 4) as $tagLivre): ?><a href="catalogue.php?tag=<?= rawurlencode($tagLivre['slug']) ?>">#<?= htmlspecialchars($tagLivre['nom']) ?></a><?php endforeach; ?>
                            <?php if (count($tagsLivre) > 4): ?><details><summary>+<?= count($tagsLivre) - 4 ?></summary><?php foreach (array_slice($tagsLivre, 4) as $tagLivre): ?><a href="catalogue.php?tag=<?= rawurlencode($tagLivre['slug']) ?>">#<?= htmlspecialchars($tagLivre['nom']) ?></a><?php endforeach; ?></details><?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane" id="contenu-details" role="tabpanel" aria-labelledby="onglet-details" tabindex="0">
                        <dl class="mt-3 fiche-details">
                            <?php foreach ($caracteristiquesLivre as $libelle => $valeur): ?><div><dt><?= htmlspecialchars($libelle) ?></dt><dd><?= htmlspecialchars((string) $valeur) ?></dd></div><?php endforeach; ?>
                        </dl>
                    </div>
                    <div class="tab-pane" id="contenu-auteur" role="tabpanel" aria-labelledby="onglet-auteur" tabindex="0">
                        <h2 class="h5 mt-3"><?= htmlspecialchars($livre['auteur']) ?></h2>
                        <p class="text-muted">Retrouvez les livres de cet auteur disponibles dans notre catalogue.</p>
                        <a class="btn btn-outline-success" href="livres_auteur.php?auteur=<?= rawurlencode($livre['auteur']) ?>">Voir ses livres</a>
                        <?php if ($slugAuteur): ?><a class="d-block mt-3" href="auteur.php?a=<?= rawurlencode($slugAuteur) ?>">Découvrir sa page auteur</a><?php endif; ?>
                    </div>
                </div>
            </section>
            <section class="fiche-livre__communaute">
                <?php if ($etatEmprunt['total'] > 0): ?><?php include __DIR__ . '/partie_emprunt_livre.php'; ?><hr><?php endif; ?>
                <?php if (!$selectionMagasin): ?>
                    <?php if (!$idUserConnecte): ?><a class="btn btn-outline-success mb-3" href="login.php">Être averti du retour en stock</a><?php elseif ($alerteActive): ?><p>Vous serez averti du retour en stock.</p><?= formulaireCompte('alerte_retirer', (int) $id, 'Annuler l’alerte', 'btn btn-link') ?><?php else: ?><?= formulaireCompte('alerte_ajouter', (int) $id, 'M’avertir du retour en stock', 'btn btn-outline-success') ?><?php endif; ?>
                <?php endif; ?>
                <p class="mb-2">Vous avez ce livre ? <a href="annonce_creer.php?livre=<?= (int) $id ?>">Vendez-le</a> ou <a href="don_creer.php?livre=<?= (int) $id ?>">donnez-le</a>.</p>
                <a class="small" href="#offres">Voir toutes les offres : magasin, lecteurs, dons et emprunts</a>
                <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'gestionnaire'], true)): ?><div class="d-flex flex-wrap gap-2 mt-3"><a href="admin/livre.php?id=<?= (int) $id ?>" class="btn btn-sm btn-outline-secondary">Modifier la fiche</a><a href="admin/exemplaires.php?id=<?= (int) $id ?>" class="btn btn-sm btn-outline-success">Gérer le stock</a></div><?php endif; ?>
            </section>
    </div>
</main>
<?php if ($serieLivre && $selectionSerie['tomes']): ?>
<section class="serie-complete" aria-labelledby="titre-serie-complete">
    <div>
        <p class="small text-muted mb-2"><?= htmlspecialchars($serieLivre['auteur']) ?></p>
        <h2 id="titre-serie-complete">Acheter la série complète</h2>
        <p class="mb-1"><?= htmlspecialchars($serieLivre['nom']) ?></p>
        <p class="small text-muted"><?= count($selectionSerie['tomes']) ?> tome(s) au catalogue · <?= count($selectionSerie['offres']) ?> disponible(s)</p>
        <a href="serie.php?id=<?= (int) $serieLivre['id_serie'] ?>">Voir toute la série</a>
        <div class="serie-complete__total"><?= formaterPrix($selectionSerie['total']) ?></div>
        <?php if ($selectionSerie['total_neuf'] !== null && $selectionSerie['total_neuf'] > $selectionSerie['total']): ?><p class="small">Économisez <?= formaterPrix($selectionSerie['total_neuf'] - $selectionSerie['total']) ?> par rapport au neuf sur les tomes disponibles.</p><?php endif; ?>
        <form method="POST" action="panier_action.php" data-panier-formulaire>
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>"><input type="hidden" name="action" value="serie"><input type="hidden" name="id_serie" value="<?= (int) $serieLivre['id_serie'] ?>">
            <button class="bouton bouton-action w-100" <?= !$selectionSerie['offres'] ? 'disabled' : '' ?>>Ajouter les <?= count($selectionSerie['offres']) ?> tomes disponibles</button>
        </form>
        <p class="small text-muted mt-2 mb-0">Seuls les tomes en stock sont ajoutés, au meilleur prix disponible pour chacun.</p>
    </div>
    <div style="min-width:0;">
        <div class="serie-complete__navigation"><button type="button" data-carrousel-cible="tomes-serie" data-direction="-1" aria-label="Tomes précédents"><i class="bi bi-chevron-left" aria-hidden="true"></i></button><button type="button" data-carrousel-cible="tomes-serie" data-direction="1" aria-label="Tomes suivants"><i class="bi bi-chevron-right" aria-hidden="true"></i></button></div>
        <div class="serie-complete__tomes" id="tomes-serie">
            <?php foreach ($selectionSerie['tomes'] as $tome): ?><a class="serie-tome" href="detail_livre.php?id=<?= (int) $tome['id_livre'] ?>"><img src="<?= htmlspecialchars(urlImage($tome['couverture'])) ?>" alt="Couverture de <?= htmlspecialchars($tome['titre']) ?>" loading="lazy"><span>Tome <?= htmlspecialchars(numeroTomeLisible($tome['numero_tome'])) ?></span><strong><?= htmlspecialchars($tome['titre']) ?></strong></a><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
