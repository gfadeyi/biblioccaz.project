<?php

const REGLES_EMPRUNTS = [
    'emprunts_max' => ['Emprunts et réservations simultanés par lecteur', 3, 1, 20],
    'attentes_max' => ["Listes d'attente simultanées par lecteur", 3, 1, 20],
    'reservation_jours_ouvres' => ['Délai de retrait en jours ouvrés', 3, 1, 14],
    'duree_jours' => ['Durée proposée pour un nouvel exemplaire (jours)', 21, 7, 90],
    'renouvellements_max' => ['Renouvellements proposés pour un nouvel exemplaire', 2, 0, 5],
    'renouvellement_jours' => ['Durée de chaque prolongation (jours)', 14, 1, 90],
];

function reglesEmpruntsParDefaut(): array
{
    return array_map(fn(array $regle): int => $regle[1], REGLES_EMPRUNTS);
}

function validerReglesEmprunts(array $saisie): array
{
    if (count($saisie) !== count(REGLES_EMPRUNTS) || array_diff_key($saisie, REGLES_EMPRUNTS)) {
        throw new DomainException('Tous les réglages sont nécessaires.');
    }
    $regles = [];
    foreach (REGLES_EMPRUNTS as $cle => [$libelle, , $minimum, $maximum]) {
        $valeur = $saisie[$cle] ?? null;
        if ((!is_int($valeur) && !is_string($valeur)) || filter_var($valeur, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimum, 'max_range' => $maximum]]) === false) {
            throw new DomainException($libelle . ' : choisissez un entier entre ' . $minimum . ' et ' . $maximum . '.');
        }
        $regles[$cle] = (int) $valeur;
    }
    return $regles;
}

function chargerReglesEmprunts(PDO $pdo, bool $recharger = false): array
{
    static $cache = [];
    $cle = spl_object_id($pdo);
    if (!$recharger && isset($cache[$cle])) { return $cache[$cle]; }
    try {
        $requete = $pdo->prepare('SELECT version, configuration FROM regle_emprunt WHERE id_regle = 1');
        $requete->execute();
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);
        $cache[$cle] = ['disponible' => (bool) $ligne, 'version' => $ligne ? (int) $ligne['version'] : 0, 'regles' => $ligne ? validerReglesEmprunts(json_decode($ligne['configuration'], true, 512, JSON_THROW_ON_ERROR)) : reglesEmpruntsParDefaut()];
    } catch (PDOException $erreur) {
        if ((int) ($erreur->errorInfo[1] ?? 0) !== 1146) { throw $erreur; }
        $cache[$cle] = ['disponible' => false, 'version' => 0, 'regles' => reglesEmpruntsParDefaut()];
    }
    return $cache[$cle];
}

function regleEmprunt(PDO $pdo, string $cle): int
{
    return chargerReglesEmprunts($pdo)['regles'][$cle];
}

function joursRenouvellementEmprunt(array $emprunt): int
{
    return (int) ($emprunt['renouvellement_jours'] ?? 14);
}

function enregistrerReglesEmprunts(PDO $pdo, int $idAdministrateur, array $saisie, int $version): bool
{
    $regles = validerReglesEmprunts($saisie);
    $pdo->beginTransaction();
    try {
        $requete = $pdo->prepare("SELECT id FROM user WHERE id = ? AND role = 'admin' AND statut = 'actif' AND email_verifie = 1 FOR UPDATE");
        $requete->execute([$idAdministrateur]);
        if (!$requete->fetchColumn()) { throw new DomainException('Ces réglages sont réservés à un administrateur actif.'); }
        $requete = $pdo->prepare('SELECT version, configuration FROM regle_emprunt WHERE id_regle = 1 FOR UPDATE');
        $requete->execute();
        $avant = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$avant || (int) $avant['version'] !== $version) { throw new DomainException('Les règles ont changé. Rechargez la page avant de réessayer.'); }
        if (validerReglesEmprunts(json_decode($avant['configuration'], true, 512, JSON_THROW_ON_ERROR)) === $regles) {
            $pdo->commit();
            return false;
        }
        $configuration = json_encode($regles, JSON_THROW_ON_ERROR);
        $pdo->prepare('UPDATE regle_emprunt SET configuration = ?, version = version + 1, id_modifie_par = ?, date_modification = NOW() WHERE id_regle = 1')->execute([$configuration, $idAdministrateur]);
        $pdo->prepare('INSERT INTO regle_emprunt_historique (version, ancienne_configuration, nouvelle_configuration, id_modifie_par) VALUES (?, ?, ?, ?)')->execute([$version + 1, $avant['configuration'], $configuration, $idAdministrateur]);
        $pdo->commit();
        chargerReglesEmprunts($pdo, true);
        return true;
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erreur;
    }
}
