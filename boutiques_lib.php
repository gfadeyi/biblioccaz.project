<?php

const BOUTIQUES = [
    'paris' => ['ville' => 'Paris', 'region' => 'Île-de-France', 'postal' => '75', 'departements' => ['75', '77', '78', '91', '92', '93', '94', '95'], 'accroche' => 'Des histoires à partager, de quartier en quartier.', 'lieux' => ['Paris', 'Montreuil', 'Saint-Denis', 'Créteil', 'Vincennes', 'Versailles', 'Boulogne-Billancourt']],
    'lyon' => ['ville' => 'Lyon', 'region' => 'Auvergne-Rhône-Alpes', 'postal' => '69', 'accroche' => 'Un nouveau chapitre entre Rhône et Saône.', 'lieux' => ['Lyon', 'Villeurbanne']],
    'bordeaux' => ['ville' => 'Bordeaux', 'region' => 'Nouvelle-Aquitaine', 'postal' => '33', 'accroche' => 'Le goût des livres qui passent de main en main.', 'lieux' => ['Bordeaux']],
    'lille' => ['ville' => 'Lille', 'region' => 'Hauts-de-France', 'postal' => '59', 'accroche' => 'Un coin chaleureux pour les lecteurs du Nord.', 'lieux' => ['Lille']],
    'nantes' => ['ville' => 'Nantes', 'region' => 'Pays de la Loire', 'postal' => '44', 'accroche' => 'Des lectures et des rencontres au fil de la Loire.', 'lieux' => ['Nantes']],
    'toulouse' => ['ville' => 'Toulouse', 'region' => 'Occitanie', 'postal' => '31', 'accroche' => 'La lecture prend ses quartiers dans le Sud-Ouest.', 'lieux' => ['Toulouse']],
];

function texteRechercheBoutique(string $texte): string
{
    return strtr(mb_strtolower(trim($texte)), ['à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'œ' => 'oe']);
}

function boutiquesRecherche(string $recherche): array
{
    $recherche = texteRechercheBoutique(mb_substr($recherche, 0, 80));
    if ($recherche === '') { return BOUTIQUES; }
    return array_filter(BOUTIQUES, function (array $boutique) use ($recherche): bool {
        if (preg_match('/\A\d{2,5}\z/', $recherche)) {
            foreach ($boutique['departements'] ?? [$boutique['postal']] as $departement) {
                if (str_starts_with($recherche, $departement)) { return true; }
            }
            return false;
        }
        return str_contains(texteRechercheBoutique($boutique['ville'] . ' ' . $boutique['region'] . ' ' . implode(' ', $boutique['lieux'])), $recherche);
    });
}

function motifLieuBoutique(string $slug): string
{
    if (!isset(BOUTIQUES[$slug])) { return '(?!)'; }
    return '(^|[^[:alpha:]])(' . implode('|', array_map(fn($lieu) => preg_quote($lieu, '~'), BOUTIQUES[$slug]['lieux'] ?? [])) . ')([^[:alpha:]]|$)';
}

function boutiquePourLieu(string $lieu): ?string
{
    foreach (BOUTIQUES as $slug => $boutique) {
        $mots = array_map(fn($ville) => preg_quote(texteRechercheBoutique($ville), '~'), $boutique['lieux']);
        if (preg_match('~(?<!\p{L})(?:' . implode('|', $mots) . ')(?!\p{L})~u', texteRechercheBoutique($lieu))) { return $slug; }
    }
    return null;
}

function nombresRencontresBoutiques(PDO $pdo): ?array
{
    try {
        $lecture = $pdo->prepare("SELECT lieu, COUNT(*) AS nombre FROM evenement WHERE statut = 'publie' AND date_fin >= NOW() GROUP BY lieu");
        $lecture->execute();
        $nombres = array_fill_keys(array_keys(BOUTIQUES), 0);
        foreach ($lecture->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $slug = boutiquePourLieu($ligne['lieu']);
            if ($slug !== null) { $nombres[$slug] += (int) $ligne['nombre']; }
        }
        return $nombres;
    } catch (PDOException $e) {
        return null;
    }
}
