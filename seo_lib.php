<?php

const PAGES_PUBLIQUES_INDEXABLES = ['index.php', 'catalogue.php', 'bd_mangas.php', 'jeunesse.php', 'roman.php', 'scolaire.php', 'petits_prix.php', 'nouveautÃ©.php', 'derniÃ¨re_chance.php', 'meilleures_ventes.php', 'plus_dons.php', 'plus_emprunts.php', 'dons.php', 'evenements.php', 'auteurs.php', 'mentions_legales.php', 'confidentialite.php', 'cgu.php', 'detail_livre.php', 'serie.php', 'editions_livre.php', 'auteur.php', 'evenement.php'];

function cheminPageReferencement(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $racine = str_replace('\\', '/', __DIR__) . '/';
    return str_starts_with($script, $racine) ? substr($script, strlen($racine)) : basename($script);
}

function adresseCanonique(string $page): string
{
    $url = rtrim(SITE_URL, '/') . '/' . rawurlencode($page);
    if (in_array($page, ['detail_livre.php', 'serie.php', 'editions_livre.php', 'evenement.php'], true)) {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id ? $url . '?id=' . $id : '';
    }
    if (in_array($page, ['auteur.php', 'livres_auteur.php'], true)) {
        $slug = is_string($_GET['a'] ?? null) ? $_GET['a'] : '';
        return preg_match('/\A[a-z0-9-]{1,150}\z/', $slug) ? $url . '?a=' . rawurlencode($slug) : '';
    }
    return $url;
}

function indexationPermise(string $page): bool
{
    if (!modeProduction() || !in_array($page, PAGES_PUBLIQUES_INDEXABLES, true) || http_response_code() >= 400 || adresseCanonique($page) === '') { return false; }
    $parametresPermis = in_array($page, ['auteur.php', 'livres_auteur.php'], true) ? ['a'] : (in_array($page, ['detail_livre.php', 'serie.php', 'editions_livre.php', 'evenement.php'], true) ? ['id'] : []);
    return !array_diff(array_keys($_GET), $parametresPermis);
}

function descriptionReferencement(string $page, string $titre): string
{
    if ($page === 'index.php') { return 'BibliOccaz : livres dâ€™occasion, dons, emprunts et rencontres entre lecteurs. Site de dÃ©monstration, commandes et paiements fictifs.'; }
    return mb_substr(trim($titre) . ' sur BibliOccaz, la librairie communautaire de dÃ©monstration. Commandes et paiements fictifs.', 0, 180);
}
