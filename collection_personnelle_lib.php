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
    return $requete->fetchAll(PDO::FETCH_ASSOC);
}

function volumesCollectionPersonnelle(PDO $pdo, int $idUser, int $idSerie): array
{
    $requete = $pdo->prepare('SELECT cle_volume,id_livre FROM collection_volume WHERE id_user=? AND id_serie=?');
    $requete->execute([$idUser,$idSerie]);
    return $requete->fetchAll(PDO::FETCH_KEY_PAIR);
}

function enregistrerCollectionPersonnelle(PDO $pdo, int $idUser, int $idSerie, array $identifiants): void
{
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
        $clesAffichees = array_map('cleVolumeCollection',$volumes);
        if ($clesAffichees) {
            $pdo->prepare('DELETE FROM collection_volume WHERE id_user=? AND id_serie=? AND cle_volume IN (' . implode(',',array_fill(0,count($clesAffichees),'?')) . ')')->execute([$idUser,$idSerie,...$clesAffichees]);
        }
        $ajout = $pdo->prepare('INSERT INTO collection_volume(id_user,id_serie,cle_volume,id_livre) VALUES (?,?,?,?)');
        foreach ($retenus as $cle => $idLivre) { $ajout->execute([$idUser,$idSerie,$cle,$idLivre]); }
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
