<?php
require_once __DIR__ . '/series_lib.php';

const COLLECTION_SERIES_MAX = 100;
const COLLECTION_VOLUMES_MAX = 500;

function collectionPersonnelleDisponible(PDO $pdo): bool
{
    try {
        $pdo->prepare('SELECT 1 FROM collection_serie LIMIT 0')->execute();
        $pdo->prepare('SELECT 1 FROM collection_volume LIMIT 0')->execute();
        return true;
    } catch (PDOException $e) {
        if (($e->errorInfo[0] ?? '') !== '42S02') { throw $e; }
        return false;
    }
}

function cleVolumeCollection(array $livre): string
{
    if ($livre['numero_tome'] !== null) {
        return 'tome:' . numeroTomeLisible($livre['numero_tome']) . ':' . numeroTomeLisible($livre['numero_tome_fin'] ?? $livre['numero_tome']);
    }
    return !empty($livre['cle_oeuvre']) ? 'oeuvre:' . $livre['cle_oeuvre'] : 'livre:' . (int) $livre['id_livre'];
}

function seriesCollectionPersonnelle(PDO $pdo, int $idUser): array
{
    $requete = $pdo->prepare('SELECT s.id_serie,s.nom,s.auteur,COUNT(v.cle_volume) AS possedes FROM collection_serie c JOIN serie s ON s.id_serie=c.id_serie LEFT JOIN collection_volume v ON v.id_user=c.id_user AND v.id_serie=c.id_serie WHERE c.id_user=? GROUP BY s.id_serie,s.nom,s.auteur ORDER BY s.nom LIMIT 100');
    $requete->execute([$idUser]);
    $series = $requete->fetchAll(PDO::FETCH_ASSOC);
    if (!$series) { return []; }
    $requete = $pdo->prepare('SELECT id_serie,cle_volume FROM collection_volume WHERE id_user=?');
    $requete->execute([$idUser]);
    $possedes = [];
    foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $volume) { $possedes[$volume['id_serie']][$volume['cle_volume']] = true; }
    $requete = $pdo->prepare('SELECT ls.* FROM collection_serie c JOIN livre_serie ls ON ls.id_serie=c.id_serie JOIN livre l ON l.id_livre=ls.id_livre WHERE c.id_user=? AND l.is_valide=1');
    $requete->execute([$idUser]);
    $references = [];
    foreach ($requete->fetchAll(PDO::FETCH_ASSOC) as $volume) { $references[$volume['id_serie']][cleVolumeCollection($volume)] = true; }
    foreach ($series as &$serie) {
        $cles = $references[$serie['id_serie']] ?? [];
        $serie['total'] = count($cles);
        $serie['possedes'] = count(array_intersect_key($cles,$possedes[$serie['id_serie']] ?? []));
    }
    unset($serie);
    return $series;
}

function messageProgressionCollection(string $nom, int $possedes, int $total): array
{
    $possedes = max(0,min($possedes,$total));
    $restants = max(0,$total-$possedes);
    if ($total <= 0) { return ['court'=>'La prochaine aventure se prépare…','titre'=>'Votre prochaine trouvaille vous attend !','detail'=>'Les volumes de cette série ne sont pas encore référencés au catalogue.','complete'=>false]; }
    if ($restants === 0) { return ['court'=>'Bravo, tout est sur votre étagère !','titre'=>'Bravo, vous avez réuni tous les volumes de « '.$nom.' » !','detail'=>'Tous les volumes référencés dans notre catalogue sont dans votre bibliothèque.','complete'=>true]; }
    if ($possedes === 0) { return ['court'=>'À vous la première trouvaille !','titre'=>'Une nouvelle aventure commence avec « '.$nom.' » !','detail'=>'Quel volume rejoindra votre étagère en premier ?','complete'=>false]; }
    $volumes = $possedes . ($possedes === 1 ? ' volume' : ' volumes');
    return ['court'=>'Déjà '.$volumes.' sur votre étagère !','titre'=>$restants === 1 ? 'Plus qu’un volume pour compléter « '.$nom.' » !' : 'Votre collection « '.$nom.' » prend vie !','detail'=>'Déjà '.$volumes.' dans votre bibliothèque. '.($restants === 1 ? 'La dernière trouvaille vous attend parmi les références du catalogue.' : 'Encore '.$restants.' à dénicher parmi les références du catalogue.'),'complete'=>false];
}

function volumesCollectionPersonnelle(PDO $pdo, int $idUser, int $idSerie): array
{
    $requete = $pdo->prepare('SELECT cle_volume,id_livre FROM collection_volume WHERE id_user=? AND id_serie=?');
    $requete->execute([$idUser,$idSerie]);
    return $requete->fetchAll(PDO::FETCH_KEY_PAIR);
}

