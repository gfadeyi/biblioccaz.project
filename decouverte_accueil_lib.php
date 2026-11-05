<?php
require_once __DIR__ . '/livres_lib.php';

function selectionsRayonsAccueil(PDO $pdo, ?string $jour = null): array
{
    $rayons = [
        ['titre' => 'Romans à découvrir', 'detail' => 'Une autre histoire vous attend.', 'categories' => ['roman','classiques','romance','policier-thriller','science-fiction'], 'lien' => 'catalogue.php?categorie=litterature'],
        ['titre' => 'Le coin poésie & théâtre', 'detail' => 'Quelques vers, une voix, une scène.', 'categories' => ['poesie-theatre'], 'lien' => 'catalogue.php?categorie=poesie-theatre'],
        ['titre' => 'Révisions à petit prix', 'detail' => 'Des manuels disponibles à 10 € maximum. Vérifiez leur année d’édition.', 'categories' => ['primaire','college','lycee','universite','prepas-concours','soutien-scolaire'], 'lien' => 'scolaire.php', 'budget' => 10],
    ];
    foreach ($rayons as &$rayon) {
        $conditions = ' AND l.id_categorie IN (SELECT id_categorie FROM categorie WHERE slug IN (' . implode(',', array_fill(0, count($rayon['categories']), '?')) . '))';
        $sql = 'SELECT cartes.*, (SELECT MIN(id_serie) FROM livre_serie WHERE id_livre=cartes.id_livre) AS serie_selection FROM (' . sqlCartesLivres($conditions);
        if (isset($rayon['budget'])) { $sql .= ' HAVING prix <= ?'; }
        $sql .= ') cartes ORDER BY CRC32(CONCAT(?, cartes.id_livre)), cartes.id_livre LIMIT 160';
        $requete = $pdo->prepare($sql);
        $parametres = $rayon['categories'];
        if (isset($rayon['budget'])) { $parametres[] = $rayon['budget']; }
        $parametres[] = ($jour ?? date('Y-m-d')) . '-' . $rayon['categories'][0] . '-';
        $requete->execute($parametres);
        $rayon['livres'] = [];
        $series = [];
        foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $livre) {
            if (!aUneVraieCouverture($livre['couverture'])) { continue; }
            $cle = $livre['serie_selection'] === null ? 'livre-' . $livre['id_livre'] : 'serie-' . $livre['serie_selection'];
            if (isset($series[$cle])) { continue; }
            $series[$cle] = true;
            $rayon['livres'][] = $livre;
            if (count($rayon['livres']) === 8) { break; }
        }
    }
    unset($rayon);
    return $rayons;
}
