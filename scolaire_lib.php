<?php
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/categories_lib.php';

function optionsParcoursScolaire(PDO $pdo, string $niveau = ''): array
{
    $ids = idsCategorieEtEnfants($pdo, 'scolaire-etudes');
    $base = ' FROM livre l WHERE l.is_valide=1 AND l.id_categorie IN (' . implode(',', array_fill(0,count($ids),'?')) . ')';
    $requete = $pdo->prepare('SELECT DISTINCT l.niveau_scolaire' . $base . " AND l.niveau_scolaire IS NOT NULL AND l.niveau_scolaire<>'' ORDER BY l.niveau_scolaire");
    $requete->execute($ids);
    $niveaux = $requete->fetchAll(PDO::FETCH_COLUMN);
    $ordre=['cp'=>0,'ce1'=>1,'ce2'=>2,'cm1'=>3,'cm2'=>4,'6e'=>5,'5e'=>6,'4e'=>7,'3e'=>8,'seconde'=>9,'première'=>10,'terminale'=>11,'prépa'=>12];
    usort($niveaux,fn($a,$b)=>(($ordre[mb_strtolower($a)]??100)<=>($ordre[mb_strtolower($b)]??100)) ?: strnatcasecmp($a,$b));
    $matieres = [];
    if (in_array($niveau, $niveaux, true)) {
        $requete = $pdo->prepare('SELECT DISTINCT l.matiere' . $base . " AND l.niveau_scolaire=? AND l.matiere IS NOT NULL AND l.matiere<>'' ORDER BY l.matiere");
        $requete->execute([...$ids, $niveau]);
        $matieres = $requete->fetchAll(PDO::FETCH_COLUMN);
    }
    return ['niveaux'=>$niveaux, 'matieres'=>$matieres, 'ids'=>$ids];
}

function livresParcoursScolaire(PDO $pdo, array $options, string $niveau, string $matiere): array
{
    if (!in_array($niveau, $options['niveaux'], true) || ($matiere !== '' && !in_array($matiere, $options['matieres'], true))) { throw new InvalidArgumentException('Choisissez un niveau et une matière parmi les options proposées.'); }
    $conditions = ' AND l.id_categorie IN (' . implode(',',array_fill(0,count($options['ids']),'?')) . ') AND l.niveau_scolaire=?';
    $parametres = [...$options['ids'], $niveau];
    if ($matiere !== '') { $conditions .= ' AND l.matiere=?'; $parametres[] = $matiere; }
    $requete = $pdo->prepare('SELECT cartes.*, edition.annee_parution FROM (' . sqlCartesLivres($conditions) . ') cartes JOIN livre edition ON edition.id_livre=cartes.id_livre ORDER BY edition.annee_parution DESC, cartes.titre, cartes.id_livre LIMIT 12');
    $requete->execute($parametres);
    return $requete->fetchAll(PDO::FETCH_ASSOC);
}
