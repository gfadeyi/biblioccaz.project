<?php
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/categories_lib.php';

const BUDGETS_LECTURE = ['' => 'Sans limite', '5' => '5 € maximum', '10' => '10 € maximum', '20' => '20 € maximum'];

function choixLecture(PDO $pdo): array
{
    $univers = $pdo->prepare('SELECT id_categorie, nom, slug FROM categorie WHERE id_parent IS NULL ORDER BY ordre, nom');
    $univers->execute();
    $ambiances = $pdo->prepare("SELECT nom, slug FROM tag WHERE famille = 'theme' ORDER BY ordre, nom");
    $ambiances->execute();
    return ['univers' => $univers->fetchAll(PDO::FETCH_ASSOC), 'ambiance' => $ambiances->fetchAll(PDO::FETCH_ASSOC)];
}

function criteresLecture(array $entree, array $choix): array
{
    $criteres = [];
    foreach (['univers', 'ambiance', 'budget'] as $cle) {
        $valeur = $entree[$cle] ?? '';
        if (!is_string($valeur) || strlen($valeur) > 100) { throw new InvalidArgumentException('Merci de choisir parmi les options proposées.'); }
        $permis = $cle === 'budget' ? array_map('strval', array_keys(BUDGETS_LECTURE)) : ['', ...array_column($choix[$cle], 'slug')];
        if (!in_array($valeur, $permis, true)) { throw new InvalidArgumentException('Une option a changé. Merci de refaire votre choix.'); }
        $criteres[$cle] = $valeur;
    }
    return $criteres;
}

function selectionLecture(PDO $pdo, array $criteres, int $graine): array
{
    $conditions = '';
    $parametres = [];
    if ($criteres['univers'] !== '') {
        $ids = idsCategorieEtEnfants($pdo, $criteres['univers']);
        $conditions .= ' AND l.id_categorie IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        array_push($parametres, ...$ids);
    }
    if ($criteres['ambiance'] !== '') {
        $conditions .= ' AND EXISTS (SELECT 1 FROM livre_tag lt JOIN tag t ON t.id_tag = lt.id_tag WHERE lt.id_livre = l.id_livre AND t.slug = ?)';
        $parametres[] = $criteres['ambiance'];
    }
    $limitePrix = '';
    if ($criteres['budget'] !== '') {
        $limitePrix = ' HAVING prix <= ?';
        $parametres[] = (int) $criteres['budget'];
    }
    $parametres[] = (string) $graine;
    $requete = $pdo->prepare('SELECT cartes.*, (SELECT GROUP_CONCAT(ls.id_serie) FROM livre_serie ls WHERE ls.id_livre = cartes.id_livre) AS series FROM (' . sqlCartesLivres($conditions) . $limitePrix . ') cartes ORDER BY CRC32(CONCAT(?, \'-\', cartes.id_livre)), cartes.id_livre LIMIT 120');
    $requete->execute($parametres);
    $series = [];
    $resultat = [];
    foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $livre) {
        $ids = $livre['series'] ? explode(',', $livre['series']) : [];
        if (array_intersect($ids, array_keys($series))) { continue; }
        foreach ($ids as $id) { $series[$id] = true; }
        $resultat[] = $livre;
        if (count($resultat) === 6) { break; }
    }
    return $resultat;
}

function lienCatalogueLecture(array $criteres): string
{
    return 'catalogue.php?' . http_build_query(array_filter(['categorie' => $criteres['univers'], 'tag' => $criteres['ambiance'], 'prix_max' => $criteres['budget'], 'offre' => 'magasin'], fn($v) => $v !== ''));
}
