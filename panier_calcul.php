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

const PALIERS_PANIER = [
    ['montant' => 20, 'court' => 'Livraison offerte', 'mini' => 'Livraison', 'objectif' => 'la livraison offerte', 'icone' => 'truck'],
    ['montant' => 30, 'court' => '-5 %', 'mini' => '-5 %', 'objectif' => '-5 % sur tout le panier', 'icone' => 'percent'],
    ['montant' => 50, 'court' => '-10 %', 'mini' => '-10 %', 'objectif' => '-10 % sur tout le panier', 'icone' => 'stars'],
];

function progressionPanier(float $sousTotal): array
{
    $montantMax = PALIERS_PANIER[array_key_last(PALIERS_PANIER)]['montant'];
    $prochain = null;
    foreach (PALIERS_PANIER as $palier) {
        if ($sousTotal < $palier['montant']) {
            $prochain = $palier;
            break;
        }
    }
    return [
        'montant_max' => $montantMax,
        'progression' => max(0, min(1, $sousTotal / $montantMax)),
        'prochain'    => $prochain,
        'reste'       => $prochain ? round($prochain['montant'] - $sousTotal, 2) : 0,
    ];
}
