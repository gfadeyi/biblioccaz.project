<?php

const POINTS_PAR_EURO = 1;

function calculerTotauxPanier(array $panier): array
{
    $sousTotal = 0;
    $nbArticles = 0;
    foreach ($panier as $item) {
        $sousTotal += $item['prix'] * $item['quantite'];
        $nbArticles += $item['quantite'];
    }

    $remise = 0;
    if ($sousTotal >= 50) {
        $remise = $sousTotal * 0.10;
    } elseif ($sousTotal >= 30) {
        $remise = $sousTotal * 0.05;
    }
    $remise = round($remise, 2);

    $fraisLivraison = ($sousTotal > 0 && $sousTotal < 20) ? 1.99 : 0;

    $total = round($sousTotal - $remise + $fraisLivraison, 2);
    $points = (int) floor(($sousTotal - $remise) * POINTS_PAR_EURO);

    return [
        'sous_total'      => round($sousTotal, 2),
        'nb_articles'     => $nbArticles,
        'remise'          => $remise,
        'frais_livraison' => $fraisLivraison,
        'total'           => $total,
        'points'          => $points,
    ];
}
