<?php
require_once __DIR__ . '/panier_calcul.php';
require_once __DIR__ . '/livres_lib.php';
$jauge = progressionPanier($sousTotal);
if ($sousTotal <= 0) {
    $iconeJauge = 'tag';
    $messageJauge = 'Votre panier est vide : la livraison est offerte dès <strong>' . PALIERS_PANIER[0]['montant'] . ' €</strong>. Faites le plein de <a href="petits_prix.php">bons plans à moins de 10 €</a>&nbsp;!';
} elseif ($jauge['prochain']) {
    $iconeJauge = $jauge['prochain']['icone'];
    $messageJauge = 'Plus que <strong>' . formatPrix($jauge['reste']) . '</strong> pour ' . $jauge['prochain']['objectif'] . '&nbsp;!';
} else {
    $iconeJauge = 'emoji-smile';
    $messageJauge = '<strong>Bravo&nbsp;!</strong> Livraison offerte et -10 % sur tout votre panier.';
}
?>
<p class="message-encouragement"><i class="bi bi-<?= $iconeJauge ?>"></i> <span><?= $messageJauge ?></span></p>
<div class="jauge" role="progressbar" aria-label="Montant du panier vers les avantages"
     aria-valuemin="0" aria-valuemax="<?= $jauge['montant_max'] ?>" aria-valuenow="<?= $sousTotal ?>"
     style="--progression: <?= round($jauge['progression'], 3) ?>;">
    <div class="jauge__remplissage"></div>
    <?php foreach (PALIERS_PANIER as $palier): ?>
        <span class="jauge__repere <?= $sousTotal >= $palier['montant'] ? 'atteint' : '' ?>"
              style="--position: <?= $palier['montant'] / $jauge['montant_max'] ?>;"><i class="bi bi-check-lg"></i></span>
    <?php endforeach; ?>
</div>
<ol class="jauge__paliers">
    <?php foreach (PALIERS_PANIER as $palier): ?>
        <li class="<?= $sousTotal >= $palier['montant'] ? 'atteint' : '' ?>" style="--position: <?= $palier['montant'] / $jauge['montant_max'] ?>;">
            <strong><span class="libelle-long"><?= $palier['court'] ?></span><span class="libelle-court"><?= $palier['mini'] ?></span></strong><span>dès <?= $palier['montant'] ?> €</span>
        </li>
    <?php endforeach; ?>
</ol>
