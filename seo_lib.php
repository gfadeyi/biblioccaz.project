<?php
require_once __DIR__ . '/urls_lib.php';

const PAGES_PUBLIQUES_INDEXABLES = ['index.php', 'catalogue.php', 'bd_mangas.php', 'jeunesse.php', 'roman.php', 'scolaire.php', 'petits_prix.php', 'nouveauté.php', 'dernière_chance.php', 'meilleures_ventes.php', 'plus_dons.php', 'plus_emprunts.php', 'dons.php', 'evenements.php', 'auteurs.php', 'mentions_legales.php', 'confidentialite.php', 'cgu.php', 'detail_livre.php', 'serie.php', 'editions_livre.php', 'auteur.php', 'evenement.php', 'qui_sommes_nous.php'];

function cheminPageReferencement(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $racine = str_replace('\\', '/', __DIR__) . '/';
    return str_starts_with($script, $racine) ? substr($script, strlen($racine)) : basename($script);
}

function adresseCanonique(string $page): string
{
    $url = rtrim(SITE_URL, '/') . '/' . ltrim(urlPropre($page), './');
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
    if ($page === 'index.php') { return 'BibliOccaz : livres d’occasion, dons, emprunts et rencontres entre lecteurs. Site de démonstration, commandes et paiements fictifs.'; }
    return mb_substr(trim($titre) . ' sur BibliOccaz, la librairie communautaire de démonstration. Commandes et paiements fictifs.', 0, 180);
}

function urlAbsolueSite(string $chemin = ''): string
{
    if (preg_match('~\Ahttps://~', $chemin)) {
        return $chemin;
    }
    return rtrim(SITE_URL, '/') . '/' . ltrim(preg_replace('~\A\./~', '', $chemin), '/');
}

function texteReferencement(?string $texte, int $longueur = 180): string
{
    $texte = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $texte)));
    if (mb_strlen($texte) <= $longueur) {
        return $texte;
    }
    $coupe = mb_substr($texte, 0, $longueur - 1);
    $espace = mb_strrpos($coupe, ' ');
    return rtrim($espace !== false && $espace > $longueur * 0.6 ? mb_substr($coupe, 0, $espace) : $coupe, " ,;:.-") . '…';
}

function baliseDonneesStructurees(array $donnees): string
{
    $json = json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    return $json === false ? '' : '<script type="application/ld+json">' . $json . '</script>';
}

function donneesOrganisation(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => urlAbsolueSite() . '#organisation',
        'name' => 'BibliOccaz',
        'alternateName' => 'BIBLIOccaz',
        'url' => urlAbsolueSite(),
        'logo' => urlAbsolueSite('icon-512.png'),
        'description' => 'Librairie communautaire de démonstration : livres d’occasion, dons, emprunts et rencontres entre lecteurs.',
    ];
}

