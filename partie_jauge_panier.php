<?php
$montantJauge = max(0, min(50, (float) $sousTotal));
$resteJauge = max(0, 50 - (float) $sousTotal);
?>
<p class="message-encouragement"><i class="bi bi-tag" aria-hidden="true"></i><span><?= $resteJauge > 0 ? 'Encore <strong>' . formaterPrix($resteJauge) . '</strong> pour profiter de −10 % dès 50 € de livres.' : '<strong>−10 % sur vos livres !</strong> Le seuil de 50 € est atteint.' ?></span></p>
<div class="jauge" role="progressbar" aria-label="Montant de livres vers la remise de dix pour cent" aria-valuemin="0" aria-valuemax="50" aria-valuenow="<?= $montantJauge ?>" style="--progression:<?= $montantJauge / 50 ?>;">
    <div class="jauge__remplissage"></div>
    <span class="jauge__repere <?= $resteJauge <= 0 ? 'atteint' : '' ?>" style="--position:1;"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
</div>
<p class="small text-end text-muted mt-2 mb-0">−10 % dès 50 €</p>