function enregistrerCollectionPersonnelle(PDO $pdo, int $idUser, int $idSerie, array $identifiants, array $lectures = []): void
{
    foreach ($lectures as $id => $statut) {
        if ((!is_int($id) && !ctype_digit((string)$id)) || (int)$id <= 0 || !is_string($statut) || !in_array($statut,['','a_lire','en_cours','lu'],true)) { throw new DomainException('Statut de lecture invalide.'); }
    }
    if (count($lectures) > COLLECTION_VOLUMES_MAX) { throw new DomainException('Trop de statuts de lecture.'); }
    if (count($identifiants) > COLLECTION_VOLUMES_MAX) { throw new DomainException('Trop de volumes sélectionnés.'); }
    foreach ($identifiants as $id) {
        if (!is_string($id) || !ctype_digit($id) || (int) $id <= 0) { throw new DomainException('Un volume sélectionné est invalide.'); }
    }
    $pdo->beginTransaction();
    try {
        verrouillerProprietaireCollection($pdo,$idUser);
        $serie = $pdo->prepare('SELECT id_serie FROM serie WHERE id_serie=?');
        $serie->execute([$idSerie]);
        if (!$serie->fetchColumn()) { throw new DomainException('Cette série est introuvable.'); }
        $volumes = tomesDeSerie($pdo,$idSerie);
        if (count($volumes) > COLLECTION_VOLUMES_MAX) { throw new DomainException('Cette série dépasse la limite de volumes gérés pour le moment.'); }
        $idsVisibles = array_fill_keys(array_column($volumes,'id_livre'),true);
        foreach ($lectures as $id => $statut) { if (!isset($idsVisibles[(int)$id])) { throw new DomainException('Ce volume ne fait pas partie de cette série.'); } }
        $selection = array_fill_keys(array_map('intval',$identifiants),true);
        $retenus = [];
        foreach ($volumes as $volume) {
            if (isset($selection[(int) $volume['id_livre']])) {
                $retenus[cleVolumeCollection($volume)] = (int) $volume['id_livre'];
                unset($selection[(int) $volume['id_livre']]);
            }
        }
        if ($selection) { throw new DomainException('Un volume a changé ou ne fait pas partie de cette série. Rechargez la page.'); }
        $suivi = $pdo->prepare('SELECT id_serie FROM collection_serie WHERE id_user=?');
        $suivi->execute([$idUser]);
        $series = array_map('intval',$suivi->fetchAll(PDO::FETCH_COLUMN));
        if (!in_array($idSerie,$series,true) && count($series) >= COLLECTION_SERIES_MAX) { throw new DomainException('La limite est de 100 séries suivies.'); }
        $pdo->prepare('INSERT IGNORE INTO collection_serie(id_user,id_serie) VALUES (?,?)')->execute([$idUser,$idSerie]);
        $clesAffichees = array_values(array_diff(array_map('cleVolumeCollection',$volumes),array_keys($retenus)));
        if ($clesAffichees) {
            $pdo->prepare('DELETE FROM collection_volume WHERE id_user=? AND id_serie=? AND cle_volume IN (' . implode(',',array_fill(0,count($clesAffichees),'?')) . ')')->execute([$idUser,$idSerie,...$clesAffichees]);
        }
        $ajout = $pdo->prepare('INSERT INTO collection_volume(id_user,id_serie,cle_volume,id_livre) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id_livre=VALUES(id_livre)');
        $lectureDisponible = lectureCollectionDisponible($pdo);
        $modifierLecture = $lectureDisponible ? $pdo->prepare('UPDATE collection_volume SET statut_lecture=? WHERE id_user=? AND id_serie=? AND cle_volume=?') : null;
        foreach ($retenus as $cle => $idLivre) {
            $ajout->execute([$idUser,$idSerie,$cle,$idLivre]);
            if ($modifierLecture && array_key_exists($idLivre,$lectures)) { $modifierLecture->execute([$lectures[$idLivre] ?: null,$idUser,$idSerie,$cle]); }
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}

function verrouillerProprietaireCollection(PDO $pdo, int $idUser): void
{
    $requete = $pdo->prepare('SELECT role,statut FROM user WHERE id=? FOR UPDATE');
    $requete->execute([$idUser]);
    $membre = $requete->fetch(PDO::FETCH_ASSOC);
    if (!$membre || !in_array($membre['role'],['client','auteur'],true) || $membre['statut'] !== 'actif') { throw new DomainException('Cette collection est réservée aux lecteurs et auteurs actifs.'); }
}

function retirerSerieCollection(PDO $pdo, int $idUser, int $idSerie): void
{
    $pdo->beginTransaction();
    try {
        verrouillerProprietaireCollection($pdo,$idUser);
        $pdo->prepare('DELETE FROM collection_serie WHERE id_user=? AND id_serie=?')->execute([$idUser,$idSerie]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}

const STATUTS_LECTURE_COLLECTION = ['a_lire'=>'À lire','en_cours'=>'En cours','lu'=>'Lu'];

function lectureCollectionDisponible(PDO $pdo): bool
{
    try { $pdo->prepare('SELECT statut_lecture FROM collection_volume LIMIT 0')->execute(); return true; }
    catch (PDOException $e) { if (!in_array($e->errorInfo[0] ?? '',['42S22','42S02'],true)) { throw $e; } return false; }
}

function lecturesCollectionPersonnelle(PDO $pdo, int $idUser, int $idSerie): array
{
    if (!lectureCollectionDisponible($pdo)) { return []; }
    $requete=$pdo->prepare('SELECT cle_volume,statut_lecture FROM collection_volume WHERE id_user=? AND id_serie=?');
    $requete->execute([$idUser,$idSerie]);
    return $requete->fetchAll(PDO::FETCH_KEY_PAIR);
}