function donneesSiteWeb(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => urlAbsolueSite() . '#site',
        'name' => 'BibliOccaz',
        'url' => urlAbsolueSite(),
        'inLanguage' => 'fr-FR',
        'publisher' => ['@id' => urlAbsolueSite() . '#organisation'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => urlAbsolueSite('catalogue') . '?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function donneesFilAriane(array $etapes): array
{
    $elements = [];
    foreach (array_values($etapes) as $position => [$nom, $chemin]) {
        $elements[] = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $nom, 'item' => urlAbsolueSite($chemin)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elements];
}

function isbnPourReferencement(?string $isbn): ?string
{
    $isbn = strtoupper(str_replace([' ', '-'], '', trim((string) $isbn)));
    if (preg_match('/\A97[89][0-9]{10}\z/', $isbn)) {
        $somme = 0;
        for ($i = 0; $i < 12; $i++) {
            $somme += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return (10 - $somme % 10) % 10 === (int) $isbn[12] ? $isbn : null;
    }
    if (preg_match('/\A[0-9]{9}[0-9X]\z/', $isbn)) {
        $somme = 0;
        for ($i = 0; $i < 10; $i++) {
            $somme += ($isbn[$i] === 'X' ? 10 : (int) $isbn[$i]) * (10 - $i);
        }
        return $somme % 11 === 0 ? $isbn : null;
    }
    return null;
}

function donneesLivre(array $livre, array $offres, array $note): array
{
    $adresse = urlAbsolueSite('livre') . '?id=' . (int) $livre['id_livre'];
    $donnees = [
        '@context' => 'https://schema.org',
        '@type' => ['Book', 'Product'],
        '@id' => $adresse . '#livre',
        'name' => (string) $livre['titre'],
        'url' => $adresse,
        'image' => urlAbsolueSite(urlImage($livre['couverture'] ?? null)),
    ];
    if (trim((string) ($livre['auteur'] ?? '')) !== '') {
        $donnees['author'] = ['@type' => 'Person', 'name' => trim((string) $livre['auteur'])];
    }
    if (trim((string) ($livre['description'] ?? '')) !== '') {
        $donnees['description'] = texteReferencement($livre['description'], 500);
    }
    $isbn = isbnPourReferencement($livre['isbn'] ?? null);
    if ($isbn !== null) {
        $donnees['isbn'] = $isbn;
        if (strlen($isbn) === 13) {
            $donnees['gtin13'] = $isbn;
        }
    }
    if (trim((string) ($livre['editeur'] ?? '')) !== '') {
        $donnees['publisher'] = ['@type' => 'Organization', 'name' => trim((string) $livre['editeur'])];
    }
    if (!empty($livre['date_parution']) && preg_match('/\A\d{4}-\d{2}-\d{2}/', (string) $livre['date_parution'])) {
        $donnees['datePublished'] = substr((string) $livre['date_parution'], 0, 10);
    } elseif (preg_match('/\A(1[4-9]|20)\d{2}\z/', (string) ($livre['annee_parution'] ?? ''))) {
        $donnees['datePublished'] = (string) $livre['annee_parution'];
    }
    if ((int) ($livre['nb_pages'] ?? 0) > 0) {
        $donnees['numberOfPages'] = (int) $livre['nb_pages'];
    }
    $prix = [];
    foreach ($offres as $offre) {
        if (in_array($offre['origine'] ?? '', ['magasin', 'particulier'], true) && (float) $offre['prix'] > 0) {
            $prix[] = (float) $offre['prix'];
        }
    }
    if ($prix) {
        $donnees['offers'] = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'EUR',
            'lowPrice' => number_format(min($prix), 2, '.', ''),
            'highPrice' => number_format(max($prix), 2, '.', ''),
            'offerCount' => count($prix),
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/UsedCondition',
            'url' => $adresse,
        ];
    }
    if ((int) ($note['nb'] ?? 0) > 0 && (float) ($note['moyenne'] ?? 0) > 0) {
        $donnees['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => number_format((float) $note['moyenne'], 1, '.', ''), 'bestRating' => '5', 'worstRating' => '1', 'ratingCount' => (int) $note['nb']];
    }
    return $donnees;
}

function donneesEvenement(array $evt, string $etat, int $placesRestantes): array
{
    $adresse = urlAbsolueSite('evenement') . '?id=' . (int) $evt['id_evenement'];
    $fuseau = new DateTimeZone(date_default_timezone_get());
    $donnees = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        '@id' => $adresse . '#evenement',
        'name' => (string) $evt['titre'],
        'url' => $adresse,
        'startDate' => (new DateTimeImmutable((string) $evt['date_debut'], $fuseau))->format(DATE_ATOM),
        'eventStatus' => 'https://schema.org/' . ($etat === 'annule' ? 'EventCancelled' : 'EventScheduled'),
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'organizer' => ['@type' => 'Organization', 'name' => 'BibliOccaz', 'url' => urlAbsolueSite()],
    ];
    if (!empty($evt['date_fin'])) {
        $donnees['endDate'] = (new DateTimeImmutable((string) $evt['date_fin'], $fuseau))->format(DATE_ATOM);
    }
    if (trim((string) ($evt['lieu'] ?? '')) !== '') {
        $donnees['location'] = ['@type' => 'Place', 'name' => trim((string) $evt['lieu']), 'address' => trim((string) $evt['lieu'])];
    }
    if (trim((string) ($evt['description'] ?? '')) !== '') {
        $donnees['description'] = texteReferencement($evt['description'], 500);
    }
    if (!empty($evt['image'])) {
        $donnees['image'] = urlAbsolueSite(urlImage($evt['image']));
    }
    if ((int) ($evt['places_max'] ?? 0) > 0) {
        $donnees['maximumAttendeeCapacity'] = (int) $evt['places_max'];
        $donnees['remainingAttendeeCapacity'] = $placesRestantes;
    }
    $prix = (float) ($evt['prix'] ?? 0);
    $donnees['isAccessibleForFree'] = $prix <= 0;
    if (in_array($etat, ['ouvert', 'complet'], true)) {
        $donnees['offers'] = [
            '@type' => 'Offer',
            'price' => number_format(max(0, $prix), 2, '.', ''),
            'priceCurrency' => 'EUR',
            'availability' => 'https://schema.org/' . ($etat === 'complet' ? 'SoldOut' : 'InStock'),
            'url' => $adresse,
        ];
    }
    return $donnees;
}
