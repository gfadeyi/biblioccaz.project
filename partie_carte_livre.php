<?php
require_once __DIR__ . '/livres_lib.php';
$favori = in_array((int) $l['id_livre'], $idsFavoris ?? [], true);
$couverture = !empty($l['couverture']) ? $l['couverture'] : 'default.png';
$lienLivre = 'detail_livre.php?id=' . (int) $l['id_livre'];
$stockLivre = isset($l['stock']) ? (int) $l['stock'] : null;
$nbOffres = isset($l['nb_offres']) ? (int) $l['nb_offres'] : 0;
$originesLivre = explode(',', $l['origines'] ?? 'magasin');
$sansPrix = !array_intersect(['magasin', 'particulier'], $originesLivre);
$donSeulement = $sansPrix && !in_array('emprunt', $originesLivre, true);
$empruntSeulement = $sansPrix && !$donSeulement;
?>
<article class="carte-livre">
    <div class="carte-livre__visuel">
        <div class="carte-livre__badges">
            <?php if (!empty($l['coup_de_coeur'])): ?>
                <span class="badge-bibli badge-coeur"><i class="bi bi-heart-fill"></i> Coup de cœur</span>
            <?php endif; ?>
            <?php if (in_array('particulier', $originesLivre, true) && !in_array('magasin', $originesLivre, true)): ?>
                <span class="badge-bibli badge-don"><i class="bi bi-people" aria-hidden="true"></i> Entre particuliers</span>
            <?php endif; ?>
            <?php if (in_array('emprunt', $originesLivre, true) && !$empruntSeulement): ?>
                <span class="badge-bibli badge-don"><i class="bi bi-book" aria-hidden="true"></i> Aussi à emprunter</span>
            <?php endif; ?>
            <?php if (in_array('don', $originesLivre, true) && !$sansPrix): ?>
                <span class="badge-bibli badge-don"><i class="bi bi-gift" aria-hidden="true"></i> Aussi en don</span>
            <?php endif; ?>
            <?php if ($stockLivre === 1): ?>
                <span class="badge-bibli badge-dernier">Dernier exemplaire</span>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <button type="button" class="bouton-coeur <?= $favori ? 'actif' : '' ?>" data-livre="<?= (int) $l['id_livre'] ?>"
                    aria-pressed="<?= $favori ? 'true' : 'false' ?>" aria-label="Favori : <?= htmlspecialchars($l['titre']) ?>"
                    title="<?= $favori ? "Retirer de mes favoris" : "Ajouter à mes favoris" ?>">
                <i class="bi bi-heart<?= $favori ? '-fill' : '' ?>"></i>
            </button>
        <?php else: ?>
            <a href="login.php" class="bouton-coeur" title="Connectez-vous pour ajouter ce livre à vos favoris" aria-label="Se connecter pour ajouter ce livre à vos favoris"><i class="bi bi-heart"></i></a>
        <?php endif; ?>
        <img src="<?= htmlspecialchars(urlImage($couverture)) ?>" alt="Couverture de <?= htmlspecialchars($l['titre']) ?>" loading="lazy">
    </div>

    <div class="carte-livre__corps">
        <h3 class="carte-livre__titre"><a href="<?= $lienLivre ?>"><?= htmlspecialchars($l['titre']) ?></a></h3>
        <p class="carte-livre__auteur"><?= htmlspecialchars($l['auteur'] ?? '') ?></p>
        <?php if (isset($l['nb_avis'])): ?>
            <div class="carte-livre__note">
                <?php if ($l['nb_avis'] > 0): ?>
                    <?= etoiles((float) $l['note_moyenne']) ?> (<?= (int) $l['nb_avis'] ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="carte-livre__pied">
            <?php if ($nbOffres > 1): ?>
                <p class="carte-livre__offres"><?= $nbOffres ?> offres dès <?= formaterPrix((float) $l['prix']) ?></p>
            <?php endif; ?>
            <div class="carte-livre__bas">
                <span class="badge-bibli badge-etat"><?= htmlspecialchars(libelleEtat($l['etat'])) ?></span>
                <?php if ($empruntSeulement): ?>
                    <span class="prix"><i class="bi bi-book" aria-hidden="true"></i> À emprunter</span>
                <?php elseif ($donSeulement): ?>
                    <span class="prix"><i class="bi bi-gift" aria-hidden="true"></i> Don</span>
                <?php else: ?>
                    <span class="prix"><?= formaterPrix((float) $l['prix']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>
