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

const SEPARATEURS_RECHERCHE = ['-', ' ', "\u{2010}", "\u{2011}", "\u{2013}", "\u{2014}", "'", "\u{2019}", '.', ':', ',', "\u{00A0}"];

function texteCompactRecherche(string $texte): string
{
    return str_replace(SEPARATEURS_RECHERCHE, '', $texte);
}

function colonneCompacteSql(string $colonne): string
{
    $expression = "COALESCE($colonne, '')";
    foreach (SEPARATEURS_RECHERCHE as $separateur) {
        $expression = "REPLACE($expression, '" . str_replace("'", "''", $separateur) . "', '')";
    }
    return $expression;
}

function motifCompactRecherche(string $texte): string
{
    $compact = texteCompactRecherche($texte);
    return mb_strlen($compact) >= 2 ? motifRecherche($compact) : '';
}
