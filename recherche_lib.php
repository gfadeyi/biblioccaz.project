<?php

function texteRecherche(mixed $valeur, int $longueur = 200): string
{
    return is_string($valeur) ? mb_substr(trim($valeur), 0, $longueur) : '';
}

function motifRecherche(string $texte): string
{
    return '%' . strtr($texte, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
}

function isbnRecherche(string $texte): string
{
    $normalise = str_replace([' ', '-'], '', $texte);
    return preg_match('/\A[0-9]+[Xx]?\z/', $normalise) ? strtoupper($normalise) : '';
}
