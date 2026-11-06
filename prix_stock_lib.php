<?php

function suggestionsPrixStock(?float $prixNeuf, array $offres): array
{
    $coefficients = ['neuf' => 1, 'tres bon' => 0.63, 'bon' => 0.46, 'use' => 0.30];
    $suggestions = [];
    foreach ($coefficients as $etat => $coefficient) {
        $historique = array_filter($offres, fn($offre) => $offre['etat'] === $etat && is_finite((float) $offre['prix']) && (float) $offre['prix'] > 0);
        usort($historique, fn($a, $b) => (int) $b['id_exemplaire'] <=> (int) $a['id_exemplaire']);
        if ($historique) {
            $offre = $historique[0];
            $suggestions[$etat] = ['prix' => round((float) $offre['prix'], 2), 'origine' => 'Dernier tarif enregistré pour cet état, offre n° ' . (int) $offre['id_exemplaire'] . '.'];
        } elseif ($prixNeuf !== null && is_finite($prixNeuf) && $prixNeuf > 0) {
            $suggestions[$etat] = ['prix' => max(0.01, round($prixNeuf * $coefficient, 2)), 'origine' => $etat === 'neuf' ? 'Prix neuf de référence enregistré sur la fiche.' : (int) round($coefficient * 100) . ' % du prix neuf de référence enregistré.'];
        } else {
            $suggestions[$etat] = ['prix' => null, 'origine' => 'Aucun ancien tarif ni prix neuf de référence disponible pour cet état. Saisissez votre prix.'];
        }
    }
    return $suggestions;
}
