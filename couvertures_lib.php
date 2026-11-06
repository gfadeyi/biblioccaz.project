<?php

function couverturePourCarte(?string $fichier): array
{
    $original = urlImage($fichier);
    if (!$fichier || !str_starts_with($fichier, 'livres/') || str_contains($fichier, '..')) {
        return ['src' => $original, 'srcset' => null];
    }
    $miniature = 'livres/miniatures/' . hash('sha256', $fichier) . '.webp';
    $source = __DIR__ . '/img/' . $fichier;
    $cible = __DIR__ . '/img/' . $miniature;
    if (!is_file($source) || !is_file($cible) || filemtime($cible) < filemtime($source)) {
        return ['src' => $original, 'srcset' => null];
    }
    $url = urlImage($miniature);
    $version = '?v=' . filemtime($cible);
    return ['src' => $url . $version, 'srcset' => $url . $version . ' 1x, ' . $original . ' 2x'];
}
